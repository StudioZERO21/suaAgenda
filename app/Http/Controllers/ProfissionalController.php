<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreProfissionalRequest;
use App\Http\Requests\UpdateProfissionalRequest;
use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\Profissional;
use App\Models\Servico;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
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
            ->with('servicos:id')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $base = Profissional::where('company_id', $empresa);
        $stats = [
            'total' => (clone $base)->count(),
            'ativos' => (clone $base)->where('ativo', true)->count(),
            'inativos' => (clone $base)->where('ativo', false)->count(),
            'comissao_media' => (float) (clone $base)->where('ativo', true)->avg('comissao_pct'),
        ];

        $servicos = Servico::where('company_id', $empresa)->ativo()->orderBy('nome')->get(['id', 'nome', 'cor', 'duracao_minutos', 'preco']);

        return view('profissionais.index', compact('profissionais', 'stats', 'servicos'));
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

        $profissional->load(['servicos', 'agendamentos' => fn ($q) => $q->with('cliente', 'servico')->latest('data_hora')->limit(20)]);

        $mesInicio = Carbon::today()->startOfMonth();
        $mesFim = Carbon::today()->endOfMonth();

        $agsMes = Agendamento::where('profissional_id', $profissional->id)
            ->whereBetween('data_hora', [$mesInicio, $mesFim]);

        $totalMes = (clone $agsMes)->count();
        $finalizadosMes = (clone $agsMes)->where('status', Agendamento::STATUS_FINALIZADO)->count();
        $receitaMes = (float) (clone $agsMes)->where('status', Agendamento::STATUS_FINALIZADO)->sum('valor');
        $taxaConclusao = $totalMes > 0 ? (int) round($finalizadosMes / $totalMes * 100) : 0;

        $notaMedia = Avaliacao::whereHas('agendamento', fn ($q) => $q->where('profissional_id', $profissional->id))->avg('nota');

        return view('profissionais.show', compact('profissional', 'totalMes', 'receitaMes', 'taxaConclusao', 'notaMedia'));
    }

    public function edit(Profissional $profissional): View
    {
        $this->authorize('update', $profissional);

        $servicos = Servico::where('company_id', auth()->user()->empresa_id)->ativo()->orderBy('nome')->get();
        $profissional->load('servicos');

        return view('profissionais.edit', compact('profissional', 'servicos'));
    }

    public function update(UpdateProfissionalRequest $request, Profissional $profissional): JsonResponse|RedirectResponse
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

        if ($request->wantsJson()) {
            $profissional->loadCount('agendamentos');

            return response()->json([
                'success' => true,
                'profissional' => $profissional->only(['id', 'name', 'especialidade', 'comissao_pct', 'ativo', 'cor', 'phone', 'admissao', 'instagram', 'tiktok', 'facebook']),
                'agendamentos_count' => $profissional->agendamentos_count,
            ]);
        }

        return redirect()->route('profissionais.show', $profissional)
            ->with('success', 'Profissional atualizado com sucesso.');
    }

    public function uploadFoto(Request $request, Profissional $profissional): JsonResponse
    {
        $this->authorize('update', $profissional);

        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $companyId = auth()->user()->empresa_id;

        if ($profissional->foto_path) {
            Storage::disk('public')->delete($profissional->foto_path);
        }

        $path = $request->file('foto')->store("profissionais/{$companyId}", 'public');

        $profissional->update(['foto_path' => $path]);

        return response()->json([
            'foto_url' => Storage::disk('public')->url($path),
        ]);
    }

    public function deleteFoto(Profissional $profissional): Response
    {
        $this->authorize('update', $profissional);

        if ($profissional->foto_path) {
            Storage::disk('public')->delete($profissional->foto_path);
            $profissional->update(['foto_path' => null]);
        }

        return response()->noContent();
    }

    public function destroy(Profissional $profissional): RedirectResponse
    {
        $this->authorize('delete', $profissional);

        $profissional->delete();

        return redirect()->route('profissionais.index')
            ->with('success', "Profissional {$profissional->name} removido.");
    }
}
