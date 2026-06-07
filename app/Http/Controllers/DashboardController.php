<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Profissional;
use App\Models\Servico;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $empresa = auth()->user()->empresa_id;

        if (! $empresa) {
            return view('dashboard', ['stats' => null]);
        }

        $hoje = today();
        $mesInicio = $hoje->copy()->startOfMonth();

        $agendamentosHoje = Agendamento::where('company_id', $empresa)
            ->whereDate('data_hora', $hoje)
            ->whereIn('status', [Agendamento::STATUS_PENDENTE, Agendamento::STATUS_CONFIRMADO])
            ->count();

        $finalizadosHoje = Agendamento::where('company_id', $empresa)
            ->whereDate('data_hora', $hoje)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->count();

        $receitaHoje = Agendamento::where('company_id', $empresa)
            ->whereDate('data_hora', $hoje)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->sum('valor');

        $receitaMes = Agendamento::where('company_id', $empresa)
            ->whereBetween('data_hora', [$mesInicio, $hoje->copy()->endOfDay()])
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->sum('valor');

        $totalClientes = Cliente::where('company_id', $empresa)->count();

        $totalProfissionais = Profissional::where('company_id', $empresa)->ativo()->count();

        $totalServicos = Servico::where('company_id', $empresa)->ativo()->count();

        $proximosAgendamentos = Agendamento::where('company_id', $empresa)
            ->where('data_hora', '>=', now())
            ->whereIn('status', [Agendamento::STATUS_PENDENTE, Agendamento::STATUS_CONFIRMADO])
            ->with(['cliente', 'profissional', 'servico'])
            ->orderBy('data_hora')
            ->limit(5)
            ->get();

        $statusDistribuicao = Agendamento::where('company_id', $empresa)
            ->whereDate('data_hora', $hoje)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $stats = compact(
            'agendamentosHoje',
            'finalizadosHoje',
            'receitaHoje',
            'receitaMes',
            'totalClientes',
            'totalProfissionais',
            'totalServicos',
            'proximosAgendamentos',
            'statusDistribuicao'
        );

        return view('dashboard', compact('stats'));
    }
}
