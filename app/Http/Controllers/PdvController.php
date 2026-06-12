<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
}
