<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreProdutoRequest;
use App\Http\Requests\UpdateProdutoRequest;
use App\Models\Produto;
use App\Models\ProdutoImagem;
use App\Models\VendaItem;
use App\Support\SaDemoData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function estoqueBaixo(): JsonResponse
    {
        $companyId = auth()->user()->empresa_id;

        $produtos = Produto::where('company_id', $companyId)
            ->where('ativo', true)
            ->where(function ($q): void {
                $q->whereColumn('estoque', '<=', 'estoque_min')
                    ->orWhere('estoque', '<=', 0);
            })
            ->orderBy('estoque')
            ->get(['id', 'nome', 'sku', 'categoria', 'estoque', 'estoque_min', 'unidade'])
            ->map(fn (Produto $p) => [
                'id' => $p->id,
                'nome' => $p->nome,
                'sku' => $p->sku ?? '',
                'categoria' => $p->categoria ?? '',
                'estoque' => $p->estoque,
                'estoque_min' => $p->estoque_min,
                'unidade' => $p->unidade ?? 'un.',
                'status' => $p->estoqueStatus(),
            ]);

        return response()->json($produtos);
    }

    public function resumoEstoque(): JsonResponse
    {
        $companyId = auth()->user()->empresa_id;

        $produtos = Produto::where('company_id', $companyId)
            ->where('ativo', true)
            ->get(['nome', 'categoria', 'estoque', 'estoque_min', 'preco', 'custo']);

        $semEstoque = $produtos->filter(fn (Produto $p) => $p->estoque <= 0);
        $estoqueBaixo = $produtos->filter(fn (Produto $p) => $p->estoque > 0 && $p->estoque <= $p->estoque_min);

        $valorVenda = $produtos->sum(fn (Produto $p) => (float) $p->preco * $p->estoque);
        $valorCusto = $produtos->sum(fn (Produto $p) => (float) $p->custo * $p->estoque);

        $porCategoria = $produtos->groupBy('categoria')->map(function ($items, string $categoria): array {
            return [
                'categoria' => $categoria ?: 'Sem categoria',
                'total_produtos' => $items->count(),
                'valor_estoque' => round((float) $items->sum(fn (Produto $p) => (float) $p->preco * $p->estoque), 2),
            ];
        })->sortByDesc('valor_estoque')->values();

        return response()->json([
            'total_produtos' => $produtos->count(),
            'sem_estoque' => $semEstoque->count(),
            'estoque_baixo' => $estoqueBaixo->count(),
            'ok' => $produtos->count() - $semEstoque->count() - $estoqueBaixo->count(),
            'valor_total_venda' => round($valorVenda, 2),
            'valor_total_custo' => round($valorCusto, 2),
            'margem_bruta' => $valorCusto > 0 ? round(($valorVenda - $valorCusto) / $valorVenda * 100, 1) : null,
            'por_categoria' => $porCategoria,
        ]);
    }

    public function exportarCsv(): StreamedResponse
    {
        $companyId = auth()->user()->empresa_id;

        $produtos = Produto::where('company_id', $companyId)
            ->orderBy('nome')
            ->get();

        return response()->streamDownload(function () use ($produtos): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nome', 'SKU', 'Categoria', 'Preço (R$)', 'Custo (R$)', 'Estoque', 'Est. Mínimo', 'Unidade', 'Status'], ';');

            foreach ($produtos as $p) {
                fputcsv($out, [
                    $p->nome,
                    $p->sku ?? '',
                    $p->categoria ?? '',
                    number_format((float) $p->preco, 2, ',', '.'),
                    number_format((float) ($p->custo ?? 0), 2, ',', '.'),
                    $p->estoque,
                    $p->estoque_min,
                    $p->unidade ?? 'un.',
                    $p->ativo ? 'Ativo' : 'Inativo',
                ], ';');
            }

            fclose($out);
        }, 'produtos-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function categorias(): JsonResponse
    {
        $companyId = auth()->user()->empresa_id;

        $categorias = Produto::where('company_id', $companyId)
            ->whereNotNull('categoria')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria');

        return response()->json($categorias->values());
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

    public function preco(Request $request, Produto $produto): JsonResponse
    {
        abort_if($produto->company_id !== auth()->user()->empresa_id, 403);

        $request->validate([
            'preco' => ['required', 'numeric', 'min:0'],
        ]);

        $produto->update(['preco' => $request->input('preco')]);

        return response()->json([
            'preco' => (float) $produto->preco,
            'updated_at' => $produto->updated_at->toIso8601String(),
        ]);
    }

    public function estoque(Request $request, Produto $produto): JsonResponse
    {
        abort_if($produto->company_id !== auth()->user()->empresa_id, 403);

        $request->validate([
            'estoque' => ['required', 'integer', 'min:0'],
        ]);

        $produto->update(['estoque' => $request->integer('estoque')]);

        return response()->json([
            'estoque' => $produto->estoque,
            'status' => $produto->estoqueStatus(),
            'updated_at' => $produto->updated_at->toIso8601String(),
        ]);
    }

    public function categoria(Request $request, Produto $produto): JsonResponse
    {
        abort_if($produto->company_id !== auth()->user()->empresa_id, 403);

        $request->validate(['categoria' => ['required', 'string', 'max:60']]);

        $produto->update(['categoria' => $request->input('categoria')]);

        return response()->json([
            'categoria' => $produto->categoria,
            'updated_at' => $produto->updated_at->toIso8601String(),
        ]);
    }

    public function unidade(Request $request, Produto $produto): JsonResponse
    {
        abort_if($produto->company_id !== auth()->user()->empresa_id, 403);

        $request->validate(['unidade' => ['required', 'string', 'max:10']]);

        $produto->update(['unidade' => $request->input('unidade')]);

        return response()->json([
            'unidade' => $produto->unidade,
            'updated_at' => $produto->updated_at->toIso8601String(),
        ]);
    }

    public function sku(Request $request, Produto $produto): JsonResponse
    {
        abort_if($produto->company_id !== auth()->user()->empresa_id, 403);

        $request->validate(['sku' => ['nullable', 'string', 'max:60']]);

        $produto->update(['sku' => $request->input('sku', '')]);

        return response()->json([
            'sku' => $produto->sku ?? '',
            'updated_at' => $produto->updated_at->toIso8601String(),
        ]);
    }

    public function custo(Request $request, Produto $produto): JsonResponse
    {
        abort_if($produto->company_id !== auth()->user()->empresa_id, 403);

        $request->validate(['custo' => ['required', 'numeric', 'min:0']]);

        $produto->update(['custo' => $request->input('custo')]);

        return response()->json([
            'custo' => (float) $produto->custo,
            'updated_at' => $produto->updated_at->toIso8601String(),
        ]);
    }

    public function estoqueMin(Request $request, Produto $produto): JsonResponse
    {
        abort_if($produto->company_id !== auth()->user()->empresa_id, 403);

        $request->validate(['estoque_min' => ['required', 'integer', 'min:0']]);

        $produto->update(['estoque_min' => $request->integer('estoque_min')]);

        return response()->json([
            'estoque_min' => $produto->estoque_min,
            'status' => $produto->estoqueStatus(),
            'updated_at' => $produto->updated_at->toIso8601String(),
        ]);
    }

    public function vendas(Request $request, Produto $produto): JsonResponse
    {
        abort_if($produto->company_id !== auth()->user()->empresa_id, 403);

        $limite = min((int) $request->input('limite', 10), 50);

        $itens = VendaItem::where('produto_id', $produto->id)
            ->with(['venda:id,company_id,created_at,total'])
            ->whereHas('venda', fn ($q) => $q->where('company_id', $produto->company_id))
            ->orderByDesc('created_at')
            ->limit($limite)
            ->get()
            ->map(fn (VendaItem $item) => [
                'venda_id' => $item->venda_id,
                'data' => $item->created_at->toDateString(),
                'qtd' => $item->qtd,
                'preco_unit' => (float) $item->preco_unit,
                'total' => (float) $item->total,
            ]);

        return response()->json([
            'total_vendas' => $itens->count(),
            'items' => $itens->values(),
        ]);
    }

    public function nome(Request $request, Produto $produto): JsonResponse
    {
        abort_if($produto->company_id !== auth()->user()->empresa_id, 403);

        $request->validate([
            'nome' => ['required', 'string', 'max:100'],
        ]);

        $produto->update(['nome' => $request->input('nome')]);

        return response()->json([
            'nome' => $produto->nome,
            'updated_at' => $produto->updated_at->toIso8601String(),
        ]);
    }

    public function descricao(Request $request, Produto $produto): JsonResponse
    {
        abort_if($produto->company_id !== auth()->user()->empresa_id, 403);

        $request->validate([
            'descricao' => ['nullable', 'string', 'max:500'],
        ]);

        $produto->update(['descricao' => $request->input('descricao', '')]);

        return response()->json([
            'descricao' => $produto->descricao ?? '',
            'updated_at' => $produto->updated_at->toIso8601String(),
        ]);
    }

    public function detalhe(Produto $produto): JsonResponse
    {
        abort_if($produto->company_id !== auth()->user()->empresa_id, 403);

        $produto->load('imagens');

        return response()->json($this->toJson($produto));
    }

    public function maisRentaveis(Request $request): JsonResponse
    {
        $companyId = auth()->user()->empresa_id;
        $limite = min((int) $request->input('limite', 10), 50);
        $apenasAtivos = filter_var($request->input('apenas_ativos', true), FILTER_VALIDATE_BOOLEAN);

        $produtos = Produto::where('company_id', $companyId)
            ->when($apenasAtivos, fn ($q) => $q->where('ativo', true))
            ->whereNotNull('custo')
            ->where('custo', '>', 0)
            ->get(['id', 'nome', 'categoria', 'preco', 'custo', 'estoque', 'ativo']);

        $ranked = $produtos->map(function (Produto $p): array {
            $preco = (float) $p->preco;
            $custo = (float) $p->custo;
            $lucro = $preco - $custo;
            $margem = $preco > 0 ? round($lucro / $preco * 100, 1) : 0.0;

            return [
                'id' => $p->id,
                'nome' => $p->nome,
                'categoria' => $p->categoria ?? 'Outros',
                'preco' => $preco,
                'custo' => $custo,
                'lucro_unitario' => round($lucro, 2),
                'margem_pct' => $margem,
                'estoque' => $p->estoque,
                'valor_lucro_estoque' => round($lucro * $p->estoque, 2),
                'ativo' => (bool) $p->ativo,
            ];
        })->sortByDesc('margem_pct')->take($limite)->values();

        return response()->json([
            'total' => $ranked->count(),
            'items' => $ranked,
        ]);
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
