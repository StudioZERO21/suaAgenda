<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAgendamentoRequest;
use App\Http\Requests\UpdateAgendamentoRequest;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Profissional;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AgendamentoController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Agendamento::class);

        $agendamentos = Agendamento::with(['profissional', 'cliente'])
            ->where('company_id', auth()->user()->empresa_id)
            ->ativo()
            ->orderBy('data_hora')
            ->paginate(20);

        return view('agendamentos.index', compact('agendamentos'));
    }

    public function create(): View
    {
        $this->authorize('create', Agendamento::class);

        $profissionais = Profissional::where('company_id', auth()->user()->empresa_id)
            ->ativo()
            ->orderBy('name')
            ->get();

        $clientes = Cliente::where('company_id', auth()->user()->empresa_id)
            ->orderBy('name')
            ->get();

        return view('agendamentos.create', compact('profissionais', 'clientes'));
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

            Agendamento::create([
                'company_id' => auth()->user()->empresa_id,
                ...$request->validated(),
            ]);
        } finally {
            $lock->release();
        }

        return redirect()->route('agendamentos.index')
            ->with('success', 'Agendamento criado com sucesso.');
    }

    public function show(Agendamento $agendamento): View
    {
        $this->authorize('view', $agendamento);

        return view('agendamentos.show', compact('agendamento'));
    }

    public function edit(Agendamento $agendamento): View
    {
        $this->authorize('update', $agendamento);

        $profissionais = Profissional::where('company_id', auth()->user()->empresa_id)
            ->ativo()
            ->orderBy('name')
            ->get();

        $clientes = Cliente::where('company_id', auth()->user()->empresa_id)
            ->orderBy('name')
            ->get();

        return view('agendamentos.edit', compact('agendamento', 'profissionais', 'clientes'));
    }

    public function update(UpdateAgendamentoRequest $request, Agendamento $agendamento): RedirectResponse
    {
        $agendamento->update($request->validated());

        return redirect()->route('agendamentos.index')
            ->with('success', 'Agendamento atualizado com sucesso.');
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
