<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MoveAgendamentoRequest;
use App\Http\Requests\StoreAgendamentoRequest;
use App\Http\Requests\UpdateAgendamentoRequest;
use App\Mail\AgendamentoConfirmado;
use App\Models\Agendamento;
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
            ->when($request->filled('q'), fn ($q) => $q->whereHas('cliente', fn ($cq) => $cq->where('name', 'like', '%'.$request->q.'%')))
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
            ->when($request->filled('q'), fn ($q) => $q->whereHas('cliente', fn ($cq) => $cq->where('name', 'like', '%'.$request->q.'%')))
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
