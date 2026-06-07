<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAgendamentoRequest;
use App\Http\Requests\UpdateAgendamentoRequest;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Profissional;
use App\Models\Servico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AgendamentoController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Agendamento::class);

        $empresa = auth()->user()->empresa_id;

        $agendamentos = Agendamento::with(['profissional', 'cliente', 'servico'])
            ->where('company_id', $empresa)
            ->when(
                ! $request->filled('status'),
                fn ($q) => $q->ativo(),
                fn ($q) => $q->where('status', $request->status)
            )
            ->when($request->filled('data'), fn ($q) => $q->whereDate('data_hora', $request->data))
            ->when($request->filled('profissional_id'), fn ($q) => $q->where('profissional_id', $request->profissional_id))
            ->orderBy('data_hora')
            ->paginate(20)
            ->withQueryString();

        $profissionais = Profissional::where('company_id', $empresa)
            ->ativo()
            ->orderBy('name')
            ->get();

        return view('agendamentos.index', compact('agendamentos', 'profissionais'));
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
            $overlap = Agendamento::ativo()
                ->where('profissional_id', $request->profissional_id)
                ->where('data_hora', $request->data_hora)
                ->exists();

            if ($overlap) {
                return back()->withErrors(['data_hora' => 'Horário já ocupado para este profissional.'])->withInput();
            }

            $agendamento = Agendamento::create([
                'company_id' => auth()->user()->empresa_id,
                ...$request->validated(),
            ]);
        } finally {
            $lock->release();
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
        $agendamento->update($request->validated());

        return redirect()->route('agendamentos.show', $agendamento)
            ->with('success', 'Agendamento atualizado com sucesso.');
    }

    public function updateStatus(Request $request, Agendamento $agendamento): RedirectResponse
    {
        $this->authorize('update', $agendamento);

        $request->validate([
            'status' => ['required', 'in:pendente,confirmado,finalizado,cancelado'],
        ]);

        $agendamento->update(['status' => $request->status]);

        return back()->with('success', 'Status atualizado para "'.ucfirst($request->status).'".');
    }

    public function destroy(Agendamento $agendamento): RedirectResponse
    {
        $this->authorize('delete', $agendamento);

        $agendamento->update(['status' => Agendamento::STATUS_CANCELADO]);
        $agendamento->delete();

        return redirect()->route('agendamentos.index')
            ->with('success', 'Agendamento cancelado com sucesso.');
    }
}
