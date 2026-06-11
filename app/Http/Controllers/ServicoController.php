<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreServicoRequest;
use App\Http\Requests\UpdateServicoRequest;
use App\Models\Agendamento;
use App\Models\Profissional;
use App\Models\Servico;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServicoController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Servico::class);

        $empresa = auth()->user()->empresa_id;

        $baseQuery = Servico::where('company_id', $empresa);

        $servicos = (clone $baseQuery)
            ->when($request->search, fn ($q) => $q->where('nome', 'like', "%{$request->search}%"))
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'ativos' => (clone $baseQuery)->where('ativo', true)->count(),
            'ticket_medio' => (float) ((clone $baseQuery)->avg('preco') ?? 0),
            'duracao_media' => (int) round((float) ((clone $baseQuery)->avg('duracao_minutos') ?? 0)),
        ];

        return view('servicos.index', compact('servicos', 'stats'));
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

    public function exportarCsv(): StreamedResponse
    {
        $this->authorize('viewAny', Servico::class);

        $empresa = auth()->user()->empresa_id;

        $servicos = Servico::where('company_id', $empresa)
            ->with('profissionais:id,name')
            ->withCount('agendamentos')
            ->orderBy('nome')
            ->get();

        return response()->streamDownload(function () use ($servicos): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nome', 'Duração (min)', 'Preço (R$)', 'Cor', 'Profissionais', 'Total Agendamentos', 'Status'], ';');

            foreach ($servicos as $s) {
                $profissionais = $s->profissionais->pluck('name')->join(', ');
                fputcsv($out, [
                    $s->nome,
                    $s->duracao_minutos,
                    number_format((float) $s->preco, 2, ',', '.'),
                    $s->cor ?? '',
                    $profissionais,
                    $s->agendamentos_count,
                    $s->ativo ? 'Ativo' : 'Inativo',
                ], ';');
            }

            fclose($out);
        }, 'servicos-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function ativos(): JsonResponse
    {
        $this->authorize('viewAny', Servico::class);

        $empresa = auth()->user()->empresa_id;

        $servicos = Servico::where('company_id', $empresa)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get(['id', 'nome', 'cor', 'duracao_minutos', 'preco'])
            ->map(fn (Servico $s) => [
                'id' => $s->id,
                'nome' => $s->nome,
                'cor' => $s->cor ?? '#999999',
                'duracao_minutos' => (int) $s->duracao_minutos,
                'preco' => (float) $s->preco,
            ]);

        return response()->json($servicos);
    }

    public function estatisticas(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Servico::class);

        $empresa = auth()->user()->empresa_id;
        $preset = $request->input('preset', '30d');
        $hoje = Carbon::today();

        [$inicio, $fim] = match ($preset) {
            '7d' => [$hoje->copy()->subDays(6), $hoje],
            '3m' => [$hoje->copy()->subMonths(3), $hoje],
            'mes' => [$hoje->copy()->startOfMonth(), $hoje->copy()->endOfMonth()],
            default => [$hoje->copy()->subDays(29), $hoje],
        };

        $servicos = Servico::where('company_id', $empresa)
            ->orderBy('nome')
            ->get(['id', 'nome', 'cor', 'preco', 'duracao_minutos']);

        $agFinalizados = Agendamento::where('company_id', $empresa)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->whereBetween('data_hora', [$inicio->startOfDay(), $fim->copy()->endOfDay()])
            ->get(['servico_id', 'valor'])
            ->groupBy('servico_id');

        $agTotal = Agendamento::where('company_id', $empresa)
            ->whereNotIn('status', [Agendamento::STATUS_CANCELADO])
            ->whereBetween('data_hora', [$inicio->startOfDay(), $fim->copy()->endOfDay()])
            ->get(['servico_id'])
            ->groupBy('servico_id');

        $rows = $servicos->map(function (Servico $s) use ($agFinalizados, $agTotal): array {
            $finalizados = $agFinalizados->get($s->id, collect());
            $total = $agTotal->get($s->id, collect());
            $receita = (float) $finalizados->sum('valor');

            return [
                'id' => $s->id,
                'nome' => $s->nome,
                'cor' => $s->cor ?? '#999999',
                'preco' => (float) $s->preco,
                'duracao_minutos' => (int) $s->duracao_minutos,
                'total_agendamentos' => $total->count(),
                'finalizados' => $finalizados->count(),
                'receita_total' => $receita,
                'ticket_medio' => $finalizados->count() > 0 ? round($receita / $finalizados->count(), 2) : 0.0,
            ];
        })->sortByDesc('receita_total')->values();

        return response()->json($rows);
    }

    public function buscar(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Servico::class);

        $q = trim((string) $request->input('q', ''));
        $empresa = auth()->user()->empresa_id;

        $query = Servico::where('company_id', $empresa)->ativo()->orderBy('nome');

        if ($q !== '') {
            $query->where('nome', 'like', "%{$q}%");
        }

        $servicos = $query->limit(15)
            ->get(['id', 'nome', 'cor', 'duracao_minutos', 'preco'])
            ->map(fn (Servico $s) => [
                'id' => $s->id,
                'nome' => $s->nome,
                'cor' => $s->cor ?? '#999999',
                'duracao_minutos' => (int) $s->duracao_minutos,
                'preco' => (float) $s->preco,
            ]);

        return response()->json($servicos);
    }

    public function toggle(Servico $servico): JsonResponse
    {
        $this->authorize('update', $servico);

        $servico->update(['ativo' => ! $servico->ativo]);

        return response()->json(['ativo' => $servico->ativo]);
    }

    public function preco(Request $request, Servico $servico): JsonResponse
    {
        $this->authorize('update', $servico);

        $request->validate([
            'preco' => ['required', 'numeric', 'min:0'],
        ]);

        $servico->update(['preco' => $request->input('preco')]);

        return response()->json([
            'preco' => (float) $servico->preco,
            'updated_at' => $servico->updated_at->toIso8601String(),
        ]);
    }

    public function duracao(Request $request, Servico $servico): JsonResponse
    {
        $this->authorize('update', $servico);

        $request->validate([
            'duracao_minutos' => ['required', 'integer', 'min:5', 'max:480'],
        ]);

        $servico->update(['duracao_minutos' => $request->integer('duracao_minutos')]);

        return response()->json([
            'duracao_minutos' => $servico->duracao_minutos,
            'updated_at' => $servico->updated_at->toIso8601String(),
        ]);
    }

    public function cor(Request $request, Servico $servico): JsonResponse
    {
        $this->authorize('update', $servico);

        $request->validate(['cor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/']]);

        $servico->update(['cor' => $request->input('cor')]);

        return response()->json([
            'cor' => $servico->cor,
            'updated_at' => $servico->updated_at->toIso8601String(),
        ]);
    }

    public function profissionais(Servico $servico): JsonResponse
    {
        $this->authorize('view', $servico);

        $profissionais = $servico->profissionais()
            ->where('ativo', true)
            ->orderBy('name')
            ->get(['profissionais.id', 'name', 'especialidade', 'cor', 'foto_path'])
            ->map(fn (Profissional $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'especialidade' => $p->especialidade ?? '',
                'cor' => $p->cor ?? '#999999',
            ]);

        return response()->json($profissionais);
    }

    public function detalhe(Servico $servico): JsonResponse
    {
        $this->authorize('view', $servico);

        $servico->load([
            'profissionais' => fn ($q) => $q->where('ativo', true)->orderBy('name'),
        ]);

        return response()->json([
            'id' => $servico->id,
            'nome' => $servico->nome,
            'descricao' => $servico->descricao ?? '',
            'cor' => $servico->cor ?? '#999999',
            'duracao_minutos' => (int) $servico->duracao_minutos,
            'preco' => (float) $servico->preco,
            'ativo' => $servico->ativo,
            'profissionais' => $servico->profissionais->map(fn (Profissional $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'especialidade' => $p->especialidade ?? '',
                'cor' => $p->cor ?? '#999999',
            ])->values(),
        ]);
    }

    public function destroy(Servico $servico): RedirectResponse
    {
        $this->authorize('delete', $servico);

        $servico->delete();

        return redirect()->route('servicos.index')
            ->with('success', "Serviço {$servico->nome} removido.");
    }
}
