<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\HorarioTrabalho;
use App\Models\Profissional;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HorarioTrabalhoController extends Controller
{
    public function show(Profissional $profissional): View
    {
        $this->authorize('update', $profissional);

        $horarios = HorarioTrabalho::where('profissional_id', $profissional->id)
            ->withTrashed()
            ->get()
            ->keyBy('dia_semana');

        return view('profissionais.horarios', compact('profissional', 'horarios'));
    }

    public function update(Request $request, Profissional $profissional): RedirectResponse
    {
        $this->authorize('update', $profissional);

        $dias = $request->input('dias', []);

        for ($dia = 0; $dia <= 6; $dia++) {
            $dadosDia = $dias[$dia] ?? null;
            $ativo = isset($dadosDia['ativo']);

            if ($ativo && isset($dadosDia['hora_inicio'], $dadosDia['hora_fim'])) {
                HorarioTrabalho::withTrashed()->updateOrCreate(
                    ['profissional_id' => $profissional->id, 'dia_semana' => $dia],
                    [
                        'empresa_id' => $profissional->company_id,
                        'hora_inicio' => $dadosDia['hora_inicio'],
                        'hora_fim' => $dadosDia['hora_fim'],
                        'ativo' => true,
                        'deleted_at' => null,
                    ]
                );
            } else {
                HorarioTrabalho::where('profissional_id', $profissional->id)
                    ->where('dia_semana', $dia)
                    ->update(['ativo' => false]);
            }
        }

        return redirect()->route('profissionais.horarios', $profissional)
            ->with('success', 'Horários atualizados com sucesso.');
    }
}
