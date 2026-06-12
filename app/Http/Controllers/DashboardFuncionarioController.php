<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Avaliacao;
use Illuminate\View\View;

/**
 * Dashboard do funcionário (perfil com cal_own): apenas dados próprios —
 * agenda do dia, próximos agendamentos, comissão do mês e nota média.
 */
class DashboardFuncionarioController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $empresa = $user->empresa_id;
        $profissional = $user->profissional;

        if (! $empresa || $profissional === null) {
            return view('dashboard-funcionario', ['stats' => null]);
        }

        $hoje = today();
        $mesInicio = $hoje->copy()->startOfMonth();
        $mesFim = $hoje->copy()->endOfMonth();

        $baseProprio = Agendamento::where('company_id', $empresa)
            ->where('profissional_id', $profissional->id);

        $agendaHoje = (clone $baseProprio)
            ->whereDate('data_hora', $hoje)
            ->whereNotIn('status', Agendamento::STATUSES_INATIVOS)
            ->with(['cliente:id,name,phone', 'servico:id,nome,cor'])
            ->orderBy('data_hora')
            ->get();

        $proximos = (clone $baseProprio)
            ->where('data_hora', '>', now())
            ->whereIn('status', [Agendamento::STATUS_PENDENTE, Agendamento::STATUS_CONFIRMADO])
            ->with(['cliente:id,name', 'servico:id,nome,cor'])
            ->orderBy('data_hora')
            ->limit(7)
            ->get();

        $finalizadosMes = (clone $baseProprio)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->whereBetween('data_hora', [$mesInicio, $mesFim]);

        $receitaMes = (float) (clone $finalizadosMes)->sum('valor');
        $atendimentosMes = (clone $finalizadosMes)->count();
        $pct = (float) ($profissional->comissao_pct ?? 0);
        $comissaoMes = round($receitaMes * $pct / 100, 2);

        $clientesAtendidosMes = (clone $finalizadosMes)
            ->distinct('cliente_id')
            ->count('cliente_id');

        $notaMedia = Avaliacao::whereHas('agendamento', function ($q) use ($empresa, $profissional, $mesInicio, $mesFim): void {
            $q->where('company_id', $empresa)
                ->where('profissional_id', $profissional->id)
                ->whereBetween('data_hora', [$mesInicio, $mesFim]);
        })->avg('nota');

        $stats = [
            'profissional' => $profissional,
            'agendaHoje' => $agendaHoje,
            'proximos' => $proximos,
            'atendimentosMes' => $atendimentosMes,
            'receitaMes' => $receitaMes,
            'comissaoPct' => $pct,
            'comissaoMes' => $comissaoMes,
            'clientesAtendidosMes' => $clientesAtendidosMes,
            'notaMedia' => $notaMedia !== null ? round((float) $notaMedia, 1) : null,
            'podeVerComissao' => $user->can('fin_own') || $user->can('fin_view'),
        ];

        return view('dashboard-funcionario', compact('stats'));
    }
}
