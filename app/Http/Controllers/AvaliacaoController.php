<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Avaliacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AvaliacaoController extends Controller
{
    public function show(string $token): View
    {
        $ag = Agendamento::with(['servico', 'profissional', 'company', 'avaliacao'])
            ->where('cancel_token', $token)
            ->firstOrFail();

        abort_unless($ag->status === Agendamento::STATUS_FINALIZADO, 404);
        abort_if($ag->avaliacao !== null, 410); // already rated

        return view('public.avaliar', [
            'ag' => $ag,
            'company' => $ag->company,
            'token' => $token,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $ag = Agendamento::with('avaliacao')
            ->where('cancel_token', $token)
            ->firstOrFail();

        abort_unless($ag->status === Agendamento::STATUS_FINALIZADO, 422);
        abort_if($ag->avaliacao !== null, 422);

        $request->validate([
            'nota' => ['required', 'integer', 'min:1', 'max:5'],
            'comentario' => ['nullable', 'string', 'max:500'],
        ]);

        Avaliacao::create([
            'company_id' => $ag->company_id,
            'agendamento_id' => $ag->id,
            'nota' => $request->integer('nota'),
            'comentario' => $request->input('comentario'),
        ]);

        return redirect()->route('agendamento.meu', $token)
            ->with('avaliado', true);
    }

    public function destroy(Avaliacao $avaliacao): Response
    {
        $agendamento = $avaliacao->agendamento;
        abort_if($agendamento === null || $agendamento->company_id !== auth()->user()->empresa_id, 403);
        abort_if(! auth()->user()->hasAnyRole(['admin_empresa', 'gestor']), 403);

        $avaliacao->delete();

        return response()->noContent();
    }

    public function update(Request $request, Avaliacao $avaliacao): JsonResponse
    {
        $agendamento = $avaliacao->agendamento;
        abort_if($agendamento === null || $agendamento->company_id !== auth()->user()->empresa_id, 403);
        abort_if(! auth()->user()->hasAnyRole(['admin_empresa', 'gestor']), 403);

        $request->validate([
            'nota' => ['required', 'integer', 'min:1', 'max:5'],
            'comentario' => ['nullable', 'string', 'max:1000'],
        ]);

        $avaliacao->update([
            'nota' => $request->integer('nota'),
            'comentario' => $request->input('comentario'),
        ]);

        return response()->json([
            'id' => $avaliacao->id,
            'nota' => $avaliacao->nota,
            'comentario' => $avaliacao->comentario ?? '',
            'estrelas' => $avaliacao->estrelas(),
            'updated_at' => $avaliacao->updated_at->toIso8601String(),
        ]);
    }
}
