<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Avaliacao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
}
