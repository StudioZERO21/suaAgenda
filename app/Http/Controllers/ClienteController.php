<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Cliente::class);

        $empresa = auth()->user()->empresa_id;

        $clientes = Cliente::where('company_id', $empresa)
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%")
                        ->orWhere('phone', 'like', "%{$request->search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('clientes.index', compact('clientes'));
    }

    public function create(): View
    {
        $this->authorize('create', Cliente::class);

        return view('clientes.create');
    }

    public function store(StoreClienteRequest $request): RedirectResponse
    {
        $this->authorize('create', Cliente::class);

        $cliente = Cliente::create([
            ...$request->validated(),
            'company_id' => auth()->user()->empresa_id,
            'lgpd_consent' => $request->boolean('lgpd_consent'),
        ]);

        return redirect()->route('clientes.show', $cliente)
            ->with('success', "Cliente {$cliente->name} cadastrado com sucesso.");
    }

    public function show(Cliente $cliente): View
    {
        $this->authorize('view', $cliente);

        $cliente->load(['agendamentos' => fn ($q) => $q->with('servico', 'profissional')->latest('data_hora')->limit(10)]);

        return view('clientes.show', compact('cliente'));
    }

    public function edit(Cliente $cliente): View
    {
        $this->authorize('update', $cliente);

        return view('clientes.edit', compact('cliente'));
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $this->authorize('update', $cliente);

        $cliente->update([
            ...$request->validated(),
            'lgpd_consent' => $request->boolean('lgpd_consent'),
        ]);

        return redirect()->route('clientes.show', $cliente)
            ->with('success', 'Cliente atualizado com sucesso.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        $this->authorize('delete', $cliente);

        $cliente->delete();

        return redirect()->route('clientes.index')
            ->with('success', "Cliente {$cliente->name} removido.");
    }
}
