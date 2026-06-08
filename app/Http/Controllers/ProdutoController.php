<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreProdutoRequest;
use App\Http\Requests\UpdateProdutoRequest;
use App\Models\Produto;
use App\Support\SaDemoData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ProdutoController extends Controller
{
    public function index(): View
    {
        $companyId = auth()->user()->empresa_id;

        $produtos = Produto::where('company_id', $companyId)
            ->orderBy('nome')
            ->get()
            ->map(fn (Produto $p): array => $this->toJson($p));

        return view('produtos.index', [
            'produtosJson' => $produtos,
            'categorias' => SaDemoData::categoriasProduto(),
            'unidades' => ['un.', 'ml', 'g', 'L', 'kg', 'caixa', 'par'],
        ]);
    }

    public function store(StoreProdutoRequest $request): JsonResponse
    {
        $produto = Produto::create([
            ...$request->validated(),
            'company_id' => auth()->user()->empresa_id,
            'ativo' => (bool) $request->input('ativo', true),
        ]);

        return response()->json($this->toJson($produto), 201);
    }

    public function update(UpdateProdutoRequest $request, Produto $produto): JsonResponse
    {
        abort_if($produto->company_id !== auth()->user()->empresa_id, 403);

        $produto->update([
            ...$request->validated(),
            'ativo' => (bool) $request->input('ativo', $produto->ativo),
        ]);

        return response()->json($this->toJson($produto->fresh()));
    }

    public function destroy(Produto $produto): Response
    {
        abort_if($produto->company_id !== auth()->user()->empresa_id, 403);

        $produto->delete();

        return response()->noContent();
    }

    public function toggle(Produto $produto): JsonResponse
    {
        abort_if($produto->company_id !== auth()->user()->empresa_id, 403);

        $produto->update(['ativo' => ! $produto->ativo]);

        return response()->json($this->toJson($produto->fresh()));
    }

    private function toJson(Produto $p): array
    {
        return [
            'id' => $p->id,
            'nome' => $p->nome,
            'sku' => $p->sku,
            'categoria' => $p->categoria ?? 'Outros',
            'preco' => (float) $p->preco,
            'custo' => (float) ($p->custo ?? 0),
            'estoque' => $p->estoque,
            'estoque_min' => $p->estoque_min,
            'unidade' => $p->unidade,
            'descricao' => $p->descricao,
            'ativo' => $p->ativo,
        ];
    }
}
