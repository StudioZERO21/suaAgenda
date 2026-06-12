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

    public function distribuicao(Request $request): JsonResponse
    {
        $empresa = auth()->user()->empresa_id;
        $profissionalId = $request->input('profissional_id');
        $dias = $request->input('periodo_dias');

        $query = Avaliacao::where('avaliacoes.company_id', $empresa)
            ->join('agendamentos', 'avaliacoes.agendamento_id', '=', 'agendamentos.id')
            ->select('avaliacoes.nota', 'avaliacoes.created_at', 'agendamentos.profissional_id');

        if ($profissionalId !== null) {
            $query->where('agendamentos.profissional_id', $profissionalId);
        }

        if ($dias !== null) {
            $query->where('avaliacoes.created_at', '>=', now()->subDays((int) $dias));
        }

        $avaliacoes = $query->get();
        $total = $avaliacoes->count();

        $distribuicao = collect(range(1, 5))->map(function (int $estrela) use ($avaliacoes, $total): array {
            $count = $avaliacoes->where('nota', $estrela)->count();

            return [
                'estrelas' => $estrela,
                'quantidade' => $count,
                'percentual' => $total > 0 ? round($count / $total * 100, 1) : 0.0,
            ];
        })->values();

        return response()->json([
            'total' => $total,
            'media' => $total > 0 ? round((float) $avaliacoes->avg('nota'), 2) : null,
            'periodo_dias' => $dias !== null ? (int) $dias : null,
            'profissional_id' => $profissionalId,
            'distribuicao' => $distribuicao,
        ]);
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
