<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\Cliente;
use App\Models\Lancamento;
use App\Models\Profissional;
use App\Models\VendaItem;
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

    public function retencao(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresaId = auth()->user()->empresa_id;
        [$inicio, $fim] = $this->resolverPeriodo($request);

        $clientesNoPeriodo = Agendamento::where('company_id', $empresaId)
            ->whereNotIn('status', [Agendamento::STATUS_CANCELADO])
            ->whereBetween('data_hora', [$inicio->startOfDay(), $fim->copy()->endOfDay()])
            ->pluck('cliente_id')
            ->unique();

        $totalNoPeriodo = $clientesNoPeriodo->count();

        $recorrentes = $clientesNoPeriodo->filter(function ($clienteId) use ($empresaId, $inicio): bool {
            return Agendamento::where('company_id', $empresaId)
                ->where('cliente_id', $clienteId)
                ->whereNotIn('status', [Agendamento::STATUS_CANCELADO])
                ->where('data_hora', '<', $inicio->startOfDay())
                ->exists();
        })->count();

        $novos = $totalNoPeriodo - $recorrentes;
        $taxaRetencao = $totalNoPeriodo > 0 ? round($recorrentes / $totalNoPeriodo * 100, 1) : 0.0;

        return response()->json([
            'periodo' => $request->input('preset', '30d'),
            'total_clientes_periodo' => $totalNoPeriodo,
            'clientes_recorrentes' => $recorrentes,
            'clientes_novos' => $novos,
            'taxa_retencao_pct' => $taxaRetencao,
        ]);
    }

    public function comissoesJson(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresaId = auth()->user()->empresa_id;
        [$inicio, $fim] = $this->resolverPeriodo($request);

        $profissionais = Profissional::where('company_id', $empresaId)
            ->orderBy('name')
            ->get(['id', 'name', 'comissao_pct', 'cor']);

        $agFinalizados = Agendamento::where('company_id', $empresaId)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->whereBetween('data_hora', [$inicio->copy()->startOfDay(), $fim->copy()->endOfDay()])
            ->get(['profissional_id', 'valor'])
            ->groupBy('profissional_id');

        $rows = $profissionais->map(function (Profissional $prof) use ($agFinalizados): array {
            $items = $agFinalizados->get($prof->id, collect());
            $qtd = $items->count();
            $receita = (float) $items->sum('valor');
            $pct = (float) ($prof->comissao_pct ?? 0);
            $comissao = round($receita * $pct / 100, 2);

            return [
                'profissional_id' => $prof->id,
                'profissional_nome' => $prof->name,
                'cor' => $prof->cor ?? '#999999',
                'finalizados' => $qtd,
                'receita_bruta' => $receita,
                'comissao_pct' => $pct,
                'valor_comissao' => $comissao,
            ];
        });

        return response()->json($rows);
    }

    public function profissionaisJson(Request $request): JsonResponse
    {
        $empresaId = auth()->user()->empresa_id;
        [$inicio, $fim] = $this->resolverPeriodo($request);

        $profissionais = Profissional::where('company_id', $empresaId)->orderBy('name')->get();

        $rows = $profissionais->map(function (Profissional $prof) use ($empresaId, $inicio, $fim): array {
            $base = Agendamento::where('profissional_id', $prof->id)
                ->where('company_id', $empresaId)
                ->whereBetween('data_hora', [$inicio->startOfDay(), $fim->copy()->endOfDay()]);

            $total = (clone $base)->count();
            $finalizados = (clone $base)->where('status', Agendamento::STATUS_FINALIZADO)->count();
            $receita = (float) (clone $base)->where('status', Agendamento::STATUS_FINALIZADO)->sum('valor');
            $notaMedia = round((float) Avaliacao::whereHas('agendamento', fn ($q) => $q
                ->where('profissional_id', $prof->id)
                ->whereBetween('data_hora', [$inicio->startOfDay(), $fim->copy()->endOfDay()])
            )->avg('nota') ?? 0.0, 1);

            return [
                'id' => $prof->id,
                'name' => $prof->name,
                'total' => $total,
                'finalizados' => $finalizados,
                'receita_total' => $receita,
                'taxa_conclusao' => $total > 0 ? round($finalizados / $total * 100, 1) : 0.0,
                'nota_media' => $notaMedia,
            ];
        })->sortByDesc('receita_total')->values();

        return response()->json($rows);
    }

    public function exportarAvaliacoesCsv(Request $request): StreamedResponse
    {
        $empresaId = auth()->user()->empresa_id;
        [$inicio, $fim] = $this->resolverPeriodo($request);

        $avaliacoes = Avaliacao::where('company_id', $empresaId)
            ->whereHas('agendamento', fn ($q) => $q->whereBetween('data_hora', [$inicio->startOfDay(), $fim->copy()->endOfDay()]))
            ->with(['agendamento.cliente', 'agendamento.servico', 'agendamento.profissional'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'avaliacoes-'.$inicio->format('Y-m-d').'-ao-'.$fim->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($avaliacoes): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Data Avaliação', 'Data Agendamento', 'Cliente', 'Serviço', 'Profissional', 'Nota', 'Estrelas', 'Comentário'], ';');

            foreach ($avaliacoes as $av) {
                fputcsv($out, [
                    $av->created_at->format('d/m/Y'),
                    $av->agendamento?->data_hora?->format('d/m/Y H:i') ?? '',
                    $av->agendamento?->cliente?->name ?? '',
                    $av->agendamento?->servico?->nome ?? '',
                    $av->agendamento?->profissional?->name ?? '',
                    $av->nota,
                    $av->estrelas(),
                    $av->comentario ?? '',
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function avaliacoesJson(Request $request): JsonResponse
    {
        $empresaId = auth()->user()->empresa_id;
        [$inicio, $fim] = $this->resolverPeriodo($request);

        $limite = min((int) $request->input('limite', 20), 100);

        $avaliacoes = Avaliacao::where('company_id', $empresaId)
            ->whereHas('agendamento', fn ($q) => $q->whereBetween('data_hora', [$inicio->startOfDay(), $fim->copy()->endOfDay()]))
            ->with(['agendamento.cliente:id,name', 'agendamento.profissional:id,name', 'agendamento.servico:id,nome'])
            ->orderByDesc('created_at')
            ->limit($limite)
            ->get()
            ->map(fn (Avaliacao $av) => [
                'id' => $av->id,
                'nota' => $av->nota,
                'comentario' => $av->comentario,
                'data' => $av->created_at->toIso8601String(),
                'cliente_nome' => $av->agendamento?->cliente?->name ?? '',
                'profissional_nome' => $av->agendamento?->profissional?->name ?? '',
                'servico_nome' => $av->agendamento?->servico?->nome ?? '',
            ]);

        return response()->json($avaliacoes);
    }

    public function clientesJson(Request $request): JsonResponse
    {
        $empresaId = auth()->user()->empresa_id;
        [$inicio, $fim] = $this->resolverPeriodo($request);

        $limite = min((int) $request->input('limite', 20), 100);

        $rows = Agendamento::where('company_id', $empresaId)
            ->whereBetween('data_hora', [$inicio->startOfDay(), $fim->copy()->endOfDay()])
            ->whereNotIn('status', [Agendamento::STATUS_CANCELADO])
            ->with('cliente:id,name,phone')
            ->get(['cliente_id', 'status', 'valor'])
            ->groupBy('cliente_id')
            ->map(function ($items): array {
                $cliente = $items->first()->cliente;
                $finalizados = $items->where('status', Agendamento::STATUS_FINALIZADO);
                $receita = (float) $finalizados->sum('valor');
                $total = $items->count();

                return [
                    'id' => $cliente?->id,
                    'name' => $cliente?->name ?? 'Cliente excluído',
                    'phone' => $cliente?->phone ?? '',
                    'total_agendamentos' => $total,
                    'finalizados' => $finalizados->count(),
                    'receita_total' => $receita,
                    'ticket_medio' => $finalizados->count() > 0 ? round($receita / $finalizados->count(), 2) : 0.0,
                ];
            })
            ->sortByDesc('receita_total')
            ->take($limite)
            ->values();

        return response()->json($rows);
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

    public function heatmap(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresaId = auth()->user()->empresa_id;
        [$inicio, $fim] = $this->resolverPeriodo($request);

        $agendamentos = Agendamento::where('company_id', $empresaId)
            ->whereBetween('data_hora', [$inicio->startOfDay(), $fim->copy()->endOfDay()])
            ->whereNotIn('status', [Agendamento::STATUS_CANCELADO])
            ->get(['data_hora']);

        $dias = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        $horas = range(7, 21);

        $matriz = [];
        foreach ($dias as $idx => $nome) {
            foreach ($horas as $hora) {
                $matriz[] = [
                    'dia_semana' => $idx,
                    'dia_nome' => $nome,
                    'hora' => $hora,
                    'total' => 0,
                ];
            }
        }

        foreach ($agendamentos as $ag) {
            $dt = Carbon::parse($ag->data_hora);
            $diaSemana = (int) $dt->format('w');
            $hora = (int) $dt->format('G');

            if ($hora < 7 || $hora > 21) {
                continue;
            }

            $key = array_search(true, array_map(
                fn ($m) => $m['dia_semana'] === $diaSemana && $m['hora'] === $hora,
                $matriz
            ));

            if ($key !== false) {
                $matriz[$key]['total']++;
            }
        }

        return response()->json($matriz);
    }

    public function ocupacaoJson(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresaId = auth()->user()->empresa_id;
        [$inicio, $fim] = $this->resolverPeriodo($request);

        $profissionais = Profissional::where('company_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('name')
            ->get(['id', 'name', 'cor']);

        $result = $profissionais->map(function (Profissional $prof) use ($inicio, $fim, $empresaId): array {
            $base = Agendamento::where('company_id', $empresaId)
                ->where('profissional_id', $prof->id)
                ->whereBetween('data_hora', [$inicio->startOfDay(), $fim->copy()->endOfDay()]);

            $total = (clone $base)->count();
            $finalizados = (clone $base)->where('status', Agendamento::STATUS_FINALIZADO)->count();
            $cancelados = (clone $base)->where('status', Agendamento::STATUS_CANCELADO)->count();
            $receita = (float) (clone $base)->where('status', Agendamento::STATUS_FINALIZADO)->sum('valor');
            $duracaoMedia = (int) round((float) ((clone $base)->avg('duracao') ?? 0));

            return [
                'profissional_id' => $prof->id,
                'profissional_nome' => $prof->name,
                'cor' => $prof->cor ?? '#999999',
                'total_agendamentos' => $total,
                'finalizados' => $finalizados,
                'cancelados' => $cancelados,
                'taxa_conclusao' => $total > 0 ? round($finalizados / $total * 100, 1) : 0.0,
                'receita_total' => $receita,
                'duracao_media_min' => $duracaoMedia,
            ];
        });

        return response()->json($result);
    }

    public function horariosPico(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresaId = auth()->user()->empresa_id;
        [$inicio, $fim] = $this->resolverPeriodo($request);

        $agendamentos = Agendamento::where('company_id', $empresaId)
            ->whereNotIn('status', [Agendamento::STATUS_CANCELADO])
            ->whereBetween('data_hora', [$inicio->startOfDay(), $fim->copy()->endOfDay()])
            ->get(['data_hora']);

        $porHora = $agendamentos->groupBy(fn ($ag) => $ag->data_hora->format('H'))
            ->map(fn ($items, string $hora) => [
                'hora' => (int) $hora,
                'label' => $hora.':00',
                'total' => $items->count(),
            ])
            ->sortBy('hora')
            ->values();

        $maxTotal = $porHora->max('total') ?? 0;

        $porHora = $porHora->map(fn (array $item) => [
            ...$item,
            'pct' => $maxTotal > 0 ? round($item['total'] / $maxTotal * 100) : 0,
        ])->values();

        return response()->json([
            'periodo' => $request->input('preset', '30d'),
            'total_agendamentos' => $agendamentos->count(),
            'horarios' => $porHora,
        ]);
    }

    public function produtosMaisVendidos(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresaId = auth()->user()->empresa_id;
        [$inicio, $fim] = $this->resolverPeriodo($request);
        $limite = min((int) $request->input('limite', 10), 50);

        $itens = VendaItem::whereNotNull('produto_id')
            ->whereHas('venda', fn ($q) => $q->where('company_id', $empresaId)
                ->whereBetween('created_at', [$inicio->startOfDay(), $fim->copy()->endOfDay()])
            )
            ->with('produto:id,nome,sku,categoria,unidade')
            ->get(['produto_id', 'qtd', 'total']);

        $agrupados = $itens->groupBy('produto_id')
            ->map(function ($group): array {
                $produto = $group->first()->produto;

                return [
                    'produto_id' => $produto?->id ?? '',
                    'nome' => $produto?->nome ?? 'Produto removido',
                    'sku' => $produto?->sku ?? '',
                    'categoria' => $produto?->categoria ?? '',
                    'unidade' => $produto?->unidade ?? 'un.',
                    'qtd_total' => (int) $group->sum('qtd'),
                    'receita_total' => (float) $group->sum('total'),
                ];
            })
            ->sortByDesc('qtd_total')
            ->take($limite)
            ->values();

        return response()->json($agrupados);
    }

    public function evolucaoSemanal(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresaId = auth()->user()->empresa_id;
        $semanas = min((int) $request->input('semanas', 8), 26);
        $hoje = Carbon::today();

        $semanasList = collect(range($semanas - 1, 0))->map(function (int $i) use ($hoje, $empresaId): array {
            $inicio = $hoje->copy()->subWeeks($i)->startOfWeek();
            $fim = $hoje->copy()->subWeeks($i)->endOfWeek();

            $ags = Agendamento::where('company_id', $empresaId)
                ->whereBetween('data_hora', [$inicio->startOfDay(), $fim->copy()->endOfDay()])
                ->whereNotIn('status', [Agendamento::STATUS_CANCELADO])
                ->get(['status', 'valor']);

            $receita = (float) $ags->where('status', Agendamento::STATUS_FINALIZADO)->sum('valor');

            return [
                'semana' => $inicio->format('Y-W'),
                'label' => $inicio->format('d/m'),
                'total' => $ags->count(),
                'finalizados' => $ags->where('status', Agendamento::STATUS_FINALIZADO)->count(),
                'receita' => $receita,
            ];
        });

        return response()->json([
            'semanas' => $semanasList->values(),
        ]);
    }

    public function topClientes(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresaId = auth()->user()->empresa_id;
        [$inicio, $fim] = $this->resolverPeriodo($request);
        $limite = min((int) $request->input('limite', 10), 50);

        $rows = Agendamento::where('company_id', $empresaId)
            ->whereNotIn('status', [Agendamento::STATUS_CANCELADO])
            ->whereBetween('data_hora', [$inicio->startOfDay(), $fim->copy()->endOfDay()])
            ->with('cliente:id,name,phone,email')
            ->get(['cliente_id', 'status', 'valor'])
            ->groupBy('cliente_id')
            ->map(function ($items) {
                $cliente = $items->first()->cliente;

                return [
                    'cliente_id' => $cliente?->id ?? '',
                    'name' => $cliente?->name ?? 'Cliente removido',
                    'phone' => $cliente?->phone ?? '',
                    'email' => $cliente?->email ?? '',
                    'total_visitas' => $items->count(),
                    'total_gasto' => (float) $items->where('status', Agendamento::STATUS_FINALIZADO)->sum('valor'),
                ];
            })
            ->filter(fn ($row) => $row['cliente_id'] !== '')
            ->sortByDesc('total_visitas')
            ->take($limite)
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
