<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Profissional;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarioController extends Controller
{
    public function index(Request $request): View
    {
        $empresaId = auth()->user()->empresa_id;

        $semana = $request->input('semana')
            ? Carbon::parse($request->input('semana'))->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        $inicio = $semana->copy()->startOfDay();
        $fim = $semana->copy()->addDays(5)->endOfDay(); // seg–sáb

        $profissionalId = $request->input('profissional_id');

        $query = Agendamento::with(['cliente', 'profissional', 'servico'])
            ->where('company_id', $empresaId)
            ->whereBetween('data_hora', [$inicio, $fim])
            ->whereNotIn('status', [Agendamento::STATUS_CANCELADO]);

        if ($profissionalId) {
            $query->where('profissional_id', $profissionalId);
        }

        $agendamentos = $query->orderBy('data_hora')->get();

        $profissionais = Profissional::where('company_id', $empresaId)
            ->ativo()
            ->orderBy('name')
            ->get();

        $dias = collect(range(0, 5))->map(fn ($i) => $semana->copy()->addDays($i));

        return view('calendario.index', compact(
            'agendamentos',
            'profissionais',
            'profissionalId',
            'dias',
            'semana',
        ));
    }
}
