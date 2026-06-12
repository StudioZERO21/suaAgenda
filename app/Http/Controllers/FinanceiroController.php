<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreLancamentoRequest;
use App\Models\Agendamento;
use App\Models\Lancamento;
use App\Support\SaDemoData;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceiroController extends Controller
{
    public function index(Request $request): View
    {
        $empresaId = auth()->user()->empresa_id;
        $periodo = $request->input('periodo', 'month');

        [$inicio, $fim] = $this->resolverPeriodo($periodo);

        $base = Agendamento::where('company_id', $empresaId)
            ->with(['cliente', 'servico', 'profissional'])
            ->whereBetween('data_hora', [
                $inicio->copy()->startOfDay(),
                $fim->copy()->endOfDay(),
            ]);

        $finalizados = (clone $base)
            ->where('status', Agendamento::STATUS_FINALIZADO);

        $receitaTotal = (float) (clone $finalizados)->sum('valor');
        $totalFinalizados = (clone $finalizados)->count();
        $ticketMedio = $totalFinalizados > 0
            ? $receitaTotal / $totalFinalizados
            : 0.0;

        $pendentes = (clone $base)
            ->whereIn('status', [
                Agendamento::STATUS_PENDENTE,
                Agendamento::STATUS_CONFIRMADO,
            ]);

        $aReceber = (float) (clone $pendentes)->sum('valor');
        $qtdPendentes = (clone $pendentes)->count();

        // Compute commissions first so comissaoTotal can be derived from real data
        $comissoesProfissionais = $this->comissoesPorProfissional(
            $empresaId,
            $inicio,
            $fim,
            $receitaTotal,
        );
        $comissaoTotal = (float) array_sum(array_column($comissoesProfissionais, 'valor'));

        $metodoLabels = ['Pix', 'Cartão Crédito', 'Cartão Débito', 'Dinheiro'];

        $agTransacoes = (clone $base)
            ->orderByDesc('data_hora')
            ->limit(40)
            ->get()
            ->map(fn (Agendamento $a, int $i) => [
                'id' => $a->id,
                'data' => $a->data_hora->format('Y-m-d'),
                'cliente' => $a->cliente?->name ?? 'Cliente avulso',
                'servico' => $a->servico?->nome ?? '—',
                'profissional' => $a->profissional?->name ?? '—',
                'valor' => (float) $a->valor,
                'status' => $a->status,
                'status_key' => match ($a->status) {
                    Agendamento::STATUS_FINALIZADO => 'paid',
                    Agendamento::STATUS_CANCELADO => 'refunded',
                    default => 'pending',
                },
                'tipo' => 'receita',
                'metodo' => $metodoLabels[$i % count($metodoLabels)],
                'source' => 'agendamento',
            ]);

        $lancTransacoes = Lancamento::where('company_id', $empresaId)
            ->whereBetween('data', [$inicio->format('Y-m-d'), $fim->format('Y-m-d')])
            ->orderByDesc('data')
            ->limit(20)
            ->get()
            ->map(fn (Lancamento $l) => $this->lancamentoToJson($l));

        $transacoes = $agTransacoes->concat($lancTransacoes)
            ->sortByDesc('data')
            ->values()
            ->take(50);

        $profissionaisFiltro = $agTransacoes
            ->pluck('profissional')
            ->unique()
            ->filter(fn (string $nome) => $nome !== '—')
            ->values()
            ->all();

        $receitaDiaria = $this->receitaDiaria($empresaId, $inicio, $fim);

        $metodos = SaDemoData::metodosPagamento();

        return view('financeiro.index', compact(
            'receitaTotal',
            'ticketMedio',
            'comissaoTotal',
            'aReceber',
            'qtdPendentes',
            'totalFinalizados',
            'transacoes',
            'profissionaisFiltro',
            'receitaDiaria',
            'comissoesProfissionais',
            'metodos',
            'periodo',
            'inicio',
            'fim',
        ));
    }

    public function exportarCsv(Request $request): StreamedResponse
    {
        $empresaId = auth()->user()->empresa_id;
        $periodo = $request->input('periodo', 'month');
        [$inicio, $fim] = $this->resolverPeriodo($periodo);

        $agendamentos = Agendamento::where('company_id', $empresaId)
            ->with(['cliente', 'servico', 'profissional'])
            ->whereBetween('data_hora', [$inicio->copy()->startOfDay(), $fim->copy()->endOfDay()])
            ->orderByDesc('data_hora')
            ->get();

        $lancamentos = Lancamento::where('company_id', $empresaId)
            ->whereBetween('data', [$inicio->format('Y-m-d'), $fim->format('Y-m-d')])
            ->orderByDesc('data')
            ->get();

        $filename = 'financeiro-'.$inicio->format('Y-m-d').'-ao-'.$fim->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($agendamentos, $lancamentos): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel
            fputcsv($out, ['Data', 'Descrição', 'Categoria', 'Profissional', 'Tipo', 'Método', 'Valor (R$)', 'Status'], ';');

            foreach ($agendamentos as $a) {
                fputcsv($out, [
                    $a->data_hora->format('d/m/Y'),
                    $a->cliente?->name ?? 'Cliente avulso',
                    $a->servico?->nome ?? '—',
                    $a->profissional?->name ?? '—',
                    'receita',
                    '—',
                    number_format((float) $a->valor, 2, ',', '.'),
                    match ($a->status) {
                        Agendamento::STATUS_FINALIZADO => 'Pago',
                        Agendamento::STATUS_CANCELADO => 'Cancelado',
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
                    $l->metodo_pagamento ?? '—',
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

    public function resumo(Request $request): JsonResponse
    {
        $empresaId = auth()->user()->empresa_id;
        $periodo = $request->input('periodo', 'month');
        [$inicio, $fim] = $this->resolverPeriodo($periodo);

        $agFinalizados = Agendamento::where('company_id', $empresaId)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->whereBetween('data_hora', [$inicio->copy()->startOfDay(), $fim->copy()->endOfDay()]);

        $receitaAgendamentos = (float) (clone $agFinalizados)->sum('valor');
        $totalFinalizados = (clone $agFinalizados)->count();

        $lancamentos = Lancamento::where('company_id', $empresaId)
            ->whereBetween('data', [$inicio->format('Y-m-d'), $fim->format('Y-m-d')])
            ->get();

        $receitaLancamentos = (float) $lancamentos->where('tipo', 'receita')->where('status', 'pago')->sum('valor');
        $despesas = (float) $lancamentos->where('tipo', 'despesa')->where('status', 'pago')->sum('valor');
        $receitaBruta = $receitaAgendamentos + $receitaLancamentos;
        $lucroLiquido = $receitaBruta - $despesas;
        $ticketMedio = $totalFinalizados > 0 ? round($receitaAgendamentos / $totalFinalizados, 2) : 0.0;

        return response()->json([
            'periodo' => $periodo,
            'receita_agendamentos' => $receitaAgendamentos,
            'receita_lancamentos' => $receitaLancamentos,
            'receita_bruta' => $receitaBruta,
            'despesas' => $despesas,
            'lucro_liquido' => $lucroLiquido,
            'total_finalizados' => $totalFinalizados,
            'ticket_medio' => $ticketMedio,
        ]);
    }

    public function storeLancamento(StoreLancamentoRequest $request): JsonResponse
    {
        $lancamento = Lancamento::create([
            ...$request->validated(),
            'company_id' => auth()->user()->empresa_id,
        ]);

        return response()->json($this->lancamentoToJson($lancamento), 201);
    }

    public function updateLancamento(StoreLancamentoRequest $request, Lancamento $lancamento): JsonResponse
    {
        abort_if($lancamento->company_id !== auth()->user()->empresa_id, 403);

        $lancamento->update($request->validated());

        return response()->json($this->lancamentoToJson($lancamento));
    }

    public function fluxoCaixa(Request $request): JsonResponse
    {
        $empresaId = auth()->user()->empresa_id;
        $periodo = $request->input('periodo', 'month');
        [$inicio, $fim] = $this->resolverPeriodo($periodo);

        $dias = $inicio->diffInDays($fim) + 1;
        $serie = [];

        for ($i = 0; $i < $dias; $i++) {
            $dia = $inicio->copy()->addDays($i);
            $serie[$dia->format('Y-m-d')] = [
                'data' => $dia->format('Y-m-d'),
                'receita' => 0.0,
                'despesa' => 0.0,
                'saldo' => 0.0,
            ];
        }

        $agFinalizados = Agendamento::where('company_id', $empresaId)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->whereBetween('data_hora', [$inicio->copy()->startOfDay(), $fim->copy()->endOfDay()])
            ->get(['data_hora', 'valor']);

        foreach ($agFinalizados as $ag) {
            $key = Carbon::parse($ag->data_hora)->format('Y-m-d');
            if (isset($serie[$key])) {
                $serie[$key]['receita'] += (float) $ag->valor;
            }
        }

        $lancamentos = Lancamento::where('company_id', $empresaId)
            ->where('status', 'pago')
            ->whereBetween('data', [$inicio->format('Y-m-d'), $fim->format('Y-m-d')])
            ->get(['data', 'tipo', 'valor']);

        foreach ($lancamentos as $l) {
            $key = Carbon::parse($l->data)->format('Y-m-d');
            if (isset($serie[$key])) {
                if ($l->tipo === 'receita') {
                    $serie[$key]['receita'] += (float) $l->valor;
                } else {
                    $serie[$key]['despesa'] += (float) $l->valor;
                }
            }
        }

        foreach ($serie as &$dia) {
            $dia['saldo'] = round($dia['receita'] - $dia['despesa'], 2);
            $dia['receita'] = round($dia['receita'], 2);
            $dia['despesa'] = round($dia['despesa'], 2);
        }

        return response()->json(array_values($serie));
    }

    public function buscarLancamentos(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        $empresaId = auth()->user()->empresa_id;

        if ($q === '') {
            return response()->json([]);
        }

        $lancamentos = Lancamento::where('company_id', $empresaId)
            ->where(function ($query) use ($q): void {
                $query->where('descricao', 'like', "%{$q}%")
                    ->orWhere('categoria', 'like', "%{$q}%")
                    ->orWhere('tipo', 'like', "%{$q}%");
            })
            ->orderByDesc('data')
            ->limit(20)
            ->get()
            ->map(fn (Lancamento $l) => [
                'id' => $l->id,
                'descricao' => $l->descricao,
                'tipo' => $l->tipo,
                'categoria' => $l->categoria ?? '',
                'valor' => (float) $l->valor,
                'data' => $l->data->format('Y-m-d'),
                'status' => $l->status,
            ]);

        return response()->json($lancamentos);
    }

    public function statusLancamento(Request $request, Lancamento $lancamento): JsonResponse
    {
        abort_if($lancamento->company_id !== auth()->user()->empresa_id, 403);

        if (! auth()->user()->hasAnyRole(['admin_empresa', 'gestor'])) {
            abort(403);
        }

        $request->validate(['status' => ['required', 'in:pendente,pago,cancelado']]);

        $lancamento->update(['status' => $request->input('status')]);

        return response()->json([
            'status' => $lancamento->status,
            'updated_at' => $lancamento->updated_at->toIso8601String(),
        ]);
    }

    public function categoriaLancamento(Request $request, Lancamento $lancamento): JsonResponse
    {
        abort_if($lancamento->company_id !== auth()->user()->empresa_id, 403);

        $request->validate(['categoria' => ['nullable', 'string', 'max:60']]);

        $lancamento->update(['categoria' => $request->input('categoria')]);

        return response()->json([
            'categoria' => $lancamento->categoria ?? '',
            'updated_at' => $lancamento->updated_at->toIso8601String(),
        ]);
    }

    public function observacaoLancamento(Request $request, Lancamento $lancamento): JsonResponse
    {
        abort_if($lancamento->company_id !== auth()->user()->empresa_id, 403);

        $request->validate(['observacao' => ['nullable', 'string', 'max:1000']]);

        $lancamento->update(['observacao' => $request->input('observacao')]);

        return response()->json([
            'observacao' => $lancamento->observacao ?? '',
            'updated_at' => $lancamento->updated_at->toIso8601String(),
        ]);
    }

    public function showLancamento(Lancamento $lancamento): JsonResponse
    {
        abort_if($lancamento->company_id !== auth()->user()->empresa_id, 403);

        return response()->json($this->lancamentoToJson($lancamento));
    }

    public function destroyLancamento(Lancamento $lancamento): Response
    {
        abort_if($lancamento->company_id !== auth()->user()->empresa_id, 403);

        $lancamento->delete();

        return response()->noContent();
    }

    private function lancamentoToJson(Lancamento $l): array
    {
        return [
            'id' => $l->id,
            'data' => $l->data->format('Y-m-d'),
            'cliente' => $l->descricao,
            'servico' => $l->categoria ?? '—',
            'profissional' => '—',
            'valor' => (float) $l->valor,
            'status' => $l->status,
            'status_key' => match ($l->status) {
                'pago' => 'paid',
                'cancelado' => 'refunded',
                default => 'pending',
            },
            'tipo' => $l->tipo,
            'metodo' => $l->metodo_pagamento ?? '—',
            'source' => 'lancamento',
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolverPeriodo(string $periodo): array
    {
        $hoje = Carbon::today();

        return match ($periodo) {
            'quarter' => [$hoje->copy()->startOfQuarter(), $hoje->copy()->endOfQuarter()],
            'year' => [$hoje->copy()->startOfYear(), $hoje->copy()->endOfYear()],
            default => [$hoje->copy()->startOfMonth(), $hoje->copy()->endOfMonth()],
        };
    }

    /**
     * @return list<float>
     */
    private function receitaDiaria(
        string $empresaId,
        Carbon $inicio,
        Carbon $fim,
    ): array {
        $dias = $inicio->diffInDays($fim) + 1;
        $serie = [];

        for ($i = 0; $i < $dias; $i++) {
            $dia = $inicio->copy()->addDays($i);

            $serie[] = (float) Agendamento::where('company_id', $empresaId)
                ->where('status', Agendamento::STATUS_FINALIZADO)
                ->whereDate('data_hora', $dia)
                ->sum('valor');
        }

        return $serie;
    }

    /**
     * @return list<array{name: string, cor: string, valor: float, pct: float}>
     */
    private function comissoesPorProfissional(
        string $empresaId,
        Carbon $inicio,
        Carbon $fim,
        float $receitaTotal,
    ): array {
        if ($receitaTotal <= 0) {
            return [];
        }

        $porProf = Agendamento::where('company_id', $empresaId)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->whereBetween('data_hora', [
                $inicio->copy()->startOfDay(),
                $fim->copy()->endOfDay(),
            ])
            ->with('profissional')
            ->selectRaw('profissional_id, SUM(valor) as total')
            ->groupBy('profissional_id')
            ->orderByDesc('total')
            ->get();

        return $porProf->map(function ($row) use ($receitaTotal) {
            $total = (float) $row->total;
            $pct = $total / $receitaTotal;
            $comissaoPct = (float) ($row->profissional?->comissao_pct ?? 0);

            return [
                'name' => $row->profissional?->name ?? 'Sem profissional',
                'cor' => '#6366f1',
                'valor' => round($total * $comissaoPct / 100, 2),
                'pct' => $pct,
            ];
        })->values()->all();
    }
}
