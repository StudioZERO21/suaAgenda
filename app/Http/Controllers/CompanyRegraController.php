<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Regra\UpdateCompanyRegraRequest;
use App\Models\CompanyRegra;
use App\Models\RegraCatalogo;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Regras de negócio da empresa: ativa/desativa e configura parâmetros
 * a partir do catálogo global do sistema.
 */
class CompanyRegraController extends Controller
{
    public function index(): View
    {
        $companyId = auth()->user()->empresa_id;

        $configuradas = CompanyRegra::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('regra_catalogo_id');

        $regras = RegraCatalogo::where('ativo', true)
            ->orderBy('categoria')
            ->orderBy('nome')
            ->get()
            ->map(function (RegraCatalogo $regra) use ($configuradas): array {
                $config = $configuradas->get($regra->id);

                return [
                    'codigo' => $regra->codigo,
                    'nome' => $regra->nome,
                    'descricao' => $regra->descricao ?? '',
                    'categoria' => $regra->categoria,
                    'params_schema' => $regra->params_schema,
                    'ativo' => (bool) ($config?->ativo ?? false),
                    'params' => array_merge(
                        $regra->params_default ?? [],
                        $config?->params ?? [],
                    ),
                ];
            });

        return view('configuracoes.regras', ['regrasJson' => $regras]);
    }

    public function update(UpdateCompanyRegraRequest $request, string $codigo): JsonResponse
    {
        $catalogo = RegraCatalogo::where('codigo', $codigo)
            ->where('ativo', true)
            ->firstOrFail();

        $regra = CompanyRegra::withoutGlobalScopes()
            ->withTrashed()
            ->firstOrNew([
                'company_id' => auth()->user()->empresa_id,
                'regra_catalogo_id' => $catalogo->id,
            ]);

        if ($regra->trashed()) {
            $regra->restore();
        }

        $regra->fill([
            'ativo' => $request->boolean('ativo'),
            'params' => $request->validated('params') ?? [],
        ])->save();

        return response()->json([
            'success' => true,
            'codigo' => $codigo,
            'ativo' => $regra->ativo,
            'params' => array_merge($catalogo->params_default ?? [], $regra->params ?? []),
        ]);
    }
}
