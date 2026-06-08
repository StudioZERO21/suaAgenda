<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Lancamento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RelatorioController extends Controller
{
    public function index(Request $request): View
    {
        $empresaId = auth()->user()->empresa_id;

        [$inicio, $fim] = $this->resolverPeriodo($request);

        $base = Agendamento::where('company_id', $empresaId)
            ->whereBetween('data_hora', [$inicio->startOfDay(), $fim->copy()->endOfDay()]);

        $finalizados = (clone $base)->where('status', Agendamento::STATUS_FINALIZADO);

        $receitaAgendamentos = (float) (clone $finalizados)->sum('valor');
        $totalAgendamentos = (clone $base)->count();
        $totalFinalizados = (clone $finalizados)->count();
        $ticketMedio = $totalFinalizados > 0 ? $receitaAgendamentos / $totalFinalizados : 0.0;

        $novosClientes = Cliente::where('company_id', $empresaId)
            ->whereBetween('created_at', [$inicio->startOfDay(), $fim->copy()->endOfDay()])
            ->count();

        // Lancamentos financeiros do período
        $lancamentos = Lancamento::where('company_id', $empresaId)
            ->whereBetween('data', [$inicio->format('Y-m-d'), $fim->format('Y-m-d')])
            ->get();

        $receitaLancamentos = (float) $lancamentos->where('tipo', 'receita')->sum('valor');
        $totalDespesas = (float) $lancamentos->where('tipo', 'despesa')->sum('valor');
        $receitaBruta = $receitaAgendamentos + $receitaLancamentos;
        $lucroLiquido = $receitaBruta - $totalDespesas;

        $despesasPorCategoria = $lancamentos
            ->where('tipo', 'despesa')
            ->groupBy(fn (Lancamento $l) => $l->categoria ?: 'Sem categoria')
            ->map(fn ($items, string $cat) => [
                'categoria' => $cat,
                'total' => (float) $items->sum('valor'),
                'quantidade' => $items->count(),
            ])
            ->sortByDesc('total')
            ->values();

        $maxDespesa = $despesasPorCategoria->max('total') ?: 1;

        $receitaPorServico = (clone $finalizados)
            ->with('servico')
            ->selectRaw('servico_id, SUM(valor) as total, COUNT(*) as quantidade')
            ->groupBy('servico_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'nome' => $row->servico?->nome ?? 'Sem serviço',
                'cor' => $row->servico?->cor ?? '#999999',
                'total' => (float) $row->total,
                'quantidade' => (int) $row->quantidade,
            ]);

        $agendamentosPorProfissional = (clone $base)
            ->with('profissional')
            ->selectRaw('profissional_id, status, SUM(valor) as total, COUNT(*) as quantidade')
            ->groupBy('profissional_id', 'status')
            ->get()
            ->groupBy('profissional_id')
            ->map(fn ($rows) => [
                'name' => $rows->first()->profissional?->name ?? 'Sem profissional',
                'total' => (float) $rows->where('status', Agendamento::STATUS_FINALIZADO)->sum('total'),
                'quantidade' => (int) $rows->sum('quantidade'),
                'finalizados' => (int) $rows->where('status', Agendamento::STATUS_FINALIZADO)->sum('quantidade'),
            ])
            ->sortByDesc('total')
            ->values();

        $maxServico = $receitaPorServico->max('total') ?: 1;
        $maxProfissional = $agendamentosPorProfissional->max('total') ?: 1;

        return view('relatorios.index', compact(
            'receitaAgendamentos',
            'receitaLancamentos',
            'receitaBruta',
            'totalDespesas',
            'lucroLiquido',
            'totalAgendamentos',
            'totalFinalizados',
            'ticketMedio',
            'novosClientes',
            'receitaPorServico',
            'agendamentosPorProfissional',
            'despesasPorCategoria',
            'maxServico',
            'maxProfissional',
            'maxDespesa',
            'inicio',
            'fim',
            'request',
        ));
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function resolverPeriodo(Request $request): array
    {
        $preset = $request->input('preset', '30d');
        $hoje = Carbon::today();

        return match ($preset) {
            '7d' => [$hoje->copy()->subDays(6), $hoje],
            '3m' => [$hoje->copy()->subMonths(3), $hoje],
            '6m' => [$hoje->copy()->subMonths(6), $hoje],
            'mes' => [$hoje->copy()->startOfMonth(), $hoje->copy()->endOfMonth()],
            'custom' => [
                Carbon::parse($request->input('de', $hoje->copy()->subDays(29)->toDateString())),
                Carbon::parse($request->input('ate', $hoje->toDateString())),
            ],
            default => [$hoje->copy()->subDays(29), $hoje], // 30d
        };
    }
}
