<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreProdutoRequest;
use App\Http\Requests\UpdateProdutoRequest;
use App\Models\Produto;
use App\Models\ProdutoImagem;
use App\Support\SaDemoData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProdutoController extends Controller
{
    public function index(): View
    {
        $companyId = auth()->user()->empresa_id;

        $produtos = Produto::where('company_id', $companyId)
            ->with('imagens')
            ->orderBy('nome')
            ->get()
            ->map(fn (Produto $p): array => $this->toJson($p));

        return view('produtos.index', [
            'produtosJson' => $produtos,
            'categorias' => SaDemoData::categoriasProduto(),
            'unidades' => ['un.', 'ml', 'g', 'L', 'kg', 'caixa', 'par'],
        ]);
    }

    public function buscar(Request $request): JsonResponse
    {
        $companyId = auth()->user()->empresa_id;
        $q = trim((string) $request->input('q', ''));

        $query = Produto::where('company_id', $companyId)->where('ativo', true)->orderBy('nome');

        if ($q !== '') {
            $query->where(function ($qb) use ($q): void {
                $qb->where('nome', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('categoria', 'like', "%{$q}%");
            });
        }

        $produtos = $query->limit(15)
            ->get(['id', 'nome', 'sku', 'categoria', 'preco', 'estoque', 'unidade'])
            ->map(fn (Produto $p) => [
                'id' => $p->id,
                'nome' => $p->nome,
                'sku' => $p->sku ?? '',
                'categoria' => $p->categoria ?? '',
                'preco' => (float) $p->preco,
                'estoque' => $p->estoque,
                'unidade' => $p->unidade ?? 'un.',
            ]);

        return response()->json($produtos);
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

        return response()->json($this->toJson($produto->fresh()->load('imagens')));
    }

    public function destroy(Produto $produto): Response
    {
        abort_if($produto->company_id !== auth()->user()->empresa_id, 403);

        // Delete associated images from storage
        foreach ($produto->imagens as $imagem) {
            Storage::disk('public')->delete($imagem->imagem_path);
        }

        $produto->delete();

        return response()->noContent();
    }

    public function toggle(Produto $produto): JsonResponse
    {
        abort_if($produto->company_id !== auth()->user()->empresa_id, 403);

        $produto->update(['ativo' => ! $produto->ativo]);

        return response()->json($this->toJson($produto->fresh()->load('imagens')));
    }

    public function storeImagem(Request $request, Produto $produto): JsonResponse
    {
        abort_if($produto->company_id !== auth()->user()->empresa_id, 403);

        $request->validate([
            'imagem' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $companyId = auth()->user()->empresa_id;
        $path = $request->file('imagem')->store("produto_imagens/{$companyId}", 'public');

        $isFirstImage = $produto->imagens()->count() === 0;

        $imagem = $produto->imagens()->create([
            'imagem_path' => $path,
            'is_capa' => $isFirstImage,
            'ordem' => $produto->imagens()->max('ordem') + 1,
        ]);

        return response()->json([
            'id' => $imagem->id,
            'url' => Storage::disk('public')->url($imagem->imagem_path),
            'is_capa' => $imagem->is_capa,
        ], 201);
    }

    public function destroyImagem(ProdutoImagem $imagem): Response
    {
        $produto = $imagem->produto;
        abort_if($produto->company_id !== auth()->user()->empresa_id, 403);

        Storage::disk('public')->delete($imagem->imagem_path);

        $wasCapa = $imagem->is_capa;
        $imagem->delete();

        // If deleted image was the cover, promote the first remaining image
        if ($wasCapa) {
            $first = $produto->imagens()->orderBy('ordem')->first();
            $first?->update(['is_capa' => true]);
        }

        return response()->noContent();
    }

    public function setCapa(ProdutoImagem $imagem): JsonResponse
    {
        $produto = $imagem->produto;
        abort_if($produto->company_id !== auth()->user()->empresa_id, 403);

        $produto->imagens()->update(['is_capa' => false]);
        $imagem->update(['is_capa' => true]);

        return response()->json(['id' => $imagem->id, 'is_capa' => true]);
    }

    private function toJson(Produto $p): array
    {
        $imagens = $p->relationLoaded('imagens') ? $p->imagens : collect();

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
            'imagens' => $imagens->map(fn (ProdutoImagem $img): array => [
                'id' => $img->id,
                'url' => Storage::disk('public')->url($img->imagem_path),
                'is_capa' => $img->is_capa,
                'ordem' => $img->ordem,
            ])->values()->all(),
        ];
    }
}
