<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Lancamento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        // Heatmap: 7 dias (0=Seg…6=Dom) × 24 horas — processado em PHP para compatibilidade DB
        $heatmap = array_fill(0, 7, array_fill(0, 24, 0));
        Agendamento::where('company_id', $empresaId)
            ->whereBetween('data_hora', [$inicio->copy()->startOfDay(), $fim->copy()->endOfDay()])
            ->pluck('data_hora')
            ->each(function (Carbon $dt) use (&$heatmap): void {
                // Carbon dayOfWeek: 0=Dom…6=Sáb → converter para 0=Seg…6=Dom
                $day = $dt->dayOfWeek === 0 ? 6 : $dt->dayOfWeek - 1;
                $heatmap[$day][$dt->hour]++;
            });

        $maxHeatmap = max(array_map('max', $heatmap)) ?: 1;

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
            'heatmap',
            'maxHeatmap',
            'inicio',
            'fim',
            'request',
        ));
    }

    public function exportarCsv(Request $request): StreamedResponse
    {
        $empresaId = auth()->user()->empresa_id;
        [$inicio, $fim] = $this->resolverPeriodo($request);

        $agendamentos = Agendamento::where('company_id', $empresaId)
            ->with(['cliente', 'servico', 'profissional'])
            ->whereBetween('data_hora', [$inicio->copy()->startOfDay(), $fim->copy()->endOfDay()])
            ->orderByDesc('data_hora')
            ->get();

        $lancamentos = Lancamento::where('company_id', $empresaId)
            ->whereBetween('data', [$inicio->format('Y-m-d'), $fim->format('Y-m-d')])
            ->orderByDesc('data')
            ->get();

        $filename = 'relatorio-'.$inicio->format('Y-m-d').'-ao-'.$fim->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($agendamentos, $lancamentos): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Data', 'Cliente', 'Serviço', 'Profissional', 'Tipo', 'Valor (R$)', 'Status'], ';');

            foreach ($agendamentos as $a) {
                fputcsv($out, [
                    $a->data_hora->format('d/m/Y H:i'),
                    $a->cliente?->name ?? 'Cliente avulso',
                    $a->servico?->nome ?? '—',
                    $a->profissional?->name ?? '—',
                    'agendamento',
                    number_format((float) $a->valor, 2, ',', '.'),
                    match ($a->status) {
                        Agendamento::STATUS_FINALIZADO => 'Finalizado',
                        Agendamento::STATUS_CANCELADO => 'Cancelado',
                        Agendamento::STATUS_CONFIRMADO => 'Confirmado',
                        default => 'Pendente',
                    },
                ], ';');
            }

            foreach ($lancamentos as $l) {
                fputcsv($out, [
                    $l->data->format('d/m/Y'),
                    $l->descricao,
                    $l->categoria ?? '—',
                    '—',
                    $l->tipo,
                    number_format((float) $l->valor, 2, ',', '.'),
                    match ($l->status) {
                        'pago' => 'Pago',
                        'cancelado' => 'Cancelado',
                        default => 'Pendente',
                    },
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
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
