<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\Cliente;
use App\Models\Lancamento;
use App\Models\Profissional;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
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

        // Evolução mensal: últimos 6 meses completos
        $evolucaoMensal = collect(range(5, 0))->map(function (int $i) use ($empresaId): array {
            $mes = Carbon::today()->startOfMonth()->subMonths($i);
            $fimMes = $mes->copy()->endOfMonth();

            $agsMes = Agendamento::where('company_id', $empresaId)
                ->whereBetween('data_hora', [$mes, $fimMes])
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = ? THEN valor ELSE 0 END) as receita', [Agendamento::STATUS_FINALIZADO])
                ->first();

            return [
                'mes' => $mes->translatedFormat('M/y'),
                'agendamentos' => (int) ($agsMes->total ?? 0),
                'receita' => (float) ($agsMes->receita ?? 0),
            ];
        })->values()->toArray();

        $maxEvolucaoAg = max(array_column($evolucaoMensal, 'agendamentos') ?: [0]) ?: 1;
        $maxEvolucaoRec = max(array_column($evolucaoMensal, 'receita') ?: [0]) ?: 1;

        // Fidelidade: top clientes por agendamentos no período
        $fidelidade = Agendamento::where('company_id', $empresaId)
            ->whereBetween('data_hora', [$inicio->copy()->startOfDay(), $fim->copy()->endOfDay()])
            ->whereNotIn('status', [Agendamento::STATUS_CANCELADO])
            ->with('cliente:id,name,phone,email')
            ->selectRaw('cliente_id, COUNT(*) as total_visitas, SUM(CASE WHEN status = ? THEN valor ELSE 0 END) as total_gasto, MAX(data_hora) as ultima_visita', [Agendamento::STATUS_FINALIZADO])
            ->groupBy('cliente_id')
            ->orderByDesc('total_visitas')
            ->limit(20)
            ->get()
            ->filter(fn ($row) => $row->cliente !== null)
            ->map(fn ($row) => [
                'name' => $row->cliente->name,
                'phone' => $row->cliente->phone ?? '',
                'visitas' => (int) $row->total_visitas,
                'gasto' => (float) $row->total_gasto,
                'ultima' => Carbon::parse($row->ultima_visita)->format('d/m/Y'),
                'ticket' => $row->total_visitas > 0 ? (float) $row->total_gasto / (int) $row->total_visitas : 0.0,
            ])
            ->values();

        // Avaliações do período
        $avaliacoes = Avaliacao::whereHas('agendamento', fn ($q) => $q
            ->where('company_id', $empresaId)
            ->whereBetween('data_hora', [$inicio->copy()->startOfDay(), $fim->copy()->endOfDay()])
        )
            ->with(['agendamento.cliente', 'agendamento.profissional'])
            ->get();

        $totalAvaliacoes = $avaliacoes->count();
        $notaMediaGeral = $totalAvaliacoes > 0 ? round((float) $avaliacoes->avg('nota'), 1) : null;

        $distribuicaoNotas = collect(range(1, 5))->mapWithKeys(fn (int $n) => [
            (string) $n => $avaliacoes->where('nota', $n)->count(),
        ])->toArray();

        $positivas = $avaliacoes->whereIn('nota', [4, 5])->count();
        $nps = $totalAvaliacoes > 0 ? (int) round($positivas / $totalAvaliacoes * 100) : 0;

        $notasPorProfissional = $avaliacoes
            ->filter(fn (Avaliacao $av) => $av->agendamento?->profissional !== null)
            ->groupBy(fn (Avaliacao $av) => $av->agendamento->profissional_id)
            ->map(fn ($avs) => [
                'name' => $avs->first()->agendamento->profissional->name,
                'nota' => round((float) $avs->avg('nota'), 1),
                'total' => $avs->count(),
            ])
            ->sortByDesc('nota')
            ->values();

        $comentariosRecentes = $avaliacoes
            ->filter(fn (Avaliacao $av) => $av->comentario !== null && strlen(trim((string) $av->comentario)) > 0)
            ->sortByDesc('created_at')
            ->take(8)
            ->map(fn (Avaliacao $av) => [
                'nota' => $av->nota,
                'comentario' => $av->comentario,
                'cliente' => $av->agendamento?->cliente?->name ?? 'Cliente',
                'data' => $av->created_at->format('d/m/Y'),
                'profissional' => $av->agendamento?->profissional?->name ?? '',
            ])
            ->values();

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
            'evolucaoMensal',
            'maxEvolucaoAg',
            'maxEvolucaoRec',
            'fidelidade',
            'totalAvaliacoes',
            'notaMediaGeral',
            'distribuicaoNotas',
            'nps',
            'notasPorProfissional',
            'comentariosRecentes',
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

    public function exportarFidelidadeCsv(Request $request): StreamedResponse
    {
        $empresaId = auth()->user()->empresa_id;
        [$inicio, $fim] = $this->resolverPeriodo($request);

        $rows = Agendamento::where('company_id', $empresaId)
            ->whereBetween('data_hora', [$inicio->copy()->startOfDay(), $fim->copy()->endOfDay()])
            ->whereNotIn('status', [Agendamento::STATUS_CANCELADO])
            ->with('cliente:id,name,phone,email')
            ->selectRaw('cliente_id, COUNT(*) as total_visitas, SUM(CASE WHEN status = ? THEN valor ELSE 0 END) as total_gasto, MAX(data_hora) as ultima_visita', [Agendamento::STATUS_FINALIZADO])
            ->groupBy('cliente_id')
            ->orderByDesc('total_visitas')
            ->get()
            ->filter(fn ($row) => $row->cliente !== null);

        $filename = 'fidelidade-'.$inicio->format('Y-m-d').'-ao-'.$fim->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Posição', 'Cliente', 'Telefone', 'Visitas', 'Total Gasto (R$)', 'Ticket Médio (R$)', 'Última Visita'], ';');

            foreach ($rows->values() as $i => $row) {
                $visitas = (int) $row->total_visitas;
                $gasto = (float) $row->total_gasto;
                fputcsv($out, [
                    $i + 1,
                    $row->cliente->name,
                    $row->cliente->phone ?? '',
                    $visitas,
                    number_format($gasto, 2, ',', '.'),
                    number_format($visitas > 0 ? $gasto / $visitas : 0.0, 2, ',', '.'),
                    Carbon::parse($row->ultima_visita)->format('d/m/Y'),
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportarComissoesCsv(Request $request): StreamedResponse
    {
        $empresaId = auth()->user()->empresa_id;
        [$inicio, $fim] = $this->resolverPeriodo($request);

        $profissionais = Profissional::where('company_id', $empresaId)
            ->orderBy('name')
            ->get();

        $agFinalizados = Agendamento::where('company_id', $empresaId)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->whereBetween('data_hora', [$inicio->copy()->startOfDay(), $fim->copy()->endOfDay()])
            ->selectRaw('profissional_id, COUNT(*) as qtd, SUM(valor) as receita')
            ->groupBy('profissional_id')
            ->get()
            ->keyBy('profissional_id');

        $filename = 'comissoes-'.$inicio->format('Y-m-d').'-ao-'.$fim->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($profissionais, $agFinalizados): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Profissional', 'Finalizados', 'Receita Bruta (R$)', '% Comissão', 'Valor Comissão (R$)'], ';');

            foreach ($profissionais as $prof) {
                $row = $agFinalizados->get($prof->id);
                $qtd = $row ? (int) $row->qtd : 0;
                $receita = $row ? (float) $row->receita : 0.0;
                $pct = (float) ($prof->comissao_pct ?? 0);
                $comissao = round($receita * $pct / 100, 2);
                fputcsv($out, [
                    $prof->name,
                    $qtd,
                    number_format($receita, 2, ',', '.'),
                    number_format($pct, 1, ',', '.'),
                    number_format($comissao, 2, ',', '.'),
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function servicosJson(Request $request): JsonResponse
    {
        $empresaId = auth()->user()->empresa_id;
        [$inicio, $fim] = $this->resolverPeriodo($request);

        $rows = Agendamento::where('company_id', $empresaId)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->whereBetween('data_hora', [$inicio->startOfDay(), $fim->copy()->endOfDay()])
            ->with('servico:id,nome,cor')
            ->get(['servico_id', 'valor'])
            ->groupBy('servico_id')
            ->map(function ($items): array {
                $servico = $items->first()->servico;
                $total = (float) $items->sum('valor');
                $qtd = $items->count();

                return [
                    'nome' => $servico?->nome ?? 'Sem serviço',
                    'cor' => $servico?->cor ?? '#999999',
                    'total_agendamentos' => $qtd,
                    'receita_total' => $total,
                    'ticket_medio' => $qtd > 0 ? round($total / $qtd, 2) : 0.0,
                ];
            })
            ->sortByDesc('receita_total')
            ->values();

        return response()->json($rows);
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
