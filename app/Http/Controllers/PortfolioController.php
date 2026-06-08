<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PortfolioItem;
use App\Models\Profissional;
use App\Support\SaDemoData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    private const COLORS = ['#1a1a1a', '#d4a574', '#6366f1', '#f59e0b', '#10b981', '#ef4444'];

    public function index(): View
    {
        $companyId = auth()->user()->empresa_id;

        $profissionais = Profissional::where('company_id', $companyId)
            ->where('ativo', true)
            ->orderBy('name')
            ->get()
            ->values()
            ->map(fn (Profissional $p, int $i): array => [
                'id' => $p->id,
                'nome' => $p->name,
                'cor' => self::COLORS[$i % count(self::COLORS)],
            ]);

        $colorMap = $profissionais->pluck('cor', 'id')->all();

        $fotos = PortfolioItem::where('company_id', $companyId)
            ->with('profissional')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PortfolioItem $item): array => $this->itemToJson($item, $colorMap));

        return view('portfolio.index', [
            'fotosJson' => $fotos->values()->all(),
            'categorias' => SaDemoData::categoriasPortfolio(),
            'profissionais' => $profissionais->values()->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = auth()->user()->empresa_id;

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'categoria' => ['required', 'string', 'max:100'],
            'profissional_id' => ['nullable', 'uuid', Rule::exists('profissionais', 'id')->where('company_id', $companyId)],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ]);

        $item = PortfolioItem::create([
            'company_id' => $companyId,
            'profissional_id' => $data['profissional_id'] ?? null,
            'titulo' => $data['titulo'],
            'categoria' => $data['categoria'],
            'destaque' => false,
            'tags' => $data['tags'] ?? [],
        ]);

        $item->load('profissional');

        return response()->json($this->itemToJson($item), 201);
    }

    public function destroy(PortfolioItem $portfolioItem): Response
    {
        abort_if($portfolioItem->company_id !== auth()->user()->empresa_id, 403);
        $portfolioItem->delete();

        return response()->noContent();
    }

    public function toggleFeatured(PortfolioItem $portfolioItem): JsonResponse
    {
        abort_if($portfolioItem->company_id !== auth()->user()->empresa_id, 403);
        $portfolioItem->update(['destaque' => ! $portfolioItem->destaque]);

        return response()->json(['id' => $portfolioItem->id, 'destaque' => $portfolioItem->destaque]);
    }

    /** @param array<string,string> $colorMap */
    private function itemToJson(PortfolioItem $item, array $colorMap = []): array
    {
        return [
            'id' => $item->id,
            'prof_id' => $item->profissional_id,
            'prof' => $item->profissional?->name ?? '—',
            'categoria' => $item->categoria,
            'titulo' => $item->titulo,
            'data' => $item->created_at->format('Y-m-d'),
            'destaque' => $item->destaque,
            'cor' => $colorMap[$item->profissional_id] ?? '#888',
            'tags' => $item->tags ?? [],
        ];
    }
}
