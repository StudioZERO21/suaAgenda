<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCargoRequest;
use App\Models\Cargo;
use App\Support\SaDemoData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CargoController extends Controller
{
    public function index(): View
    {
        $companyId = auth()->user()->empresa_id;

        $cargos = Cargo::where('company_id', $companyId)
            ->withCount('profissionais as membros')
            ->orderBy('nome')
            ->get()
            ->map(fn (Cargo $c): array => $this->toJson($c));

        return view('cargos.index', [
            'cargosJson' => $cargos,
            'niveis' => SaDemoData::niveisPermissao(),
            'cores' => SaDemoData::coresCargo(),
        ]);
    }

    public function store(StoreCargoRequest $request): JsonResponse
    {
        $cargo = Cargo::create([
            'company_id' => auth()->user()->empresa_id,
            'nome' => $request->nome,
            'nivel' => $request->nivel,
            'cor' => $request->cor ?? '#6b7280',
            'descricao' => $request->descricao,
            'comissao_pct' => $request->comissao ?? null,
        ]);

        $cargo->loadCount('profissionais as membros');

        return response()->json($this->toJson($cargo), 201);
    }

    public function update(StoreCargoRequest $request, Cargo $cargo): JsonResponse
    {
        abort_if($cargo->company_id !== auth()->user()->empresa_id, 403);

        $cargo->update([
            'nome' => $request->nome,
            'nivel' => $request->nivel,
            'cor' => $request->cor ?? $cargo->cor,
            'descricao' => $request->descricao,
            'comissao_pct' => $request->comissao ?? null,
        ]);

        $cargo->loadCount('profissionais as membros');

        return response()->json($this->toJson($cargo->fresh()));
    }

    public function destroy(Cargo $cargo): Response
    {
        abort_if($cargo->company_id !== auth()->user()->empresa_id, 403);

        $cargo->delete();

        return response()->noContent();
    }

    private function toJson(Cargo $c): array
    {
        return [
            'id' => $c->id,
            'nome' => $c->nome,
            'nivel' => $c->nivel,
            'cor' => $c->cor,
            'descricao' => $c->descricao,
            'comissao' => (float) ($c->comissao_pct ?? 0),
            'membros' => (int) ($c->membros ?? 0),
        ];
    }
}
