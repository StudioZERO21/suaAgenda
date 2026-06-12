<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreServicoRequest;
use App\Http\Requests\UpdateServicoRequest;
use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\Cliente;
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

    public function categorias(): JsonResponse
    {
        $this->authorize('viewAny', Servico::class);

        $empresa = auth()->user()->empresa_id;

        $categorias = Servico::where('company_id', $empresa)
            ->whereNotNull('categoria')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria');

        return response()->json($categorias->values());
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

    public function semProfissional(): JsonResponse
    {
        $this->authorize('viewAny', Servico::class);

        $empresa = auth()->user()->empresa_id;

        $comProfissional = Servico::where('company_id', $empresa)
            ->has('profissionais')
            ->pluck('id');

        $servicos = Servico::where('company_id', $empresa)
            ->whereNotIn('id', $comProfissional)
            ->orderBy('nome')
            ->get(['id', 'nome', 'cor', 'duracao_minutos', 'preco', 'ativo'])
            ->map(fn (Servico $s) => [
                'id' => $s->id,
                'nome' => $s->nome,
                'cor' => $s->cor ?? '#999999',
                'duracao_minutos' => (int) $s->duracao_minutos,
                'preco' => (float) $s->preco,
                'ativo' => $s->ativo,
            ]);

        return response()->json(['total' => $servicos->count(), 'items' => $servicos]);
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
            ->whereNotIn('status', Agendamento::STATUSES_INATIVOS)
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

    public function categoria(Request $request, Servico $servico): JsonResponse
    {
        $this->authorize('update', $servico);

        $request->validate(['categoria' => ['required', 'string', 'max:60']]);

        $servico->update(['categoria' => $request->input('categoria')]);

        return response()->json([
            'categoria' => $servico->categoria,
            'updated_at' => $servico->updated_at->toIso8601String(),
        ]);
    }

    public function descricao(Request $request, Servico $servico): JsonResponse
    {
        $this->authorize('update', $servico);

        $request->validate(['descricao' => ['nullable', 'string', 'max:500']]);

        $servico->update(['descricao' => $request->input('descricao', '')]);

        return response()->json([
            'descricao' => $servico->descricao ?? '',
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

    public function proximos(Request $request, Servico $servico): JsonResponse
    {
        $this->authorize('view', $servico);

        $limite = min((int) $request->input('limite', 5), 20);

        $agendamentos = $servico->agendamentos()
            ->whereIn('status', [Agendamento::STATUS_PENDENTE, Agendamento::STATUS_CONFIRMADO])
            ->where('data_hora', '>=', now())
            ->with(['cliente:id,name,phone', 'profissional:id,name,cor'])
            ->orderBy('data_hora')
            ->limit($limite)
            ->get()
            ->map(fn (Agendamento $ag) => [
                'id' => $ag->id,
                'data_hora' => $ag->data_hora->toIso8601String(),
                'status' => $ag->status,
                'valor' => (float) $ag->valor,
                'cliente' => $ag->cliente ? ['id' => $ag->cliente->id, 'name' => $ag->cliente->name, 'phone' => $ag->cliente->phone ?? ''] : null,
                'profissional' => $ag->profissional ? ['id' => $ag->profissional->id, 'name' => $ag->profissional->name, 'cor' => $ag->profissional->cor ?? '#999999'] : null,
            ]);

        return response()->json(['total' => $agendamentos->count(), 'items' => $agendamentos]);
    }

    public function avaliacoes(Request $request, Servico $servico): JsonResponse
    {
        $this->authorize('view', $servico);

        $limite = min((int) $request->input('limite', 10), 50);

        $avaliacoes = Avaliacao::where('company_id', auth()->user()->empresa_id)
            ->whereHas('agendamento', fn ($q) => $q->where('servico_id', $servico->id))
            ->with(['agendamento.cliente:id,name', 'agendamento.profissional:id,name'])
            ->orderByDesc('created_at')
            ->limit($limite)
            ->get()
            ->map(fn (Avaliacao $av) => [
                'id' => $av->id,
                'nota' => $av->nota,
                'comentario' => $av->comentario ?? '',
                'data' => $av->created_at->toDateString(),
                'cliente_nome' => $av->agendamento?->cliente?->name ?? 'Cliente removido',
                'profissional_nome' => $av->agendamento?->profissional?->name ?? 'Profissional removido',
            ]);

        $notaMedia = $avaliacoes->count() > 0
            ? round($avaliacoes->avg('nota'), 2)
            : null;

        return response()->json([
            'total_avaliacoes' => $avaliacoes->count(),
            'nota_media' => $notaMedia,
            'items' => $avaliacoes->values(),
        ]);
    }

    public function nome(Request $request, Servico $servico): JsonResponse
    {
        $this->authorize('update', $servico);

        $request->validate([
            'nome' => ['required', 'string', 'max:100'],
        ]);

        $servico->update(['nome' => $request->input('nome')]);

        return response()->json([
            'nome' => $servico->nome,
            'updated_at' => $servico->updated_at->toIso8601String(),
        ]);
    }

    public function tempoMedio(Servico $servico): JsonResponse
    {
        $this->authorize('view', $servico);

        $agendamentos = $servico->agendamentos()
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->get(['duracao', 'valor']);

        $total = $agendamentos->count();

        return response()->json([
            'servico_id' => $servico->id,
            'servico_nome' => $servico->nome,
            'duracao_configurada' => (int) $servico->duracao_minutos,
            'total_realizados' => $total,
            'duracao_media' => $total > 0 ? round($agendamentos->avg('duracao'), 1) : null,
            'valor_medio' => $total > 0 ? round((float) $agendamentos->avg('valor'), 2) : null,
            'valor_total' => (float) $agendamentos->sum('valor'),
        ]);
    }

    public function topProfissionais(Request $request, Servico $servico): JsonResponse
    {
        $this->authorize('view', $servico);

        $limite = min((int) $request->input('limite', 10), 30);

        $profissionais = $servico->agendamentos()
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->with('profissional:id,name,cor,especialidade')
            ->get(['profissional_id', 'valor'])
            ->groupBy('profissional_id')
            ->map(function ($items) {
                $profissional = $items->first()->profissional;

                return [
                    'profissional_id' => $profissional?->id ?? '',
                    'profissional_nome' => $profissional?->name ?? 'Sem profissional',
                    'profissional_cor' => $profissional?->cor ?? '#999999',
                    'especialidade' => $profissional?->especialidade ?? '',
                    'total_realizados' => $items->count(),
                    'receita_total' => (float) $items->sum('valor'),
                ];
            })
            ->sortByDesc('total_realizados')
            ->take($limite)
            ->values();

        return response()->json([
            'servico_id' => $servico->id,
            'servico_nome' => $servico->nome,
            'total' => $profissionais->count(),
            'items' => $profissionais,
        ]);
    }

    public function clientesUnicos(Request $request, Servico $servico): JsonResponse
    {
        $this->authorize('view', $servico);

        $limite = min((int) $request->input('limite', 20), 100);
        $dias = $request->input('dias');

        $clienteIds = $servico->agendamentos()
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->when($dias !== null, fn ($q) => $q->where('data_hora', '>=', now()->subDays((int) $dias)))
            ->whereNotNull('cliente_id')
            ->distinct('cliente_id')
            ->pluck('cliente_id');

        $agPorCliente = $servico->agendamentos()
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->when($dias !== null, fn ($q) => $q->where('data_hora', '>=', now()->subDays((int) $dias)))
            ->whereIn('cliente_id', $clienteIds)
            ->get(['cliente_id', 'valor', 'data_hora'])
            ->groupBy('cliente_id');

        $clientes = Cliente::whereIn('id', $clienteIds)
            ->orderBy('name')
            ->limit($limite)
            ->get(['id', 'name', 'phone', 'ativo'])
            ->map(function (Cliente $c) use ($agPorCliente): array {
                $ags = $agPorCliente->get($c->id, collect());

                return [
                    'cliente_id' => $c->id,
                    'cliente_nome' => $c->name,
                    'phone' => $c->phone ?? '',
                    'ativo' => (bool) $c->ativo,
                    'total_agendamentos' => $ags->count(),
                    'receita_total' => (float) $ags->sum('valor'),
                    'ultima_visita' => $ags->sortByDesc('data_hora')->first()?->data_hora->toIso8601String(),
                ];
            });

        return response()->json([
            'servico_id' => $servico->id,
            'servico_nome' => $servico->nome,
            'total' => $clientes->count(),
            'items' => $clientes->values(),
        ]);
    }

    public function taxaCancelamento(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Servico::class);

        $empresa = auth()->user()->empresa_id;
        $dias = max(1, min(365, (int) $request->input('dias', 30)));

        $servicos = Servico::where('company_id', $empresa)
            ->orderBy('nome')
            ->get(['id', 'nome', 'cor', 'preco', 'ativo']);

        $agendamentos = Agendamento::where('company_id', $empresa)
            ->where('data_hora', '>=', now()->subDays($dias)->startOfDay())
            ->whereIn('servico_id', $servicos->pluck('id'))
            ->get(['servico_id', 'status'])
            ->groupBy('servico_id');

        $items = $servicos->map(function (Servico $s) use ($agendamentos): array {
            $ags = $agendamentos->get($s->id, collect());
            $total = $ags->count();
            $cancelados = $ags->where('status', Agendamento::STATUS_CANCELADO)->count();
            $taxa = $total > 0 ? round($cancelados / $total * 100, 1) : null;

            return [
                'servico_id' => $s->id,
                'servico_nome' => $s->nome,
                'cor' => $s->cor ?? '#999999',
                'preco' => (float) $s->preco,
                'ativo' => (bool) $s->ativo,
                'total_agendamentos' => $total,
                'cancelados' => $cancelados,
                'taxa_cancelamento_pct' => $taxa,
            ];
        })->filter(fn (array $r) => $r['total_agendamentos'] > 0)
            ->sortByDesc('taxa_cancelamento_pct')
            ->values();

        return response()->json([
            'periodo_dias' => $dias,
            'total' => $items->count(),
            'items' => $items,
        ]);
    }

    public function semAgendamento(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Servico::class);

        $empresa = auth()->user()->empresa_id;
        $dias = max(1, min(365, (int) $request->input('dias', 30)));
        $apenasAtivos = filter_var($request->input('apenas_ativos', true), FILTER_VALIDATE_BOOLEAN);

        $servicos = Servico::where('company_id', $empresa)
            ->when($apenasAtivos, fn ($q) => $q->where('ativo', true))
            ->orderBy('nome')
            ->get(['id', 'nome', 'cor', 'preco', 'duracao_minutos', 'ativo', 'created_at']);

        $comAgendamento = Agendamento::where('company_id', $empresa)
            ->where('data_hora', '>=', now()->subDays($dias)->startOfDay())
            ->whereNotIn('status', Agendamento::STATUSES_INATIVOS)
            ->pluck('servico_id')
            ->unique();

        $semAgendamento = $servicos->whereNotIn('id', $comAgendamento)->values();

        $items = $semAgendamento->map(fn (Servico $s) => [
            'id' => $s->id,
            'nome' => $s->nome,
            'cor' => $s->cor ?? '#999999',
            'preco' => (float) $s->preco,
            'duracao_minutos' => (int) $s->duracao_minutos,
            'ativo' => (bool) $s->ativo,
            'criado_em' => $s->created_at->toDateString(),
        ]);

        return response()->json([
            'periodo_dias' => $dias,
            'total_servicos' => $servicos->count(),
            'sem_agendamento' => $items->count(),
            'items' => $items->values(),
        ]);
    }

    public function duracaoReal(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Servico::class);

        $empresa = auth()->user()->empresa_id;
        $minExecucoes = max(1, (int) $request->input('min_execucoes', 3));
        $dias = $request->input('periodo_dias');

        $servicos = Servico::where('company_id', $empresa)
            ->orderBy('nome')
            ->get(['id', 'nome', 'duracao_minutos']);

        $agStats = Agendamento::where('company_id', $empresa)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->when($dias !== null, fn ($q) => $q->where('data_hora', '>=', now()->subDays((int) $dias)))
            ->selectRaw('servico_id, COUNT(*) as total, AVG(duracao) as duracao_media')
            ->groupBy('servico_id')
            ->get()
            ->keyBy('servico_id');

        $items = $servicos->map(function (Servico $s) use ($agStats, $minExecucoes): ?array {
            $stat = $agStats->get($s->id);
            $total = (int) ($stat?->total ?? 0);

            if ($total < $minExecucoes) {
                return null;
            }

            $duracaoMedia = round((float) $stat->duracao_media, 1);
            $diferenca = round($duracaoMedia - $s->duracao_minutos, 1);

            return [
                'servico_id' => $s->id,
                'servico_nome' => $s->nome,
                'duracao_configurada' => (int) $s->duracao_minutos,
                'duracao_media_real' => $duracaoMedia,
                'diferenca_minutos' => $diferenca,
                'total_execucoes' => $total,
            ];
        })->filter()->sortByDesc('diferenca_minutos')->values();

        return response()->json([
            'periodo_dias' => $dias !== null ? (int) $dias : null,
            'min_execucoes' => $minExecucoes,
            'total' => $items->count(),
            'items' => $items,
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
