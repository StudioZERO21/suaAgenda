<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreProfissionalRequest;
use App\Http\Requests\UpdateProfissionalRequest;
use App\Models\Profissional;
use App\Models\Servico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfissionalController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Profissional::class);

        $empresa = auth()->user()->empresa_id;

        $profissionais = Profissional::where('company_id', $empresa)
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->withCount('agendamentos')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        // Estatísticas agregadas (toda a empresa, não apenas a página atual)
        // mantidas separadas da paginação para alimentar os stat cards do topo.
        $base = Profissional::where('company_id', $empresa);
        $stats = [
            'total' => (clone $base)->count(),
            'ativos' => (clone $base)->where('ativo', true)->count(),
            'inativos' => (clone $base)->where('ativo', false)->count(),
            'comissao_media' => (float) (clone $base)->where('ativo', true)->avg('comissao_pct'),
        ];

        return view('profissionais.index', compact('profissionais', 'stats'));
    }

    public function create(): View
    {
        $this->authorize('create', Profissional::class);

        $servicos = Servico::where('company_id', auth()->user()->empresa_id)
            ->ativo()
            ->orderBy('nome')
            ->get();

        return view('profissionais.create', compact('servicos'));
    }

    public function store(StoreProfissionalRequest $request): RedirectResponse
    {
        $this->authorize('create', Profissional::class);

        $data = $request->validated();
        $servicoIds = $data['servicos'] ?? [];
        unset($data['servicos']);

        $profissional = Profissional::create([
            ...$data,
            'company_id' => auth()->user()->empresa_id,
            'ativo' => $request->boolean('ativo', true),
        ]);

        if ($servicoIds) {
            $profissional->servicos()->sync($servicoIds);
        }

        return redirect()->route('profissionais.show', $profissional)
            ->with('success', "Profissional {$profissional->name} cadastrado com sucesso.");
    }

    public function show(Profissional $profissional): View
    {
        $this->authorize('view', $profissional);

        $profissional->load(['servicos', 'agendamentos' => fn ($q) => $q->with('cliente', 'servico')->latest('data_hora')->limit(10)]);

        return view('profissionais.show', compact('profissional'));
    }

    public function edit(Profissional $profissional): View
    {
        $this->authorize('update', $profissional);

        $servicos = Servico::where('company_id', auth()->user()->empresa_id)->ativo()->orderBy('nome')->get();
        $profissional->load('servicos');

        return view('profissionais.edit', compact('profissional', 'servicos'));
    }

    public function update(UpdateProfissionalRequest $request, Profissional $profissional): RedirectResponse
    {
        $this->authorize('update', $profissional);

        $data = $request->validated();
        $servicoIds = $data['servicos'] ?? [];
        unset($data['servicos']);

        $profissional->update([
            ...$data,
            'ativo' => $request->boolean('ativo'),
        ]);

        $profissional->servicos()->sync($servicoIds);

        return redirect()->route('profissionais.show', $profissional)
            ->with('success', 'Profissional atualizado com sucesso.');
    }

    public function destroy(Profissional $profissional): RedirectResponse
    {
        $this->authorize('delete', $profissional);

        $profissional->delete();

        return redirect()->route('profissionais.index')
            ->with('success', "Profissional {$profissional->name} removido.");
    }
}
