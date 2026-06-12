<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBloqueioRequest;
use App\Http\Requests\UpdateBloqueioRequest;
use App\Models\BloqueioAgenda;
use App\Models\Company;
use App\Models\Profissional;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class BloqueioController extends Controller
{
    public function index(Profissional $profissional): JsonResponse
    {
        $this->authorize('update', $profissional->company);

        $bloqueios = BloqueioAgenda::where('profissional_id', $profissional->id)
            ->orderBy('data_inicio')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'data_inicio' => $b->data_inicio->format('Y-m-d'),
                'data_fim' => $b->data_fim->format('Y-m-d'),
                'motivo' => $b->motivo,
            ]);

        return response()->json($bloqueios);
    }

    public function store(StoreBloqueioRequest $request, Profissional $profissional): JsonResponse
    {
        $this->authorize('update', $profissional->company);

        $data = $request->validated();

        $bloqueio = BloqueioAgenda::create([
            'company_id' => $profissional->company_id,
            'profissional_id' => $profissional->id,
            'data_inicio' => $data['data_inicio'],
            'data_fim' => $data['data_fim'],
            'motivo' => $data['motivo'] ?? null,
        ]);

        return response()->json([
            'id' => $bloqueio->id,
            'data_inicio' => $bloqueio->data_inicio->format('Y-m-d'),
            'data_fim' => $bloqueio->data_fim->format('Y-m-d'),
            'motivo' => $bloqueio->motivo,
        ], 201);
    }

    public function update(UpdateBloqueioRequest $request, BloqueioAgenda $bloqueio): JsonResponse
    {
        $this->authorize('update', Company::findOrFail($bloqueio->company_id));

        $data = $request->validated();

        $bloqueio->update($data);

        return response()->json([
            'id' => $bloqueio->id,
            'data_inicio' => $bloqueio->data_inicio->format('Y-m-d'),
            'data_fim' => $bloqueio->data_fim->format('Y-m-d'),
            'motivo' => $bloqueio->motivo,
        ]);
    }

    public function destroy(BloqueioAgenda $bloqueio): Response
    {
        $this->authorize('update', Company::findOrFail($bloqueio->company_id));
        $bloqueio->delete();

        return response()->noContent();
    }
}
