<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCargoRequest;
use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\Cargo;
use App\Support\SaDemoData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CargoController extends Controller
{
    public function listar(): JsonResponse
    {
        $companyId = auth()->user()->empresa_id;

        $cargos = Cargo::where('company_id', $companyId)
            ->withCount('profissionais as membros')
            ->orderBy('nome')
            ->get()
            ->map(fn (Cargo $c): array => $this->toJson($c));

        return response()->json($cargos->values());
    }

    public function index(): View
    {
        $companyId = auth()->user()->empresa_id;

        $cargos = Cargo::where('company_id', $companyId)
            ->withCount('profissionais as membros')
            ->orderBy('nome')
            ->get()
            ->map(fn (Cargo $c): array => $this->toJson($c));

        return view('cargos.index', [
            'cargosJson' => $cargos,
            'niveis' => SaDemoData::niveisPermissao(),
            'cores' => SaDemoData::coresCargo(),
        ]);
    }

    public function store(StoreCargoRequest $request): JsonResponse
    {
        $cargo = Cargo::create([
            'company_id' => auth()->user()->empresa_id,
            'nome' => $request->nome,
            'nivel' => $request->nivel,
            'cor' => $request->cor ?? '#6b7280',
            'descricao' => $request->descricao,
            'comissao_pct' => $request->comissao ?? null,
        ]);

        $cargo->loadCount('profissionais as membros');

        return response()->json($this->toJson($cargo), 201);
    }

    public function update(StoreCargoRequest $request, Cargo $cargo): JsonResponse
    {
        abort_if($cargo->company_id !== auth()->user()->empresa_id, 403);

        $cargo->update([
            'nome' => $request->nome,
            'nivel' => $request->nivel,
            'cor' => $request->cor ?? $cargo->cor,
            'descricao' => $request->descricao,
            'comissao_pct' => $request->comissao ?? null,
        ]);

        $cargo->loadCount('profissionais as membros');

        return response()->json($this->toJson($cargo->fresh()));
    }

    public function cor(Request $request, Cargo $cargo): JsonResponse
    {
        abort_if($cargo->company_id !== auth()->user()->empresa_id, 403);

        $request->validate([
            'cor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $cargo->update(['cor' => $request->input('cor')]);

        return response()->json([
            'cor' => $cargo->cor,
            'updated_at' => $cargo->updated_at->toIso8601String(),
        ]);
    }

    public function detalhe(Cargo $cargo): JsonResponse
    {
        abort_if($cargo->company_id !== auth()->user()->empresa_id, 403);

        $cargo->loadCount('profissionais as membros');

        return response()->json([
            'id' => $cargo->id,
            'nome' => $cargo->nome,
            'nivel' => $cargo->nivel,
            'cor' => $cargo->cor,
            'descricao' => $cargo->descricao ?? '',
            'comissao' => (float) ($cargo->comissao_pct ?? 0),
            'membros' => (int) ($cargo->membros ?? 0),
            'created_at' => $cargo->created_at->toIso8601String(),
            'updated_at' => $cargo->updated_at->toIso8601String(),
        ]);
    }

    public function profissionais(Cargo $cargo): JsonResponse
    {
        abort_if($cargo->company_id !== auth()->user()->empresa_id, 403);

        $profissionais = $cargo->profissionais()
            ->orderBy('name')
            ->get(['id', 'name', 'ativo', 'especialidade', 'cor'])
            ->map(fn (object $p): array => [
                'id' => $p->id,
                'name' => $p->name,
                'ativo' => (bool) $p->ativo,
                'especialidade' => $p->especialidade ?? '',
                'cor' => $p->cor ?? '#999999',
            ]);

        return response()->json([
            'cargo_id' => $cargo->id,
            'cargo_nome' => $cargo->nome,
            'total' => $profissionais->count(),
            'items' => $profissionais->values(),
        ]);
    }

    public function estatisticas(Cargo $cargo): JsonResponse
    {
        abort_if($cargo->company_id !== auth()->user()->empresa_id, 403);

        $profissionais = $cargo->profissionais()->get(['id', 'name', 'ativo']);
        $profIds = $profissionais->pluck('id');

        $agendamentosMes = Agendamento::whereIn('profissional_id', $profIds)
            ->where('company_id', $cargo->company_id)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->whereMonth('data_hora', now()->month)
            ->whereYear('data_hora', now()->year)
            ->get(['profissional_id', 'valor']);

        $avaliacoes = Avaliacao::where('avaliacoes.company_id', $cargo->company_id)
            ->join('agendamentos', 'avaliacoes.agendamento_id', '=', 'agendamentos.id')
            ->whereIn('agendamentos.profissional_id', $profIds)
            ->get(['avaliacoes.nota']);

        $mediaAvaliacao = $avaliacoes->count() > 0 ? round($avaliacoes->avg('nota'), 1) : null;

        return response()->json([
            'cargo_id' => $cargo->id,
            'cargo_nome' => $cargo->nome,
            'total_profissionais' => $profissionais->count(),
            'ativos' => $profissionais->where('ativo', true)->count(),
            'inativos' => $profissionais->where('ativo', false)->count(),
            'agendamentos_mes' => $agendamentosMes->count(),
            'receita_mes' => round((float) $agendamentosMes->sum('valor'), 2),
            'total_avaliacoes' => $avaliacoes->count(),
            'media_avaliacao' => $mediaAvaliacao,
        ]);
    }

    public function descricao(Request $request, Cargo $cargo): JsonResponse
    {
        abort_if($cargo->company_id !== auth()->user()->empresa_id, 403);

        $request->validate(['descricao' => ['nullable', 'string', 'max:500']]);

        $cargo->update(['descricao' => $request->input('descricao')]);

        return response()->json([
            'descricao' => $cargo->descricao ?? '',
            'updated_at' => $cargo->updated_at->toIso8601String(),
        ]);
    }

    public function nome(Request $request, Cargo $cargo): JsonResponse
    {
        abort_if($cargo->company_id !== auth()->user()->empresa_id, 403);

        $request->validate(['nome' => ['required', 'string', 'max:100']]);

        $cargo->update(['nome' => $request->input('nome')]);

        return response()->json([
            'nome' => $cargo->nome,
            'updated_at' => $cargo->updated_at->toIso8601String(),
        ]);
    }

    public function nivel(Request $request, Cargo $cargo): JsonResponse
    {
        abort_if($cargo->company_id !== auth()->user()->empresa_id, 403);

        $request->validate(['nivel' => ['required', 'string', 'max:50']]);

        $cargo->update(['nivel' => $request->input('nivel')]);

        return response()->json([
            'nivel' => $cargo->nivel,
            'updated_at' => $cargo->updated_at->toIso8601String(),
        ]);
    }

    public function comissao(Request $request, Cargo $cargo): JsonResponse
    {
        abort_if($cargo->company_id !== auth()->user()->empresa_id, 403);
        abort_if(! auth()->user()->hasRole('admin_empresa'), 403);

        $request->validate(['comissao' => ['required', 'numeric', 'min:0', 'max:100']]);

        $cargo->update(['comissao_pct' => $request->input('comissao')]);
        $cargo->refresh();

        return response()->json([
            'comissao' => (float) ($cargo->comissao_pct ?? 0),
            'updated_at' => $cargo->updated_at->toIso8601String(),
        ]);
    }

    public function destroy(Cargo $cargo): Response
    {
        abort_if($cargo->company_id !== auth()->user()->empresa_id, 403);

        $cargo->delete();

        return response()->noContent();
    }

    private function toJson(Cargo $c): array
    {
        return [
            'id' => $c->id,
            'nome' => $c->nome,
            'nivel' => $c->nivel,
            'cor' => $c->cor,
            'descricao' => $c->descricao,
            'comissao' => (float) ($c->comissao_pct ?? 0),
            'membros' => (int) ($c->membros ?? 0),
        ];
    }
}
