<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Profissional;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** @var array<int, string> */
    private const PROF_COLORS = [
        '#1a1a1a', '#d4a574', '#6366f1', '#10b981', '#f59e0b', '#ec4899',
    ];

    public function index(): View
    {
        $empresa = auth()->user()->empresa_id;

        if (! $empresa) {
            return view('dashboard', ['stats' => null]);
        }

        $hoje = today();
        $mesInicio = $hoje->copy()->startOfMonth();
        $mesFim = $hoje->copy()->endOfMonth();
        $mesAnteriorInicio = $mesInicio->copy()->subMonth();
        $mesAnteriorFim = $mesInicio->copy()->subDay();

        $baseMes = Agendamento::where('company_id', $empresa)
            ->whereBetween('data_hora', [$mesInicio, $mesFim]);

        $baseMesAnterior = Agendamento::where('company_id', $empresa)
            ->whereBetween('data_hora', [$mesAnteriorInicio, $mesAnteriorFim]);

        $totalMes = (clone $baseMes)->count();
        $confirmadosMes = (clone $baseMes)->where('status', Agendamento::STATUS_CONFIRMADO)->count();
        $pendentesMes = (clone $baseMes)->where('status', Agendamento::STATUS_PENDENTE)->count();
        $canceladosMes = (clone $baseMes)->where('status', Agendamento::STATUS_CANCELADO)->count();

        $receitaMes = (float) (clone $baseMes)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->sum('valor');

        $receitaMesAnterior = (float) (clone $baseMesAnterior)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->sum('valor');

        $totalMesAnterior = (clone $baseMesAnterior)->count();

        $novosClientesMes = Cliente::where('company_id', $empresa)
            ->whereBetween('created_at', [$mesInicio, $mesFim])
            ->count();

        $novosClientesMesAnterior = Cliente::where('company_id', $empresa)
            ->whereBetween('created_at', [$mesAnteriorInicio, $mesAnteriorFim])
            ->count();

        $taxaConfirmacao = $totalMes > 0
            ? (int) round($confirmadosMes / $totalMes * 100)
            : 0;

        $confirmadosMesAnterior = (clone $baseMesAnterior)
            ->where('status', Agendamento::STATUS_CONFIRMADO)
            ->count();
        $totalMesAnt = (clone $baseMesAnterior)->count();
        $taxaAnterior = $totalMesAnt > 0
            ? (int) round($confirmadosMesAnterior / $totalMesAnt * 100)
            : 0;

        $agendamentosHoje = Agendamento::where('company_id', $empresa)
            ->whereDate('data_hora', $hoje)
            ->whereNotIn('status', [Agendamento::STATUS_CANCELADO])
            ->count();

        $confirmadosHoje = Agendamento::where('company_id', $empresa)
            ->whereDate('data_hora', $hoje)
            ->where('status', Agendamento::STATUS_CONFIRMADO)
            ->count();

        $pendentesHoje = Agendamento::where('company_id', $empresa)
            ->whereDate('data_hora', $hoje)
            ->where('status', Agendamento::STATUS_PENDENTE)
            ->count();

        $receitaPrevistaHoje = (float) Agendamento::where('company_id', $empresa)
            ->whereDate('data_hora', $hoje)
            ->whereIn('status', [
                Agendamento::STATUS_PENDENTE,
                Agendamento::STATUS_CONFIRMADO,
            ])
            ->sum('valor');

        $proximosAgendamentos = Agendamento::where('company_id', $empresa)
            ->where('data_hora', '>=', now())
            ->whereIn('status', [
                Agendamento::STATUS_PENDENTE,
                Agendamento::STATUS_CONFIRMADO,
            ])
            ->with(['cliente', 'profissional', 'servico'])
            ->orderBy('data_hora')
            ->limit(9)
            ->get();

        $meuProfissionalId = auth()->user()->profissional_id;
        $isAdminEmpresa = auth()->user()->hasRole('admin_empresa');

        $pctConfirmados = $totalMes > 0
            ? (int) round($confirmadosMes / $totalMes * 100)
            : 0;
        $pctPendentes = $totalMes > 0
            ? (int) round($pendentesMes / $totalMes * 100)
            : 0;
        $pctCancelados = max(0, 100 - $pctConfirmados - $pctPendentes);

        $donut = [
            'total' => $totalMes,
            'segments' => [
                [
                    'label' => 'Confirmados',
                    'pct' => $pctConfirmados,
                    'count' => $confirmadosMes,
                    'color' => '#10b981',
                ],
                [
                    'label' => 'Pendentes',
                    'pct' => $pctPendentes,
                    'count' => $pendentesMes,
                    'color' => '#f59e0b',
                ],
                [
                    'label' => 'Cancelados',
                    'pct' => $pctCancelados,
                    'count' => $canceladosMes,
                    'color' => '#ef4444',
                ],
            ],
        ];

        $profissionais = Profissional::where('company_id', $empresa)
            ->ativo()
            ->withCount([
                'agendamentos as agendamentos_mes_count' => function ($q) use ($mesInicio, $mesFim) {
                    $q->whereBetween('data_hora', [$mesInicio, $mesFim])
                        ->where('status', '!=', Agendamento::STATUS_CANCELADO);
                },
            ])
            ->orderByDesc('agendamentos_mes_count')
            ->get()
            ->map(function (Profissional $prof, int $index) {
                $prof->cor = self::PROF_COLORS[$index % count(self::PROF_COLORS)];

                return $prof;
            });

        $maxProfCount = max($profissionais->max('agendamentos_mes_count') ?? 0, 1);

        $profCores = $profissionais->pluck('cor', 'id')->toArray();

        $kanbanQuery = Agendamento::where('company_id', $empresa)
            ->whereDate('data_hora', $hoje)
            ->with(['cliente', 'profissional', 'servico'])
            ->orderBy('data_hora');

        if (! $isAdminEmpresa && $meuProfissionalId !== null) {
            $kanbanQuery->where('profissional_id', $meuProfissionalId);
        }

        $kanbanCards = $kanbanQuery->get()->map(fn (Agendamento $ag) => [
            'id' => $ag->id,
            'status' => $ag->status,
            'hora' => $ag->data_hora->format('H:i'),
            'cliente' => $ag->cliente?->name ?? 'Cliente avulso',
            'servico' => $ag->servico?->nome ?? '—',
            'profissional' => explode(' ', $ag->profissional?->name ?? '—')[0],
            'profissional_id' => $ag->profissional_id,
            'cor' => $profCores[$ag->profissional_id] ?? '#1a1a1a',
            'duracao' => $ag->duracao,
            'valor' => (float) $ag->valor,
            'canEdit' => $isAdminEmpresa
                || ($meuProfissionalId === null)
                || ($meuProfissionalId !== null && $meuProfissionalId === $ag->profissional_id),
            'statusUrl' => route('agendamentos.updateStatus', $ag->id),
        ])->values();

        $stats = [
            'cards' => [
                [
                    'label' => 'Agendamentos',
                    'value' => $totalMes,
                    'trend' => $this->trendPercent($totalMes, $totalMesAnterior),
                    'icon' => 'calendar',
                ],
                [
                    'label' => 'Receita (mês)',
                    'value' => 'R$ '.number_format($receitaMes, 2, ',', '.'),
                    'trend' => $this->trendPercent($receitaMes, $receitaMesAnterior),
                    'icon' => 'dollar',
                ],
                [
                    'label' => 'Novos Clientes',
                    'value' => $novosClientesMes,
                    'trend' => $this->trendAbsolute($novosClientesMes, $novosClientesMesAnterior),
                    'icon' => 'users',
                ],
                [
                    'label' => 'Taxa Confirmação',
                    'value' => $taxaConfirmacao.'%',
                    'trend' => $this->trendPoints($taxaConfirmacao, $taxaAnterior),
                    'icon' => 'check',
                ],
            ],
            'agendamentosHoje' => $agendamentosHoje,
            'confirmadosHoje' => $confirmadosHoje,
            'pendentesHoje' => $pendentesHoje,
            'receitaPrevistaHoje' => $receitaPrevistaHoje,
            'proximosAgendamentos' => $proximosAgendamentos,
            'kanbanCards' => $kanbanCards,
            'donut' => $donut,
            'profissionais' => $profissionais,
            'maxProfCount' => $maxProfCount,
        ];

        return view('dashboard', compact('stats'));
    }

    /**
     * Calcula tendência percentual entre períodos.
     *
     * @return array{text: string, positive: bool}|null
     */
    private function trendPercent(float|int $atual, float|int $anterior): ?array
    {
        if ($anterior == 0) {
            if ($atual == 0) {
                return null;
            }

            return ['text' => '+100%', 'positive' => true];
        }

        $pct = (int) round((($atual - $anterior) / $anterior) * 100);

        return [
            'text' => ($pct >= 0 ? '+' : '').$pct.'%',
            'positive' => $pct >= 0,
        ];
    }

    /**
     * Calcula tendência absoluta (ex: novos clientes).
     *
     * @return array{text: string, positive: bool}|null
     */
    private function trendAbsolute(int $atual, int $anterior): ?array
    {
        $diff = $atual - $anterior;
        if ($diff === 0) {
            return null;
        }

        return [
            'text' => ($diff > 0 ? '+' : '').$diff,
            'positive' => $diff >= 0,
        ];
    }

    /**
     * Calcula tendência em pontos percentuais.
     *
     * @return array{text: string, positive: bool}|null
     */
    private function trendPoints(int $atual, int $anterior): ?array
    {
        $diff = $atual - $anterior;
        if ($diff === 0) {
            return null;
        }

        return [
            'text' => ($diff > 0 ? '+' : '').$diff.'%',
            'positive' => $diff >= 0,
        ];
    }
}
