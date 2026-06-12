<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Regra\StoreRegraCatalogoRequest;
use App\Http\Requests\Regra\UpdateRegraCatalogoRequest;
use App\Models\RegraCatalogo;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * CRUD do catálogo global de regras de negócio (super_admin).
 */
class AdminRegraController extends Controller
{
    public function index(): View
    {
        $regras = RegraCatalogo::withCount([
            'companyRegras as empresas_usando' => fn ($q) => $q->where('ativo', true),
        ])
            ->orderBy('categoria')
            ->orderBy('nome')
            ->get()
            ->map(fn (RegraCatalogo $regra): array => $this->toJson($regra));

        return view('admin.regras', ['regrasJson' => $regras]);
    }

    public function store(StoreRegraCatalogoRequest $request): JsonResponse
    {
        $regra = RegraCatalogo::create([
            ...$request->validated(),
            'ativo' => $request->boolean('ativo', true),
        ]);

        return response()->json($this->toJson($regra), 201);
    }

    public function update(UpdateRegraCatalogoRequest $request, RegraCatalogo $regra): JsonResponse
    {
        $regra->update([
            ...$request->validated(),
            'ativo' => $request->boolean('ativo', true),
        ]);

        return response()->json($this->toJson($regra->fresh()));
    }

    public function destroy(RegraCatalogo $regra): JsonResponse
    {
        $regra->companyRegras()->delete();
        $regra->delete();

        return response()->json(['success' => true]);
    }

    private function toJson(RegraCatalogo $regra): array
    {
        return [
            'id' => $regra->id,
            'codigo' => $regra->codigo,
            'nome' => $regra->nome,
            'descricao' => $regra->descricao ?? '',
            'categoria' => $regra->categoria,
            'ativo' => $regra->ativo,
            'params_schema' => $regra->params_schema,
            'params_default' => $regra->params_default,
            'empresas_usando' => (int) ($regra->empresas_usando ?? 0),
        ];
    }
}
