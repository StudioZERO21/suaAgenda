<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreServicoRequest;
use App\Http\Requests\UpdateServicoRequest;
use App\Models\Profissional;
use App\Models\Servico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServicoController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Servico::class);

        $empresa = auth()->user()->empresa_id;

        $servicos = Servico::where('company_id', $empresa)
            ->when($request->search, fn ($q) => $q->where('nome', 'like', "%{$request->search}%"))
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('servicos.index', compact('servicos'));
    }

    public function create(): View
    {
        $this->authorize('create', Servico::class);

        $profissionais = Profissional::where('company_id', auth()->user()->empresa_id)
            ->ativo()
            ->orderBy('name')
            ->get();

        return view('servicos.create', compact('profissionais'));
    }

    public function store(StoreServicoRequest $request): RedirectResponse
    {
        $this->authorize('create', Servico::class);

        $data = $request->validated();
        $profissionalIds = $data['profissionais'] ?? [];
        unset($data['profissionais']);

        $servico = Servico::create([
            ...$data,
            'company_id' => auth()->user()->empresa_id,
            'ativo' => $request->boolean('ativo', true),
        ]);

        if ($profissionalIds) {
            $servico->profissionais()->sync($profissionalIds);
        }

        return redirect()->route('servicos.index')
            ->with('success', "Serviço {$servico->nome} criado com sucesso.");
    }

    public function edit(Servico $servico): View
    {
        $this->authorize('update', $servico);

        $empresa = auth()->user()->empresa_id;
        $profissionais = Profissional::where('company_id', $empresa)->ativo()->orderBy('name')->get();
        $servico->load('profissionais');

        return view('servicos.edit', compact('servico', 'profissionais'));
    }

    public function update(UpdateServicoRequest $request, Servico $servico): RedirectResponse
    {
        $this->authorize('update', $servico);

        $data = $request->validated();
        $profissionalIds = $data['profissionais'] ?? [];
        unset($data['profissionais']);

        $servico->update([
            ...$data,
            'ativo' => $request->boolean('ativo'),
        ]);

        $servico->profissionais()->sync($profissionalIds);

        return redirect()->route('servicos.index')
            ->with('success', 'Serviço atualizado com sucesso.');
    }

    public function destroy(Servico $servico): RedirectResponse
    {
        $this->authorize('delete', $servico);

        $servico->delete();

        return redirect()->route('servicos.index')
            ->with('success', "Serviço {$servico->nome} removido.");
    }
}
