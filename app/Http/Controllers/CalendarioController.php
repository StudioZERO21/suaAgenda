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
    /** @var array<int, string> */
    private const PROF_COLORS = [
        '#1a1a1a', '#d4a574', '#6366f1', '#10b981', '#f59e0b', '#ec4899',
    ];

    public function index(Request $request): View
    {
        $empresaId = auth()->user()->empresa_id;

        $viewMode = in_array($request->input('view'), ['day', 'week', 'month', 'kanban'], true)
            ? $request->input('view')
            : 'week';

        $refInput = $request->input('ref') ?? $request->input('semana');
        $ref = $refInput
            ? Carbon::parse($refInput)
            : Carbon::now();

        // UUID — sem cast para int
        $profissionalId = $request->filled('profissional_id')
            ? $request->input('profissional_id')
            : null;

        $monthWeeks = [];
        $headerTitle = '';
        $navPrev = '';
        $navNext = '';

        if ($viewMode === 'day') {
            $dias = collect([$ref->copy()]);
            $inicio = $ref->copy()->startOfDay();
            $fim = $ref->copy()->endOfDay();
            $headerTitle = $ref->translatedFormat('l, d \d\e F \d\e Y');
            $navPrev = $ref->copy()->subDay()->format('Y-m-d');
            $navNext = $ref->copy()->addDay()->format('Y-m-d');
        } elseif ($viewMode === 'month') {
            $inicio = $ref->copy()->startOfMonth()->startOfDay();
            $fim = $ref->copy()->endOfMonth()->endOfDay();
            $dias = collect();
            $monthWeeks = $this->buildMonthWeeks($ref);
            $headerTitle = $ref->translatedFormat('F Y');
            $navPrev = $ref->copy()->subMonth()->format('Y-m-d');
            $navNext = $ref->copy()->addMonth()->format('Y-m-d');
        } elseif ($viewMode === 'kanban') {
            $dias = collect([$ref->copy()]);
            $inicio = $ref->copy()->startOfDay();
            $fim = $ref->copy()->endOfDay();
            $headerTitle = $ref->translatedFormat('l, d \d\e F');
            $navPrev = $ref->copy()->subDay()->format('Y-m-d');
            $navNext = $ref->copy()->addDay()->format('Y-m-d');
        } else {
            $semana = $ref->copy()->startOfWeek(Carbon::MONDAY);
            $ref = $semana;
            $dias = collect(range(0, 5))->map(
                fn (int $i) => $semana->copy()->addDays($i)
            );
            $inicio = $semana->copy()->startOfDay();
            $fim = $semana->copy()->addDays(5)->endOfDay();
            $headerTitle = $dias->first()->format('d/m')
                .' – '
                .$dias->last()->format('d/m');
            $navPrev = $semana->copy()->subWeek()->format('Y-m-d');
            $navNext = $semana->copy()->addWeek()->format('Y-m-d');
        }

        $query = Agendamento::with(['cliente', 'profissional', 'servico'])
            ->where('company_id', $empresaId)
            ->whereBetween('data_hora', [$inicio, $fim])
            ->whereNotIn('status', Agendamento::STATUSES_INATIVOS);

        if ($profissionalId) {
            $query->where('profissional_id', $profissionalId);
        }

        $agendamentos = $query->orderBy('data_hora')->get();

        $profissionais = Profissional::where('company_id', $empresaId)
            ->ativo()
            ->orderBy('name')
            ->get()
            ->map(function (Profissional $prof, int $index) {
                $prof->cor = self::PROF_COLORS[$index % count(self::PROF_COLORS)];

                return $prof;
            });

        $profCores = $profissionais->pluck('cor', 'id');

        $agPorDia = $agendamentos->groupBy(
            fn (Agendamento $ag) => $ag->data_hora->format('Y-m-d')
        );

        $companySlug = auth()->user()->company?->slug;

        // ACL para o kanban
        $meuProfissionalId = auth()->user()->profissional_id;
        $isAdmin = auth()->user()->hasAnyRole(['admin_empresa', 'gestor']);

        // Dados JSON para o kanban
        $agendamentosKanban = null;
        if ($viewMode === 'kanban') {
            $agendamentosKanban = $agendamentos
                ->map(fn (Agendamento $ag) => [
                    'id' => $ag->id,
                    'status' => $ag->status,
                    'hora' => $ag->data_hora->format('H:i'),
                    'cliente' => $ag->cliente?->name ?? 'Cliente avulso',
                    'servico' => $ag->servico?->nome ?? '—',
                    'profissional' => explode(' ', $ag->profissional?->name ?? '—')[0],
                    'profissional_id' => $ag->profissional_id,
                    'cor' => $profCores[$ag->profissional_id] ?? '#1a1a1a',
                    'duracao' => $ag->duracao,
                    'valor' => (float) $ag->valor,
                    'canEdit' => $isAdmin
                        || ($meuProfissionalId !== null && $meuProfissionalId === $ag->profissional_id),
                    'showUrl' => route('agendamentos.show', $ag->id),
                    'statusUrl' => route('agendamentos.updateStatus', $ag->id),
                ])
                ->values();
        }

        return view('calendario.index', compact(
            'agendamentos',
            'agPorDia',
            'profissionais',
            'profCores',
            'profissionalId',
            'dias',
            'ref',
            'viewMode',
            'headerTitle',
            'navPrev',
            'navNext',
            'monthWeeks',
            'companySlug',
            'meuProfissionalId',
            'isAdmin',
            'agendamentosKanban',
        ));
    }

    /** @return array<int, array<int, int|null>> */
    private function buildMonthWeeks(Carbon $ref): array
    {
        $start = $ref->copy()->startOfMonth();
        $firstDow = ($start->dayOfWeek + 6) % 7;
        $totalDays = $ref->daysInMonth;

        $cells = array_fill(0, $firstDow, null);
        for ($d = 1; $d <= $totalDays; $d++) {
            $cells[] = $d;
        }
        while (count($cells) % 7 !== 0) {
            $cells[] = null;
        }

        return array_chunk($cells, 7);
    }
}
