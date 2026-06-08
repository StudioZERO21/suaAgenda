<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Cliente;
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

        $receitaTotal = (clone $finalizados)->sum('valor');
        $totalAgendamentos = (clone $base)->count();
        $totalFinalizados = (clone $finalizados)->count();
        $ticketMedio = $totalFinalizados > 0 ? $receitaTotal / $totalFinalizados : 0;

        $novosClientes = Cliente::where('company_id', $empresaId)
            ->whereBetween('created_at', [$inicio->startOfDay(), $fim->copy()->endOfDay()])
            ->count();

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
            'receitaTotal',
            'totalAgendamentos',
            'totalFinalizados',
            'ticketMedio',
            'novosClientes',
            'receitaPorServico',
            'agendamentosPorProfissional',
            'maxServico',
            'maxProfissional',
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
            '7d' => [$hoje->copy()->subDays(6),    $hoje],
            '3m' => [$hoje->copy()->subMonths(3),  $hoje],
            '6m' => [$hoje->copy()->subMonths(6),  $hoje],
            'mes' => [$hoje->copy()->startOfMonth(), $hoje->copy()->endOfMonth()],
            'custom' => [
                Carbon::parse($request->input('de', $hoje->copy()->subDays(29)->toDateString())),
                Carbon::parse($request->input('ate', $hoje->toDateString())),
            ],
            default => [$hoje->copy()->subDays(29), $hoje], // 30d
        };
    }
}
