<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MoveAgendamentoRequest;
use App\Http\Requests\StoreAgendamentoRequest;
use App\Http\Requests\UpdateAgendamentoRequest;
use App\Mail\AgendamentoConfirmado;
use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\Cliente;
use App\Models\Profissional;
use App\Models\Servico;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgendamentoController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresa = auth()->user()->empresa_id;

        $agendamentos = Agendamento::with(['profissional', 'cliente', 'servico', 'avaliacao'])
            ->where('company_id', $empresa)
            ->when(
                ! $request->filled('status'),
                fn ($q) => $q->ativo(),
                fn ($q) => $q->where('status', $request->status)
            )
            ->when($request->filled('data'), fn ($q) => $q->whereDate('data_hora', $request->data))
            ->when($request->filled('profissional_id'), fn ($q) => $q->where('profissional_id', $request->profissional_id))
            ->when($request->filled('servico_id'), fn ($q) => $q->where('servico_id', $request->servico_id))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('cliente', fn ($cq) => $cq->where('name', 'like', '%'.$request->q.'%')->orWhere('phone', 'like', '%'.$request->q.'%')))
            ->orderBy('data_hora')
            ->paginate(20)
            ->withQueryString();

        $profissionais = Profissional::where('company_id', $empresa)
            ->ativo()
            ->orderBy('name')
            ->get();

        $servicos = Servico::where('company_id', $empresa)
            ->ativo()
            ->orderBy('nome')
            ->get();

        return view('agendamentos.index', compact('agendamentos', 'profissionais', 'servicos'));
    }

    public function exportarCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresa = auth()->user()->empresa_id;

        $agendamentos = Agendamento::with(['profissional', 'cliente', 'servico', 'avaliacao'])
            ->where('company_id', $empresa)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('data'), fn ($q) => $q->whereDate('data_hora', $request->data))
            ->when($request->filled('profissional_id'), fn ($q) => $q->where('profissional_id', $request->profissional_id))
            ->when($request->filled('servico_id'), fn ($q) => $q->where('servico_id', $request->servico_id))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('cliente', fn ($cq) => $cq->where('name', 'like', '%'.$request->q.'%')->orWhere('phone', 'like', '%'.$request->q.'%')))
            ->orderBy('data_hora')
            ->get();

        return response()->streamDownload(function () use ($agendamentos): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Data', 'Hora', 'Cliente', 'Telefone', 'Profissional', 'Serviço', 'Duração (min)', 'Valor (R$)', 'Status', 'Avaliação'], ';');

            foreach ($agendamentos as $ag) {
                fputcsv($out, [
                    $ag->data_hora->format('d/m/Y'),
                    $ag->data_hora->format('H:i'),
                    $ag->cliente?->name ?? 'Avulso',
                    $ag->cliente?->phone ?? '',
                    $ag->profissional?->name ?? '—',
                    $ag->servico?->nome ?? '—',
                    $ag->duracao,
                    number_format((float) $ag->valor, 2, ',', '.'),
                    ucfirst($ag->status),
                    $ag->avaliacao ? $ag->avaliacao->nota.'/5' : '',
                ], ';');
            }

            fclose($out);
        }, 'agendamentos-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function create(): View
    {
        $this->authorize('create', Agendamento::class);

        $empresa = auth()->user()->empresa_id;

        $profissionais = Profissional::where('company_id', $empresa)
            ->ativo()
            ->orderBy('name')
            ->get();

        $clientes = Cliente::where('company_id', $empresa)
            ->orderBy('name')
            ->get();

        $servicos = Servico::where('company_id', $empresa)
            ->ativo()
            ->with('profissionais:id')
            ->orderBy('nome')
            ->get();

        $servicosMap = $servicos->mapWithKeys(fn ($s) => [
            $s->id => [
                'nome' => $s->nome,
                'duracao_minutos' => $s->duracao_minutos,
                'preco' => (float) $s->preco,
                'profissionais' => $s->profissionais->pluck('id')->values()->toArray(),
            ],
        ]);

        $profissionaisMap = $profissionais->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'especialidade' => $p->especialidade,
        ])->values();

        return view('agendamentos.create', compact(
            'profissionais', 'clientes', 'servicos', 'servicosMap', 'profissionaisMap'
        ));
    }

    public function store(StoreAgendamentoRequest $request): RedirectResponse
    {
        $lockKey = "agendamento:{$request->profissional_id}:{$request->data_hora}";
        $lock = Cache::lock($lockKey, 10);

        if (! $lock->get()) {
            return back()->withErrors(['data_hora' => 'Horário já está sendo reservado. Tente novamente em instantes.']);
        }

        try {
            if ($this->temConflitoHorario(
                $request->profissional_id,
                Carbon::parse($request->data_hora),
                (int) $request->duracao
            )) {
                return back()->withErrors(['data_hora' => 'Horário já ocupado para este profissional.'])->withInput();
            }

            $agendamento = Agendamento::create([
                'company_id' => auth()->user()->empresa_id,
                ...$request->validated(),
            ]);
        } finally {
            $lock->release();
        }

        $agendamento->load(['cliente', 'profissional', 'servico', 'company']);

        if ($agendamento->cliente?->email) {
            Mail::to($agendamento->cliente->email)
                ->queue(new AgendamentoConfirmado($agendamento));
        }

        return redirect()->route('agendamentos.show', $agendamento)
            ->with('success', 'Agendamento criado com sucesso.');
    }

    public function show(Agendamento $agendamento): View
    {
        $this->authorize('view', $agendamento);

        $agendamento->load(['cliente', 'profissional', 'servico']);

        return view('agendamentos.show', compact('agendamento'));
    }

    public function edit(Agendamento $agendamento): View
    {
        $this->authorize('update', $agendamento);

        $empresa = auth()->user()->empresa_id;

        $profissionais = Profissional::where('company_id', $empresa)
            ->ativo()
            ->orderBy('name')
            ->get();

        $clientes = Cliente::where('company_id', $empresa)
            ->orderBy('name')
            ->get();

        $servicos = Servico::where('company_id', $empresa)
            ->ativo()
            ->with('profissionais:id')
            ->orderBy('nome')
            ->get();

        $servicosMap = $servicos->mapWithKeys(fn ($s) => [
            $s->id => [
                'nome' => $s->nome,
                'duracao_minutos' => $s->duracao_minutos,
                'preco' => (float) $s->preco,
                'profissionais' => $s->profissionais->pluck('id')->values()->toArray(),
            ],
        ]);

        $profissionaisMap = $profissionais->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'especialidade' => $p->especialidade,
        ])->values();

        return view('agendamentos.edit', compact(
            'agendamento', 'profissionais', 'clientes', 'servicos', 'servicosMap', 'profissionaisMap'
        ));
    }

    public function update(UpdateAgendamentoRequest $request, Agendamento $agendamento): RedirectResponse
    {
        if ($request->hasAny(['data_hora', 'duracao', 'profissional_id'])) {
            $inicio = Carbon::parse($request->data_hora ?? $agendamento->data_hora);
            $duracao = (int) ($request->duracao ?? $agendamento->duracao);
            $profissionalId = $request->profissional_id ?? $agendamento->profissional_id;

            if ($this->temConflitoHorario($profissionalId, $inicio, $duracao, $agendamento->id)) {
                return back()->withErrors(['data_hora' => 'Horário já ocupado para este profissional.'])->withInput();
            }
        }

        $agendamento->update($request->validated());

        return redirect()->route('agendamentos.show', $agendamento)
            ->with('success', 'Agendamento atualizado com sucesso.');
    }

    /**
     * Reposiciona um agendamento na grade do calendário (drag-and-drop).
     */
    public function move(MoveAgendamentoRequest $request, Agendamento $agendamento): JsonResponse
    {
        if ($agendamento->status === Agendamento::STATUS_CANCELADO) {
            return response()->json([
                'message' => 'Agendamentos cancelados não podem ser movidos.',
            ], 422);
        }

        $novaData = $request->dataHora();

        if ($this->temConflitoHorario($agendamento->profissional_id, $novaData, $agendamento->duracao, $agendamento->id)) {
            return response()->json([
                'message' => 'Horário já ocupado para este profissional.',
            ], 422);
        }

        $lockKey = "agendamento:{$agendamento->profissional_id}:{$novaData->format('Y-m-d H:i')}";
        $lock = Cache::lock($lockKey, 10);

        if (! $lock->get()) {
            return response()->json([
                'message' => 'Horário já está sendo reservado. Tente novamente.',
            ], 409);
        }

        try {
            $agendamento->update(['data_hora' => $novaData]);
        } finally {
            $lock->release();
        }

        return response()->json([
            'message' => 'Movido para '.$novaData->format('H:i'),
            'data_hora' => $novaData->toIso8601String(),
            'data' => $novaData->format('Y-m-d'),
            'hora' => (int) $novaData->format('H'),
            'minuto' => (int) $novaData->format('i'),
            'hora_label' => $novaData->format('H:i'),
        ]);
    }

    public function updateStatus(Request $request, Agendamento $agendamento): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $agendamento);

        $request->validate([
            'status' => ['required', 'in:pendente,confirmado,finalizado,cancelado,em_atendimento'],
        ]);

        $previousStatus = $agendamento->status;

        $agendamento->update(['status' => $request->status]);

        if ($request->status === Agendamento::STATUS_CONFIRMADO
            && $previousStatus !== Agendamento::STATUS_CONFIRMADO
            && $agendamento->cliente?->email
        ) {
            $agendamento->load(['cliente', 'profissional', 'servico', 'company']);
            Mail::to($agendamento->cliente->email)->queue(new AgendamentoConfirmado($agendamento));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $agendamento->status,
                'message' => 'Status atualizado para "'.ucfirst($request->status).'".',
            ]);
        }

        return back()->with('success', 'Status atualizado para "'.ucfirst($request->status).'".');
    }

    public function duplicar(Request $request, Agendamento $agendamento): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Agendamento::class);

        if ($agendamento->company_id !== auth()->user()->empresa_id) {
            abort(403);
        }

        $data = $request->validate([
            'data_hora' => ['required', 'date', 'after:now'],
        ]);

        $novaData = Carbon::parse($data['data_hora']);

        if ($this->temConflitoHorario($agendamento->profissional_id, $novaData, $agendamento->duracao)) {
            return back()->withErrors(['data_hora' => 'Horário já ocupado para este profissional.'])->withInput();
        }

        $novo = Agendamento::create([
            'company_id' => $agendamento->company_id,
            'cliente_id' => $agendamento->cliente_id,
            'profissional_id' => $agendamento->profissional_id,
            'servico_id' => $agendamento->servico_id,
            'data_hora' => $novaData,
            'duracao' => $agendamento->duracao,
            'valor' => $agendamento->valor,
            'observacao' => $agendamento->observacao,
            'status' => Agendamento::STATUS_PENDENTE,
        ]);

        return redirect()->route('agendamentos.show', $novo)
            ->with('success', 'Agendamento duplicado com sucesso.');
    }

    public function bulkStatus(Request $request): JsonResponse
    {
        $this->authorize('updateAnyStatus', Agendamento::class);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['uuid'],
            'status' => ['required', 'in:pendente,confirmado,finalizado,cancelado,em_atendimento'],
        ]);

        $updated = Agendamento::where('company_id', auth()->user()->empresa_id)
            ->whereIn('id', $data['ids'])
            ->update(['status' => $data['status']]);

        return response()->json(['updated' => $updated]);
    }

    public function porProfissional(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresa = auth()->user()->empresa_id;
        $data = $request->input('data', today()->toDateString());

        $agendamentos = Agendamento::where('company_id', $empresa)
            ->whereDate('data_hora', $data)
            ->with(['profissional:id,name,cor', 'servico:id,nome', 'cliente:id,name'])
            ->orderBy('data_hora')
            ->get();

        $grouped = $agendamentos->groupBy('profissional_id')
            ->map(function ($items, $profissionalId) {
                $primeiro = $items->first();

                return [
                    'profissional_id' => $profissionalId,
                    'profissional_nome' => $primeiro->profissional?->name ?? 'Sem profissional',
                    'profissional_cor' => $primeiro->profissional?->cor ?? '#999999',
                    'total' => $items->count(),
                    'finalizados' => $items->where('status', Agendamento::STATUS_FINALIZADO)->count(),
                    'receita' => (float) $items->where('status', Agendamento::STATUS_FINALIZADO)->sum('valor'),
                    'agendamentos' => $items->map(fn (Agendamento $ag) => [
                        'id' => $ag->id,
                        'data_hora' => $ag->data_hora->toIso8601String(),
                        'status' => $ag->status,
                        'cliente_nome' => $ag->cliente?->name ?? '',
                        'servico_nome' => $ag->servico?->nome ?? '',
                        'valor' => (float) $ag->valor,
                    ])->values(),
                ];
            })
            ->values();

        return response()->json(['data' => $data, 'total_agendamentos' => $agendamentos->count(), 'profissionais' => $grouped]);
    }

    public function porServico(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresa = auth()->user()->empresa_id;
        $data = $request->input('data', today()->toDateString());

        $agendamentos = Agendamento::where('company_id', $empresa)
            ->whereDate('data_hora', $data)
            ->with(['servico:id,nome,cor', 'profissional:id,name', 'cliente:id,name'])
            ->orderBy('data_hora')
            ->get();

        $grouped = $agendamentos->groupBy('servico_id')
            ->map(function ($items, $servicoId) {
                $primeiro = $items->first();

                return [
                    'servico_id' => $servicoId,
                    'servico_nome' => $primeiro->servico?->nome ?? 'Sem serviço',
                    'servico_cor' => $primeiro->servico?->cor ?? '#999999',
                    'total' => $items->count(),
                    'finalizados' => $items->where('status', Agendamento::STATUS_FINALIZADO)->count(),
                    'receita' => (float) $items->where('status', Agendamento::STATUS_FINALIZADO)->sum('valor'),
                    'agendamentos' => $items->map(fn (Agendamento $ag) => [
                        'id' => $ag->id,
                        'data_hora' => $ag->data_hora->toIso8601String(),
                        'status' => $ag->status,
                        'cliente_nome' => $ag->cliente?->name ?? '',
                        'profissional_nome' => $ag->profissional?->name ?? '',
                        'valor' => (float) $ag->valor,
                    ])->values(),
                ];
            })
            ->values();

        return response()->json(['data' => $data, 'total_agendamentos' => $agendamentos->count(), 'servicos' => $grouped]);
    }

    public function porMes(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresa = auth()->user()->empresa_id;
        $ano = (int) $request->input('ano', now()->year);

        $agendamentos = Agendamento::where('company_id', $empresa)
            ->whereYear('data_hora', $ano)
            ->get(['data_hora', 'status', 'valor']);

        $meses = collect(range(1, 12))->map(function (int $mes) use ($agendamentos): array {
            $deste = $agendamentos->filter(fn (Agendamento $a) => $a->data_hora->month === $mes);

            return [
                'mes' => $mes,
                'mes_nome' => now()->setMonth($mes)->translatedFormat('M'),
                'total' => $deste->count(),
                'finalizados' => $deste->where('status', Agendamento::STATUS_FINALIZADO)->count(),
                'cancelados' => $deste->where('status', Agendamento::STATUS_CANCELADO)->count(),
                'receita' => (float) $deste->where('status', Agendamento::STATUS_FINALIZADO)->sum('valor'),
            ];
        });

        return response()->json([
            'ano' => $ano,
            'total_ano' => $agendamentos->count(),
            'receita_ano' => (float) $agendamentos->where('status', Agendamento::STATUS_FINALIZADO)->sum('valor'),
            'meses' => $meses,
        ]);
    }

    public function contagemPorStatus(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresa = auth()->user()->empresa_id;
        $dias = $request->input('dias');

        $agendamentos = Agendamento::where('company_id', $empresa)
            ->when($dias !== null, fn ($q) => $q->where('data_hora', '>=', now()->subDays((int) $dias)))
            ->get(['status']);

        $byStatus = $agendamentos->groupBy('status');
        $total = $agendamentos->count();

        return response()->json([
            'periodo_dias' => $dias !== null ? (int) $dias : null,
            'total' => $total,
            'pendente' => $byStatus->get(Agendamento::STATUS_PENDENTE, collect())->count(),
            'confirmado' => $byStatus->get(Agendamento::STATUS_CONFIRMADO, collect())->count(),
            'em_atendimento' => $byStatus->get(Agendamento::STATUS_EM_ATENDIMENTO, collect())->count(),
            'finalizado' => $byStatus->get(Agendamento::STATUS_FINALIZADO, collect())->count(),
            'cancelado' => $byStatus->get(Agendamento::STATUS_CANCELADO, collect())->count(),
        ]);
    }

    public function resumoSemana(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresa = auth()->user()->empresa_id;

        $semanaInicio = $request->input('inicio')
            ? Carbon::parse($request->input('inicio'))->startOfDay()
            : now()->startOfWeek(Carbon::MONDAY);
        $semanaFim = $semanaInicio->copy()->addDays(6)->endOfDay();

        $agendamentos = Agendamento::where('company_id', $empresa)
            ->whereBetween('data_hora', [$semanaInicio, $semanaFim])
            ->get(['data_hora', 'status', 'valor']);

        $dias = collect(range(0, 6))->map(function (int $offset) use ($semanaInicio, $agendamentos): array {
            $dia = $semanaInicio->copy()->addDays($offset);
            $deste = $agendamentos->filter(fn (Agendamento $a) => $a->data_hora->isSameDay($dia));

            return [
                'data' => $dia->format('Y-m-d'),
                'dia_semana' => $dia->translatedFormat('l'),
                'total' => $deste->count(),
                'finalizados' => $deste->where('status', Agendamento::STATUS_FINALIZADO)->count(),
                'cancelados' => $deste->where('status', Agendamento::STATUS_CANCELADO)->count(),
                'pendentes' => $deste->whereIn('status', [Agendamento::STATUS_PENDENTE, Agendamento::STATUS_CONFIRMADO])->count(),
                'receita' => (float) $deste->where('status', Agendamento::STATUS_FINALIZADO)->sum('valor'),
            ];
        });

        return response()->json([
            'semana_inicio' => $semanaInicio->format('Y-m-d'),
            'semana_fim' => $semanaFim->format('Y-m-d'),
            'total_semana' => $agendamentos->count(),
            'receita_semana' => (float) $agendamentos->where('status', Agendamento::STATUS_FINALIZADO)->sum('valor'),
            'dias' => $dias,
        ]);
    }

    public function porHora(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresa = auth()->user()->empresa_id;
        $dias = max(1, min(365, (int) $request->input('dias', 30)));

        $agendamentos = Agendamento::where('company_id', $empresa)
            ->where('data_hora', '>=', now()->subDays($dias)->startOfDay())
            ->get(['data_hora', 'status']);

        $porHora = $agendamentos->groupBy(fn (Agendamento $a) => (int) $a->data_hora->format('G'));

        $horas = collect(range(0, 23))->map(function (int $hora) use ($porHora): array {
            $items = $porHora->get($hora, collect());

            return [
                'hora' => $hora,
                'hora_fmt' => sprintf('%02d:00', $hora),
                'total' => $items->count(),
                'finalizados' => $items->where('status', Agendamento::STATUS_FINALIZADO)->count(),
                'cancelados' => $items->where('status', Agendamento::STATUS_CANCELADO)->count(),
            ];
        });

        $horaPico = $horas->sortByDesc('total')->first();

        return response()->json([
            'periodo_dias' => $dias,
            'total' => $agendamentos->count(),
            'horas' => $horas->values(),
            'hora_pico' => $horaPico['total'] > 0 ? $horaPico : null,
        ]);
    }

    public function resumoHoje(): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresa = auth()->user()->empresa_id;

        $agendamentos = Agendamento::where('company_id', $empresa)
            ->whereDate('data_hora', today())
            ->get(['id', 'status', 'valor', 'data_hora', 'profissional_id']);

        $byStatus = $agendamentos->groupBy('status');

        $proximo = $agendamentos
            ->filter(fn (Agendamento $a) => in_array($a->status, [Agendamento::STATUS_PENDENTE, Agendamento::STATUS_CONFIRMADO]) && $a->data_hora->isFuture())
            ->sortBy('data_hora')
            ->first();

        return response()->json([
            'total' => $agendamentos->count(),
            'pendentes' => $byStatus->get(Agendamento::STATUS_PENDENTE, collect())->count(),
            'confirmados' => $byStatus->get(Agendamento::STATUS_CONFIRMADO, collect())->count(),
            'em_atendimento' => $byStatus->get(Agendamento::STATUS_EM_ATENDIMENTO, collect())->count(),
            'finalizados' => $byStatus->get(Agendamento::STATUS_FINALIZADO, collect())->count(),
            'cancelados' => $byStatus->get(Agendamento::STATUS_CANCELADO, collect())->count(),
            'receita_dia' => (float) $agendamentos->where('status', Agendamento::STATUS_FINALIZADO)->sum('valor'),
            'proximo_horario' => $proximo?->data_hora->toIso8601String(),
        ]);
    }

    public function hoje(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresa = auth()->user()->empresa_id;
        $status = $request->input('status');

        $query = Agendamento::where('company_id', $empresa)
            ->whereDate('data_hora', today())
            ->with(['cliente:id,name,phone', 'servico:id,nome,cor', 'profissional:id,name'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('data_hora');

        $items = $query->get()->map(fn (Agendamento $ag) => [
            'id' => $ag->id,
            'data_hora' => $ag->data_hora->toIso8601String(),
            'cliente_nome' => $ag->cliente?->name ?? '',
            'cliente_phone' => $ag->cliente?->phone ?? '',
            'servico_nome' => $ag->servico?->nome ?? '',
            'servico_cor' => $ag->servico?->cor ?? '#999999',
            'profissional_nome' => $ag->profissional?->name ?? '',
            'profissional_id' => $ag->profissional_id,
            'status' => $ag->status,
            'valor' => (float) $ag->valor,
            'duracao' => (int) $ag->duracao,
        ]);

        return response()->json(['total' => $items->count(), 'items' => $items]);
    }

    public function buscar(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $q = trim((string) $request->input('q', ''));
        $empresa = auth()->user()->empresa_id;

        if ($q === '') {
            return response()->json([]);
        }

        $agendamentos = Agendamento::where('company_id', $empresa)
            ->where(function ($query) use ($q): void {
                $query->whereHas('cliente', fn ($cq) => $cq->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%"))
                    ->orWhereHas('servico', fn ($sq) => $sq->where('nome', 'like', "%{$q}%"))
                    ->orWhere('status', 'like', "%{$q}%");
            })
            ->with(['cliente:id,name,phone', 'servico:id,nome,cor', 'profissional:id,name'])
            ->orderByDesc('data_hora')
            ->limit(20)
            ->get()
            ->map(fn (Agendamento $ag) => [
                'id' => $ag->id,
                'data_hora' => $ag->data_hora->toIso8601String(),
                'cliente_nome' => $ag->cliente?->name ?? '',
                'cliente_phone' => $ag->cliente?->phone ?? '',
                'servico_nome' => $ag->servico?->nome ?? '',
                'servico_cor' => $ag->servico?->cor ?? '#999999',
                'profissional_nome' => $ag->profissional?->name ?? '',
                'status' => $ag->status,
                'valor' => (float) $ag->valor,
            ]);

        return response()->json($agendamentos);
    }

    public function proximos(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresa = auth()->user()->empresa_id;
        $limite = min((int) $request->input('limite', 10), 50);

        $items = Agendamento::where('company_id', $empresa)
            ->whereIn('status', [Agendamento::STATUS_CONFIRMADO, Agendamento::STATUS_PENDENTE])
            ->where('data_hora', '>=', now())
            ->with(['cliente:id,name,phone', 'servico:id,nome,cor', 'profissional:id,name'])
            ->orderBy('data_hora')
            ->limit($limite)
            ->get()
            ->map(fn (Agendamento $ag) => [
                'id' => $ag->id,
                'data_hora' => $ag->data_hora->toIso8601String(),
                'cliente_nome' => $ag->cliente?->name ?? '',
                'cliente_phone' => $ag->cliente?->phone ?? '',
                'servico_nome' => $ag->servico?->nome ?? '',
                'servico_cor' => $ag->servico?->cor ?? '#999999',
                'profissional_nome' => $ag->profissional?->name ?? '',
                'status' => $ag->status,
                'valor' => (float) $ag->valor,
                'duracao' => (int) $ag->duracao,
            ]);

        return response()->json(['total' => $items->count(), 'items' => $items]);
    }

    public function historicoCliente(Request $request, Agendamento $agendamento): JsonResponse
    {
        $this->authorize('view', $agendamento);

        $limite = min((int) $request->input('limite', 10), 30);

        $historico = Agendamento::where('company_id', $agendamento->company_id)
            ->where('cliente_id', $agendamento->cliente_id)
            ->where('id', '!=', $agendamento->id)
            ->with(['servico:id,nome,cor', 'profissional:id,name', 'avaliacao:id,agendamento_id,nota'])
            ->orderByDesc('data_hora')
            ->limit($limite)
            ->get()
            ->map(fn (Agendamento $ag) => [
                'id' => $ag->id,
                'data_hora' => $ag->data_hora->toIso8601String(),
                'servico_nome' => $ag->servico?->nome ?? '',
                'servico_cor' => $ag->servico?->cor ?? '#999999',
                'profissional_nome' => $ag->profissional?->name ?? '',
                'status' => $ag->status,
                'valor' => (float) $ag->valor,
                'nota' => $ag->avaliacao?->nota,
            ]);

        return response()->json([
            'cliente_id' => $agendamento->cliente_id,
            'total' => $historico->count(),
            'items' => $historico,
        ]);
    }

    public function agenda(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresa = auth()->user()->empresa_id;
        $dias = min((int) $request->input('dias', 7), 30);

        $fim = now()->addDays($dias)->endOfDay();

        $agendamentos = Agendamento::where('company_id', $empresa)
            ->whereIn('status', [Agendamento::STATUS_CONFIRMADO, Agendamento::STATUS_PENDENTE])
            ->whereBetween('data_hora', [now(), $fim])
            ->with(['cliente:id,name,phone', 'servico:id,nome,cor', 'profissional:id,name'])
            ->orderBy('data_hora')
            ->get();

        $grouped = $agendamentos->groupBy(fn (Agendamento $ag) => $ag->data_hora->format('Y-m-d'))
            ->map(fn ($items, $data) => [
                'data' => $data,
                'total' => $items->count(),
                'items' => $items->map(fn (Agendamento $ag) => [
                    'id' => $ag->id,
                    'hora' => $ag->data_hora->format('H:i'),
                    'cliente_nome' => $ag->cliente?->name ?? '',
                    'servico_nome' => $ag->servico?->nome ?? '',
                    'servico_cor' => $ag->servico?->cor ?? '#999999',
                    'profissional_nome' => $ag->profissional?->name ?? '',
                    'status' => $ag->status,
                    'duracao' => (int) $ag->duracao,
                ])->values(),
            ])->values();

        return response()->json([
            'dias' => $dias,
            'total' => $agendamentos->count(),
            'dias_com_agendamentos' => $grouped->count(),
            'agenda' => $grouped,
        ]);
    }

    public function emAtendimento(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresa = auth()->user()->empresa_id;

        $items = Agendamento::where('company_id', $empresa)
            ->where('status', Agendamento::STATUS_EM_ATENDIMENTO)
            ->with(['cliente:id,name,phone', 'servico:id,nome,cor', 'profissional:id,name'])
            ->orderBy('data_hora')
            ->get()
            ->map(fn (Agendamento $ag) => [
                'id' => $ag->id,
                'data_hora' => $ag->data_hora->toIso8601String(),
                'cliente_nome' => $ag->cliente?->name ?? '',
                'cliente_phone' => $ag->cliente?->phone ?? '',
                'servico_nome' => $ag->servico?->nome ?? '',
                'servico_cor' => $ag->servico?->cor ?? '#999999',
                'profissional_nome' => $ag->profissional?->name ?? '',
                'profissional_id' => $ag->profissional_id,
                'duracao' => (int) $ag->duracao,
            ]);

        return response()->json(['total' => $items->count(), 'items' => $items]);
    }

    public function pendentes(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresa = auth()->user()->empresa_id;
        $limite = min((int) $request->input('limite', 20), 50);

        $items = Agendamento::where('company_id', $empresa)
            ->where('status', Agendamento::STATUS_PENDENTE)
            ->where('data_hora', '>=', now())
            ->with(['cliente:id,name,phone', 'servico:id,nome,cor', 'profissional:id,name'])
            ->orderBy('data_hora')
            ->limit($limite)
            ->get()
            ->map(fn (Agendamento $ag) => [
                'id' => $ag->id,
                'data_hora' => $ag->data_hora->toIso8601String(),
                'cliente_nome' => $ag->cliente?->name ?? '',
                'cliente_phone' => $ag->cliente?->phone ?? '',
                'servico_nome' => $ag->servico?->nome ?? '',
                'servico_cor' => $ag->servico?->cor ?? '#999999',
                'profissional_nome' => $ag->profissional?->name ?? '',
                'valor' => (float) $ag->valor,
                'duracao' => (int) $ag->duracao,
                'cancel_token' => $ag->cancel_token,
            ]);

        return response()->json(['total' => $items->count(), 'items' => $items]);
    }

    public function cancelados(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresa = auth()->user()->empresa_id;
        $limite = min((int) $request->input('limite', 20), 50);
        $dias = min((int) $request->input('dias', 30), 90);

        $items = Agendamento::where('company_id', $empresa)
            ->where('status', Agendamento::STATUS_CANCELADO)
            ->where('data_hora', '>=', now()->subDays($dias))
            ->with(['cliente:id,name,phone', 'servico:id,nome,cor', 'profissional:id,name'])
            ->orderByDesc('data_hora')
            ->limit($limite)
            ->get()
            ->map(fn (Agendamento $ag) => [
                'id' => $ag->id,
                'data_hora' => $ag->data_hora->toIso8601String(),
                'cliente_nome' => $ag->cliente?->name ?? '',
                'cliente_phone' => $ag->cliente?->phone ?? '',
                'servico_nome' => $ag->servico?->nome ?? '',
                'servico_cor' => $ag->servico?->cor ?? '#999999',
                'profissional_nome' => $ag->profissional?->name ?? '',
                'valor' => (float) $ag->valor,
                'duracao' => (int) $ag->duracao,
            ]);

        return response()->json(['total' => $items->count(), 'items' => $items]);
    }

    public function semAvaliacao(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresa = auth()->user()->empresa_id;
        $dias = max(1, min(90, (int) $request->input('dias', 30)));
        $limite = min((int) $request->input('limite', 20), 100);

        $items = Agendamento::where('company_id', $empresa)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->where('data_hora', '>=', now()->subDays($dias)->startOfDay())
            ->doesntHave('avaliacao')
            ->with(['cliente:id,name,phone', 'servico:id,nome', 'profissional:id,name'])
            ->orderByDesc('data_hora')
            ->limit($limite)
            ->get()
            ->map(fn (Agendamento $ag) => [
                'agendamento_id' => $ag->id,
                'data_hora' => $ag->data_hora->toIso8601String(),
                'cliente_nome' => $ag->cliente?->name ?? '',
                'cliente_phone' => $ag->cliente?->phone ?? '',
                'profissional_nome' => $ag->profissional?->name ?? '',
                'servico_nome' => $ag->servico?->nome ?? '',
                'valor' => (float) $ag->valor,
                'dias_desde' => (int) $ag->data_hora->diffInDays(now()),
            ]);

        return response()->json([
            'periodo_dias' => $dias,
            'total' => $items->count(),
            'items' => $items->values(),
        ]);
    }

    public function detalhe(Agendamento $agendamento): JsonResponse
    {
        $this->authorize('view', $agendamento);

        $agendamento->load(['cliente:id,name,phone,email', 'profissional:id,name,especialidade,cor', 'servico:id,nome,cor,duracao_minutos,preco', 'avaliacao']);

        return response()->json([
            'id' => $agendamento->id,
            'data_hora' => $agendamento->data_hora->toIso8601String(),
            'status' => $agendamento->status,
            'duracao' => (int) $agendamento->duracao,
            'valor' => (float) $agendamento->valor,
            'observacao' => $agendamento->observacao ?? '',
            'cliente' => $agendamento->cliente ? [
                'id' => $agendamento->cliente->id,
                'name' => $agendamento->cliente->name,
                'phone' => $agendamento->cliente->phone ?? '',
                'email' => $agendamento->cliente->email ?? '',
            ] : null,
            'profissional' => $agendamento->profissional ? [
                'id' => $agendamento->profissional->id,
                'name' => $agendamento->profissional->name,
                'especialidade' => $agendamento->profissional->especialidade ?? '',
                'cor' => $agendamento->profissional->cor ?? '#999999',
            ] : null,
            'servico' => $agendamento->servico ? [
                'id' => $agendamento->servico->id,
                'nome' => $agendamento->servico->nome,
                'cor' => $agendamento->servico->cor ?? '#999999',
                'duracao_minutos' => (int) $agendamento->servico->duracao_minutos,
                'preco' => (float) $agendamento->servico->preco,
            ] : null,
            'avaliacao' => $agendamento->avaliacao ? [
                'nota' => $agendamento->avaliacao->nota,
                'comentario' => $agendamento->avaliacao->comentario ?? '',
            ] : null,
        ]);
    }

    public function observacao(Request $request, Agendamento $agendamento): JsonResponse
    {
        $this->authorize('update', $agendamento);

        $request->validate(['observacao' => ['nullable', 'string', 'max:1000']]);

        $agendamento->update(['observacao' => $request->input('observacao', '')]);

        return response()->json([
            'observacao' => $agendamento->observacao ?? '',
            'updated_at' => $agendamento->updated_at->toIso8601String(),
        ]);
    }

    public function valor(Request $request, Agendamento $agendamento): JsonResponse
    {
        $this->authorize('update', $agendamento);

        $request->validate(['valor' => ['required', 'numeric', 'min:0']]);

        $agendamento->update(['valor' => $request->input('valor')]);

        return response()->json([
            'valor' => (float) $agendamento->valor,
            'updated_at' => $agendamento->updated_at->toIso8601String(),
        ]);
    }

    public function duracao(Request $request, Agendamento $agendamento): JsonResponse
    {
        $this->authorize('update', $agendamento);

        $request->validate(['duracao' => ['required', 'integer', 'min:5', 'max:480']]);

        $agendamento->update(['duracao' => $request->integer('duracao')]);

        return response()->json([
            'duracao' => $agendamento->duracao,
            'updated_at' => $agendamento->updated_at->toIso8601String(),
        ]);
    }

    public function reassignarCliente(Request $request, Agendamento $agendamento): JsonResponse
    {
        $this->authorize('update', $agendamento);

        $request->validate([
            'cliente_id' => ['required', 'uuid', 'exists:clientes,id'],
        ]);

        $cliente = Cliente::where('id', $request->input('cliente_id'))
            ->where('company_id', auth()->user()->empresa_id)
            ->where('ativo', true)
            ->firstOrFail();

        $agendamento->update(['cliente_id' => $cliente->id]);

        return response()->json([
            'cliente_id' => $agendamento->cliente_id,
            'cliente_nome' => $cliente->name,
            'updated_at' => $agendamento->updated_at->toIso8601String(),
        ]);
    }

    public function reassignarServico(Request $request, Agendamento $agendamento): JsonResponse
    {
        $this->authorize('update', $agendamento);

        $request->validate([
            'servico_id' => ['required', 'uuid', 'exists:servicos,id'],
        ]);

        $servico = Servico::where('id', $request->input('servico_id'))
            ->where('company_id', auth()->user()->empresa_id)
            ->where('ativo', true)
            ->firstOrFail();

        $agendamento->update(['servico_id' => $servico->id]);

        return response()->json([
            'servico_id' => $agendamento->servico_id,
            'servico_nome' => $servico->nome,
            'servico_preco' => (float) $servico->preco,
            'updated_at' => $agendamento->updated_at->toIso8601String(),
        ]);
    }

    public function reassignarProfissional(Request $request, Agendamento $agendamento): JsonResponse
    {
        $this->authorize('update', $agendamento);

        $request->validate([
            'profissional_id' => ['required', 'uuid', 'exists:profissionais,id'],
        ]);

        $profissional = Profissional::where('id', $request->input('profissional_id'))
            ->where('company_id', auth()->user()->empresa_id)
            ->where('ativo', true)
            ->firstOrFail();

        $agendamento->update(['profissional_id' => $profissional->id]);

        return response()->json([
            'profissional_id' => $agendamento->profissional_id,
            'profissional_nome' => $profissional->name,
            'updated_at' => $agendamento->updated_at->toIso8601String(),
        ]);
    }

    public function getAvaliacao(Agendamento $agendamento): JsonResponse
    {
        $this->authorize('view', $agendamento);

        $avaliacao = Avaliacao::where('agendamento_id', $agendamento->id)->first();

        if ($avaliacao === null) {
            return response()->json(['avaliado' => false, 'avaliacao' => null]);
        }

        return response()->json([
            'avaliado' => true,
            'avaliacao' => [
                'id' => $avaliacao->id,
                'nota' => $avaliacao->nota,
                'comentario' => $avaliacao->comentario ?? '',
                'estrelas' => $avaliacao->estrelas(),
                'created_at' => $avaliacao->created_at->toIso8601String(),
            ],
        ]);
    }

    public function avaliacao(Request $request, Agendamento $agendamento): JsonResponse
    {
        $this->authorize('view', $agendamento);

        abort_if($agendamento->status !== Agendamento::STATUS_FINALIZADO, 422, 'Apenas agendamentos finalizados podem ser avaliados.');

        if (Avaliacao::where('agendamento_id', $agendamento->id)->exists()) {
            return response()->json(['message' => 'Este agendamento já foi avaliado.'], 409);
        }

        $request->validate([
            'nota' => ['required', 'integer', 'min:1', 'max:5'],
            'comentario' => ['nullable', 'string', 'max:1000'],
        ]);

        $avaliacao = Avaliacao::create([
            'company_id' => auth()->user()->empresa_id,
            'agendamento_id' => $agendamento->id,
            'nota' => $request->integer('nota'),
            'comentario' => $request->input('comentario'),
        ]);

        return response()->json([
            'id' => $avaliacao->id,
            'nota' => $avaliacao->nota,
            'comentario' => $avaliacao->comentario ?? '',
            'estrelas' => $avaliacao->estrelas(),
            'created_at' => $avaliacao->created_at->toIso8601String(),
        ], 201);
    }

    public function destroy(Agendamento $agendamento): RedirectResponse
    {
        $this->authorize('delete', $agendamento);

        $agendamento->update(['status' => Agendamento::STATUS_CANCELADO]);
        $agendamento->delete();

        return redirect()->route('agendamentos.index')
            ->with('success', 'Agendamento cancelado com sucesso.');
    }

    /**
     * Verifica sobreposição de horários para o mesmo profissional.
     * Cada profissional pode ter apenas 1 agendamento por slot; profissionais diferentes
     * podem ser agendados simultaneamente sem conflito.
     */
    private function temConflitoHorario(
        string $profissionalId,
        Carbon $inicio,
        int $duracao,
        ?string $excluirId = null
    ): bool {
        $fim = $inicio->copy()->addMinutes($duracao);

        $query = Agendamento::ativo()->where('profissional_id', $profissionalId);

        if ($excluirId !== null) {
            $query->where('id', '!=', $excluirId);
        }

        return $query->get()->contains(function (Agendamento $outro) use ($inicio, $fim) {
            $outroFim = $outro->data_hora->copy()->addMinutes($outro->duracao);

            return $inicio->lt($outroFim) && $fim->gt($outro->data_hora);
        });
    }
}
