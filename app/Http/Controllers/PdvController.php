<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Lancamento;
use App\Models\Produto;
use App\Models\Servico;
use App\Models\Venda;
use App\Models\VendaItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdvController extends Controller
{
    public function index(): View
    {
        $companyId = auth()->user()->empresa_id;

        $produtosJs = Produto::where('company_id', $companyId)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get()
            ->map(fn (Produto $p): array => [
                'key' => 'prd-'.$p->id,
                'id' => $p->id,
                'name' => $p->nome,
                'price' => (float) $p->preco,
                'stock' => $p->estoque,
                'type' => 'product',
            ])
            ->values()
            ->all();

        $servicosJs = Servico::where('company_id', $companyId)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get()
            ->map(fn (Servico $s): array => [
                'key' => 'svc-'.$s->id,
                'id' => $s->id,
                'name' => $s->nome,
                'price' => (float) $s->preco,
                'duration' => $s->duracao_minutos,
                'color' => $s->cor ?? '#6366f1',
                'type' => 'service',
                'stock' => null,
            ])
            ->all();

        $clientes = Cliente::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pdv.index', compact('produtosJs', 'servicosJs', 'clientes'));
    }

    public function resumo(Request $request): JsonResponse
    {
        $companyId = auth()->user()->empresa_id;
        $periodo = $request->input('periodo', 'hoje');

        $hoje = Carbon::today();

        [$inicio, $fim] = match ($periodo) {
            'semana' => [$hoje->copy()->startOfWeek(), $hoje->copy()->endOfWeek()],
            'mes' => [$hoje->copy()->startOfMonth(), $hoje->copy()->endOfMonth()],
            default => [$hoje->copy()->startOfDay(), $hoje->copy()->endOfDay()],
        };

        $vendas = Venda::where('company_id', $companyId)
            ->whereBetween('created_at', [$inicio, $fim])
            ->withCount('itens')
            ->get();

        $totalVendas = $vendas->count();
        $receita = (float) $vendas->sum('total');
        $desconto = (float) $vendas->sum('desconto');
        $totalItens = (int) $vendas->sum('itens_count');
        $ticketMedio = $totalVendas > 0 ? round($receita / $totalVendas, 2) : 0.0;

        return response()->json([
            'periodo' => $periodo,
            'total_vendas' => $totalVendas,
            'receita_total' => $receita,
            'desconto_total' => $desconto,
            'total_itens' => $totalItens,
            'ticket_medio' => $ticketMedio,
        ]);
    }

    public function exportarCsv(): StreamedResponse
    {
        $companyId = auth()->user()->empresa_id;

        $vendas = Venda::where('company_id', $companyId)
            ->with(['cliente:id,name', 'itens'])
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($vendas): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Data', 'Cliente', 'Itens', 'Subtotal', 'Desconto', 'Total', 'Método Pagamento'], ';');

            foreach ($vendas as $venda) {
                $itens = $venda->itens->map(fn ($i) => "{$i->descricao} (x{$i->qtd})")->join(', ');
                fputcsv($out, [
                    $venda->created_at->format('d/m/Y H:i'),
                    $venda->cliente?->name ?? '—',
                    $itens,
                    number_format((float) $venda->subtotal, 2, '.', ''),
                    number_format((float) $venda->desconto, 2, '.', ''),
                    number_format((float) $venda->total, 2, '.', ''),
                    $venda->metodo_pagamento ?? '—',
                ], ';');
            }

            fclose($out);
        }, 'vendas-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = auth()->user()->empresa_id;

        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'string'],
            'items.*.type' => ['required', 'in:product,service'],
            'items.*.name' => ['required', 'string'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'desconto' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'metodo_pagamento' => ['required', 'string'],
        ]);

        DB::transaction(function () use ($request, $companyId) {
            $venda = Venda::create([
                'company_id' => $companyId,
                'cliente_id' => $request->input('cliente_id') ?: null,
                'subtotal' => $request->subtotal,
                'desconto' => $request->desconto,
                'total' => $request->total,
                'metodo_pagamento' => $request->metodo_pagamento,
            ]);

            foreach ($request->items as $item) {
                VendaItem::create([
                    'venda_id' => $venda->id,
                    'produto_id' => $item['type'] === 'product' ? $item['id'] : null,
                    'servico_id' => $item['type'] === 'service' ? $item['id'] : null,
                    'descricao' => $item['name'],
                    'qtd' => $item['qty'],
                    'preco_unit' => $item['price'],
                    'total' => $item['price'] * $item['qty'],
                ]);

                // Decrease stock for products
                if ($item['type'] === 'product') {
                    Produto::where('id', $item['id'])
                        ->where('company_id', $companyId)
                        ->decrement('estoque', $item['qty']);
                }
            }

            // Auto-create lancamento for the sale
            Lancamento::create([
                'company_id' => $companyId,
                'venda_id' => $venda->id,
                'tipo' => 'receita',
                'descricao' => 'Venda PDV #'.$venda->id,
                'categoria' => 'venda',
                'valor' => $request->total,
                'data' => now()->toDateString(),
                'status' => 'pago',
                'metodo_pagamento' => $request->metodo_pagamento,
            ]);
        });

        return response()->json(['ok' => true], 201);
    }

    public function vendaDetalhe(Venda $venda): JsonResponse
    {
        abort_if($venda->company_id !== auth()->user()->empresa_id, 403);

        $venda->load(['cliente:id,name,phone', 'profissional:id,name', 'itens.produto:id,nome,sku', 'itens.servico:id,nome']);

        return response()->json([
            'id' => $venda->id,
            'created_at' => $venda->created_at->toIso8601String(),
            'subtotal' => (float) $venda->subtotal,
            'desconto' => (float) $venda->desconto,
            'total' => (float) $venda->total,
            'metodo_pagamento' => $venda->metodo_pagamento ?? '',
            'observacao' => $venda->observacao ?? '',
            'cliente' => $venda->cliente ? [
                'id' => $venda->cliente->id,
                'name' => $venda->cliente->name,
                'phone' => $venda->cliente->phone ?? '',
            ] : null,
            'profissional' => $venda->profissional ? [
                'id' => $venda->profissional->id,
                'name' => $venda->profissional->name,
            ] : null,
            'itens' => $venda->itens->map(fn (VendaItem $item) => [
                'id' => $item->id,
                'descricao' => $item->descricao,
                'qtd' => (int) $item->qtd,
                'preco_unit' => (float) $item->preco_unit,
                'total' => (float) $item->total,
                'produto_nome' => $item->produto?->nome ?? '',
                'produto_sku' => $item->produto?->sku ?? '',
                'servico_nome' => $item->servico?->nome ?? '',
            ])->values(),
        ]);
    }

    public function listarVendas(Request $request): JsonResponse
    {
        $companyId = auth()->user()->empresa_id;
        $limite = min((int) $request->input('limite', 20), 100);
        $periodo = $request->input('periodo', 'mes');
        $hoje = Carbon::today();

        [$inicio, $fim] = match ($periodo) {
            'hoje' => [$hoje->copy()->startOfDay(), $hoje->copy()->endOfDay()],
            'semana' => [$hoje->copy()->startOfWeek(), $hoje->copy()->endOfWeek()],
            default => [$hoje->copy()->startOfMonth(), $hoje->copy()->endOfMonth()],
        };

        $vendas = Venda::where('company_id', $companyId)
            ->whereBetween('created_at', [$inicio, $fim])
            ->with(['cliente:id,name', 'itens'])
            ->latest()
            ->limit($limite)
            ->get()
            ->map(fn (Venda $v): array => [
                'id' => $v->id,
                'created_at' => $v->created_at->toIso8601String(),
                'cliente_nome' => $v->cliente?->name ?? 'Avulso',
                'total' => (float) $v->total,
                'desconto' => (float) $v->desconto,
                'metodo_pagamento' => $v->metodo_pagamento ?? '',
                'total_itens' => $v->itens->count(),
            ]);

        return response()->json([
            'periodo' => $periodo,
            'total' => $vendas->count(),
            'receita_total' => (float) $vendas->sum('total'),
            'items' => $vendas->values(),
        ]);
    }

    public function observacaoVenda(Request $request, Venda $venda): JsonResponse
    {
        abort_if($venda->company_id !== auth()->user()->empresa_id, 403);

        $request->validate(['observacao' => ['nullable', 'string', 'max:500']]);

        $venda->update(['observacao' => $request->input('observacao')]);

        return response()->json([
            'observacao' => $venda->observacao ?? '',
            'updated_at' => $venda->updated_at->toIso8601String(),
        ]);
    }

    public function destroyVenda(Venda $venda): Response
    {
        abort_if($venda->company_id !== auth()->user()->empresa_id, 403);
        abort_if(! auth()->user()->hasAnyRole(['admin_empresa', 'gestor']), 403);

        DB::transaction(function () use ($venda): void {
            foreach ($venda->itens as $item) {
                if ($item->produto_id !== null) {
                    Produto::where('id', $item->produto_id)
                        ->where('company_id', $venda->company_id)
                        ->increment('estoque', $item->qtd);
                }
            }

            $venda->lancamentos()->delete();
            $venda->delete();
        });

        return response()->noContent();
    }

    public function buscarProdutos(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        $companyId = auth()->user()->empresa_id;

        $query = Produto::where('company_id', $companyId)
            ->where('ativo', true)
            ->where('estoque', '>', 0);

        if ($q !== '') {
            $query->where(function ($sub) use ($q): void {
                $sub->where('nome', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('categoria', 'like', "%{$q}%");
            });
        }

        $produtos = $query->orderBy('nome')
            ->limit(15)
            ->get(['id', 'nome', 'sku', 'categoria', 'preco', 'estoque', 'unidade'])
            ->map(fn (Produto $p) => [
                'id' => $p->id,
                'nome' => $p->nome,
                'sku' => $p->sku ?? '',
                'categoria' => $p->categoria ?? '',
                'preco' => (float) $p->preco,
                'estoque' => (int) $p->estoque,
                'unidade' => $p->unidade ?? 'un',
            ]);

        return response()->json($produtos);
    }

    public function maisVendidos(Request $request): JsonResponse
    {
        $empresa = auth()->user()->empresa_id;
        $limite = min((int) $request->input('limite', 10), 50);
        $dias = $request->input('dias');

        $itens = VendaItem::whereHas('venda', function ($q) use ($empresa, $dias): void {
            $q->where('company_id', $empresa)
                ->when($dias !== null, fn ($q) => $q->where('created_at', '>=', now()->subDays((int) $dias)));
        })
            ->with('produto:id,nome,sku,categoria,preco,unidade')
            ->get(['produto_id', 'qtd', 'preco_unit'])
            ->groupBy('produto_id')
            ->map(function ($items) {
                $produto = $items->first()->produto;

                return [
                    'produto_id' => $produto?->id ?? '',
                    'produto_nome' => $produto?->nome ?? 'Produto removido',
                    'sku' => $produto?->sku ?? '',
                    'categoria' => $produto?->categoria ?? '',
                    'total_vendido' => (int) $items->sum('qtd'),
                    'receita_total' => (float) $items->sum(fn ($i) => $i->qtd * $i->preco_unit),
                ];
            })
            ->sortByDesc('total_vendido')
            ->take($limite)
            ->values();

        return response()->json(['total_produtos' => $itens->count(), 'items' => $itens]);
    }

    public function vendasPorDia(Request $request): JsonResponse
    {
        $empresa = auth()->user()->empresa_id;

        $mes = (int) $request->input('mes', now()->month);
        $ano = (int) $request->input('ano', now()->year);

        $inicio = Carbon::createFromDate($ano, $mes, 1)->startOfMonth();
        $fim = $inicio->copy()->endOfMonth();
        $diasNoMes = (int) $inicio->daysInMonth;

        $vendas = Venda::where('company_id', $empresa)
            ->whereBetween('created_at', [$inicio->startOfDay(), $fim->copy()->endOfDay()])
            ->get(['created_at', 'total']);

        $serie = collect(range(1, $diasNoMes))->map(function (int $dia) use ($inicio, $vendas): array {
            $data = $inicio->copy()->setDay($dia);
            $dosDia = $vendas->filter(fn (Venda $v) => Carbon::parse($v->created_at)->isSameDay($data));

            return [
                'data' => $data->format('Y-m-d'),
                'total_vendas' => $dosDia->count(),
                'receita' => (float) $dosDia->sum('total'),
            ];
        });

        return response()->json([
            'mes' => $mes,
            'ano' => $ano,
            'total_mes' => $vendas->count(),
            'receita_mes' => (float) $vendas->sum('total'),
            'dias' => $serie,
        ]);
    }

    public function categoriasReceita(Request $request): JsonResponse
    {
        $empresa = auth()->user()->empresa_id;
        $dias = $request->input('dias');

        $itens = VendaItem::whereHas('venda', function ($q) use ($empresa, $dias): void {
            $q->where('company_id', $empresa)
                ->when($dias !== null, fn ($q) => $q->where('created_at', '>=', now()->subDays((int) $dias)));
        })
            ->with('produto:id,categoria')
            ->get(['produto_id', 'qtd', 'preco_unit'])
            ->filter(fn ($i) => $i->produto !== null)
            ->groupBy(fn ($i) => $i->produto->categoria ?? 'sem categoria')
            ->map(function ($items, $categoria): array {
                return [
                    'categoria' => $categoria,
                    'total_itens' => (int) $items->sum('qtd'),
                    'receita' => (float) $items->sum(fn ($i) => $i->qtd * $i->preco_unit),
                ];
            })
            ->sortByDesc('receita')
            ->values();

        return response()->json([
            'periodo_dias' => $dias !== null ? (int) $dias : null,
            'total_categorias' => $itens->count(),
            'items' => $itens,
        ]);
    }

    public function ticketMedio(Request $request): JsonResponse
    {
        $empresa = auth()->user()->empresa_id;
        $dias = max(1, min(365, (int) $request->input('dias', 30)));

        $vendas = Venda::where('company_id', $empresa)
            ->where('created_at', '>=', now()->subDays($dias)->startOfDay())
            ->get(['total', 'created_at']);

        $total = $vendas->count();
        $soma = (float) $vendas->sum('total');
        $ticketMedio = $total > 0 ? round($soma / $total, 2) : null;
        $ticketMin = $total > 0 ? (float) $vendas->min('total') : null;
        $ticketMax = $total > 0 ? (float) $vendas->max('total') : null;

        $porDiaSemana = collect(range(0, 6))->map(function (int $dia) use ($vendas): array {
            $label = Carbon::now()->startOfWeek()->addDays($dia)->translatedFormat('D');
            $deste = $vendas->filter(fn (Venda $v) => $v->created_at->dayOfWeek === $dia);

            return [
                'dia_semana' => $dia,
                'dia_nome' => $label,
                'total_vendas' => $deste->count(),
                'ticket_medio' => $deste->count() > 0 ? round((float) $deste->avg('total'), 2) : null,
            ];
        });

        return response()->json([
            'periodo_dias' => $dias,
            'total_vendas' => $total,
            'valor_total' => round($soma, 2),
            'ticket_medio' => $ticketMedio,
            'ticket_min' => $ticketMin,
            'ticket_max' => $ticketMax,
            'por_dia_semana' => $porDiaSemana->values(),
        ]);
    }

    public function vendasPorHora(Request $request): JsonResponse
    {
        $empresa = auth()->user()->empresa_id;
        $dias = max(1, min(365, (int) $request->input('dias', 30)));

        $vendas = Venda::where('company_id', $empresa)
            ->where('created_at', '>=', now()->subDays($dias)->startOfDay())
            ->get(['total', 'created_at']);

        $porHora = $vendas->groupBy(fn (Venda $v) => (int) $v->created_at->format('G'));

        $horas = collect(range(0, 23))->map(function (int $hora) use ($porHora): array {
            $items = $porHora->get($hora, collect());
            $count = $items->count();
            $soma = (float) $items->sum('total');

            return [
                'hora' => $hora,
                'hora_fmt' => sprintf('%02d:00', $hora),
                'total_vendas' => $count,
                'valor_total' => round($soma, 2),
                'ticket_medio' => $count > 0 ? round($soma / $count, 2) : null,
            ];
        });

        $horaPico = $horas->sortByDesc('total_vendas')->first();

        return response()->json([
            'periodo_dias' => $dias,
            'total_vendas' => $vendas->count(),
            'valor_total' => round((float) $vendas->sum('total'), 2),
            'horas' => $horas->values(),
            'hora_pico' => $horaPico['total_vendas'] > 0 ? $horaPico : null,
        ]);
    }

    public function evolucaoMensal(Request $request): JsonResponse
    {
        $empresa = auth()->user()->empresa_id;
        $meses = max(2, min(24, (int) $request->input('meses', 6)));

        $serie = collect(range($meses - 1, 0))->map(function (int $offset): Carbon {
            return now()->startOfMonth()->subMonths($offset);
        })->map(function (Carbon $inicio) use ($empresa): array {
            $fim = $inicio->copy()->endOfMonth();

            $vendas = Venda::where('company_id', $empresa)
                ->whereBetween('created_at', [$inicio->startOfDay(), $fim->endOfDay()])
                ->get(['total', 'created_at']);

            $totalVendas = $vendas->count();
            $valorTotal = round((float) $vendas->sum('total'), 2);

            return [
                'mes' => $inicio->month,
                'ano' => $inicio->year,
                'mes_fmt' => $inicio->translatedFormat('M/y'),
                'total_vendas' => $totalVendas,
                'valor_total' => $valorTotal,
                'ticket_medio' => $totalVendas > 0 ? round($valorTotal / $totalVendas, 2) : null,
            ];
        });

        return response()->json([
            'meses' => $meses,
            'serie' => $serie->values(),
        ]);
    }

    public function clientesSemCompra(Request $request): JsonResponse
    {
        $empresa = auth()->user()->empresa_id;
        $dias = max(1, min(180, (int) $request->input('dias', 30)));
        $limite = min((int) $request->input('limite', 20), 100);

        $desde = now()->subDays($dias)->startOfDay();

        $comAgendamento = Agendamento::where('company_id', $empresa)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->where('data_hora', '>=', $desde)
            ->whereNotNull('cliente_id')
            ->pluck('cliente_id')
            ->unique();

        $comCompra = Venda::where('company_id', $empresa)
            ->where('created_at', '>=', $desde)
            ->whereNotNull('cliente_id')
            ->pluck('cliente_id')
            ->unique();

        $semCompraIds = $comAgendamento->diff($comCompra);

        $agPorCliente = Agendamento::where('company_id', $empresa)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->where('data_hora', '>=', $desde)
            ->whereIn('cliente_id', $semCompraIds)
            ->selectRaw('cliente_id, COUNT(*) as visitas, SUM(valor) as receita_servicos, MAX(data_hora) as ultima_visita')
            ->groupBy('cliente_id')
            ->orderByDesc('visitas')
            ->limit($limite)
            ->get();

        $clientes = Cliente::whereIn('id', $agPorCliente->pluck('cliente_id'))
            ->get(['id', 'name', 'phone'])
            ->keyBy('id');

        $items = $agPorCliente->map(fn ($row) => [
            'cliente_id' => $row->cliente_id,
            'nome' => $clientes->get($row->cliente_id)?->name ?? '',
            'phone' => $clientes->get($row->cliente_id)?->phone ?? '',
            'visitas' => (int) $row->visitas,
            'receita_servicos' => round((float) $row->receita_servicos, 2),
            'ultima_visita' => $row->ultima_visita,
        ])->values();

        return response()->json([
            'periodo_dias' => $dias,
            'com_agendamento' => $comAgendamento->count(),
            'sem_compra' => $semCompraIds->count(),
            'items' => $items,
        ]);
    }
}
