<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreProfissionalRequest;
use App\Http\Requests\UpdateProfissionalRequest;
use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\BloqueioAgenda;
use App\Models\Cargo;
use App\Models\HorarioTrabalho;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\Venda;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function exportarCsv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Profissional::class);

        $empresa = auth()->user()->empresa_id;

        $profissionais = Profissional::where('company_id', $empresa)
            ->with(['servicos:id,nome', 'agendamentos' => fn ($q) => $q->latest('data_hora')->limit(1)])
            ->withCount('agendamentos')
            ->orderBy('name')
            ->get();

        return response()->streamDownload(function () use ($profissionais): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nome', 'Especialidade', 'Telefone', 'Comissão (%)', 'Serviços', 'Total Agendamentos', 'Último Agendamento', 'Status'], ';');

            foreach ($profissionais as $p) {
                $servicos = $p->servicos->pluck('nome')->join(', ');
                $ultimo = $p->agendamentos->first()?->data_hora?->format('d/m/Y H:i') ?? '';
                fputcsv($out, [
                    $p->name,
                    $p->especialidade ?? '',
                    $p->phone ?? '',
                    number_format((float) ($p->comissao_pct ?? 0), 2, '.', ''),
                    $servicos,
                    $p->agendamentos_count,
                    $ultimo,
                    $p->ativo ? 'Ativo' : 'Inativo',
                ], ';');
            }

            fclose($out);
        }, 'profissionais-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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

    public function buscar(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Profissional::class);

        $q = trim((string) $request->input('q', ''));
        $empresa = auth()->user()->empresa_id;

        if ($q === '') {
            return response()->json([]);
        }

        $profissionais = Profissional::where('company_id', $empresa)
            ->where(function ($query) use ($q): void {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('especialidade', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'especialidade', 'phone', 'ativo'])
            ->map(fn (Profissional $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'especialidade' => $p->especialidade ?? '',
                'phone' => $p->phone ?? '',
                'ativo' => $p->ativo,
            ]);

        return response()->json($profissionais);
    }

    public function toggle(Profissional $profissional): JsonResponse
    {
        $this->authorize('update', $profissional);

        $profissional->update(['ativo' => ! $profissional->ativo]);

        return response()->json(['ativo' => $profissional->ativo]);
    }

    public function disponibilidade(Request $request, Profissional $profissional): JsonResponse
    {
        $this->authorize('view', $profissional);

        $request->validate([
            'data' => ['required', 'date'],
            'duracao' => ['nullable', 'integer', 'min:5', 'max:480'],
        ]);

        $data = Carbon::parse($request->input('data'))->startOfDay();
        $duracao = max(5, (int) $request->input('duracao', 30));
        $diaSemana = (int) $data->format('w');

        if (BloqueioAgenda::blockedOn($profissional->id, $data->format('Y-m-d'))) {
            return response()->json(['slots' => [], 'bloqueado' => true]);
        }

        $horario = HorarioTrabalho::where('profissional_id', $profissional->id)
            ->where('dia_semana', $diaSemana)
            ->where('ativo', true)
            ->first();

        if (! $horario) {
            return response()->json(['slots' => [], 'bloqueado' => false]);
        }

        $ocupados = Agendamento::where('profissional_id', $profissional->id)
            ->whereDate('data_hora', $data)
            ->whereIn('status', ['pendente', 'confirmado', 'em_atendimento'])
            ->pluck('data_hora')
            ->map(fn ($dt) => Carbon::parse($dt)->format('H:i'));

        $inicio = Carbon::parse($data->format('Y-m-d').' '.$horario->hora_inicio);
        $fim = Carbon::parse($data->format('Y-m-d').' '.$horario->hora_fim);

        $slots = [];
        $current = $inicio->copy();
        while ($current->copy()->addMinutes($duracao)->lte($fim)) {
            $hora = $current->format('H:i');
            $slots[] = ['hora' => $hora, 'disponivel' => ! $ocupados->contains($hora)];
            $current->addMinutes($duracao);
        }

        return response()->json(['slots' => $slots, 'bloqueado' => false]);
    }

    public function servicos(Profissional $profissional): JsonResponse
    {
        $this->authorize('view', $profissional);

        $servicos = $profissional->servicos()
            ->where('ativo', true)
            ->orderBy('nome')
            ->get(['servicos.id', 'nome', 'cor', 'duracao_minutos', 'preco'])
            ->map(fn (Servico $s) => [
                'id' => $s->id,
                'nome' => $s->nome,
                'cor' => $s->cor ?? '#999999',
                'duracao_minutos' => (int) $s->duracao_minutos,
                'preco' => (float) $s->preco,
            ]);

        return response()->json($servicos);
    }

    public function agendamentos(Request $request, Profissional $profissional): JsonResponse
    {
        $this->authorize('view', $profissional);

        $perPage = min((int) $request->input('per_page', 10), 50);

        $paginado = $profissional->agendamentos()
            ->with(['servico:id,nome,cor', 'cliente:id,name,phone'])
            ->orderByDesc('data_hora')
            ->paginate($perPage);

        return response()->json([
            'total' => $paginado->total(),
            'per_page' => $paginado->perPage(),
            'page' => $paginado->currentPage(),
            'data' => $paginado->map(fn ($ag) => [
                'id' => $ag->id,
                'data_hora' => $ag->data_hora->toIso8601String(),
                'cliente_nome' => $ag->cliente?->name ?? '',
                'cliente_phone' => $ag->cliente?->phone ?? '',
                'servico_nome' => $ag->servico?->nome ?? '',
                'servico_cor' => $ag->servico?->cor ?? '#999999',
                'status' => $ag->status,
                'valor' => (float) $ag->valor,
                'duracao' => (int) $ag->duracao,
            ])->values(),
        ]);
    }

    public function stats(Request $request, Profissional $profissional): JsonResponse
    {
        $this->authorize('view', $profissional);

        $preset = $request->input('periodo', '30d');
        $hoje = Carbon::today();

        [$inicio, $fim] = match ($preset) {
            '7d' => [$hoje->copy()->subDays(6), $hoje],
            '3m' => [$hoje->copy()->subMonths(3), $hoje],
            'mes' => [$hoje->copy()->startOfMonth(), $hoje->copy()->endOfMonth()],
            default => [$hoje->copy()->subDays(29), $hoje],
        };

        $base = Agendamento::where('profissional_id', $profissional->id)
            ->whereBetween('data_hora', [$inicio->startOfDay(), $fim->copy()->endOfDay()]);

        $total = (clone $base)->count();
        $finalizados = (clone $base)->where('status', Agendamento::STATUS_FINALIZADO)->count();
        $receita = (float) (clone $base)->where('status', Agendamento::STATUS_FINALIZADO)->sum('valor');
        $notaMedia = round((float) Avaliacao::whereHas('agendamento', fn ($q) => $q->where('profissional_id', $profissional->id)
            ->whereBetween('data_hora', [$inicio->startOfDay(), $fim->copy()->endOfDay()]))->avg('nota') ?? 0.0, 2);

        return response()->json([
            'profissional' => $profissional->name,
            'periodo' => $preset,
            'total' => $total,
            'finalizados' => $finalizados,
            'receita' => $receita,
            'nota_media' => $notaMedia,
            'taxa_conclusao' => $total > 0 ? round($finalizados / $total * 100, 1) : 0.0,
        ]);
    }

    public function ranking(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Profissional::class);

        $empresa = auth()->user()->empresa_id;
        $preset = $request->input('preset', '30d');
        $hoje = Carbon::today();

        [$inicio, $fim] = match ($preset) {
            '7d' => [$hoje->copy()->subDays(6), $hoje],
            '3m' => [$hoje->copy()->subMonths(3), $hoje],
            'mes' => [$hoje->copy()->startOfMonth(), $hoje->copy()->endOfMonth()],
            default => [$hoje->copy()->subDays(29), $hoje],
        };

        $profissionais = Profissional::where('company_id', $empresa)
            ->where('ativo', true)
            ->orderBy('name')
            ->get(['id', 'name', 'especialidade', 'cor']);

        $agFinalizados = Agendamento::where('company_id', $empresa)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->whereBetween('data_hora', [$inicio->startOfDay(), $fim->copy()->endOfDay()])
            ->get(['profissional_id', 'valor'])
            ->groupBy('profissional_id');

        $rows = $profissionais->map(function (Profissional $prof) use ($agFinalizados): array {
            $items = $agFinalizados->get($prof->id, collect());
            $receita = (float) $items->sum('valor');

            return [
                'profissional_id' => $prof->id,
                'profissional_nome' => $prof->name,
                'especialidade' => $prof->especialidade ?? '',
                'cor' => $prof->cor ?? '#999999',
                'finalizados' => $items->count(),
                'receita_total' => $receita,
            ];
        })->sortByDesc('receita_total')->values();

        return response()->json($rows);
    }

    public function detalhe(Profissional $profissional): JsonResponse
    {
        $this->authorize('view', $profissional);

        $profissional->load(['servicos' => fn ($q) => $q->where('ativo', true)->orderBy('nome')]);

        $mesInicio = Carbon::today()->startOfMonth();
        $mesFim = Carbon::today()->endOfMonth();

        $agsMes = Agendamento::where('profissional_id', $profissional->id)
            ->whereBetween('data_hora', [$mesInicio, $mesFim]);

        $totalMes = (clone $agsMes)->count();
        $finalizadosMes = (clone $agsMes)->where('status', Agendamento::STATUS_FINALIZADO)->count();
        $receitaMes = (float) (clone $agsMes)->where('status', Agendamento::STATUS_FINALIZADO)->sum('valor');
        $notaMedia = round(
            (float) (Avaliacao::whereHas('agendamento', fn ($q) => $q->where('profissional_id', $profissional->id))->avg('nota') ?? 0.0),
            2
        );

        return response()->json([
            'id' => $profissional->id,
            'name' => $profissional->name,
            'especialidade' => $profissional->especialidade ?? '',
            'phone' => $profissional->phone ?? '',
            'instagram' => $profissional->instagram ?? '',
            'cor' => $profissional->cor ?? '#999999',
            'comissao_pct' => (float) ($profissional->comissao_pct ?? 0),
            'ativo' => $profissional->ativo,
            'foto_url' => $profissional->foto_path ? Storage::disk('public')->url($profissional->foto_path) : null,
            'servicos' => $profissional->servicos->map(fn (Servico $s) => [
                'id' => $s->id,
                'nome' => $s->nome,
                'cor' => $s->cor ?? '#999999',
                'duracao_minutos' => (int) $s->duracao_minutos,
                'preco' => (float) $s->preco,
            ])->values(),
            'stats_mes' => [
                'total' => $totalMes,
                'finalizados' => $finalizadosMes,
                'receita' => $receitaMes,
                'taxa_conclusao' => $totalMes > 0 ? round($finalizadosMes / $totalMes * 100, 1) : 0.0,
                'nota_media' => $notaMedia,
            ],
        ]);
    }

    public function comissao(Request $request, Profissional $profissional): JsonResponse
    {
        $this->authorize('update', $profissional);

        $request->validate([
            'comissao_pct' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $profissional->update(['comissao_pct' => $request->input('comissao_pct')]);

        return response()->json([
            'comissao_pct' => (float) $profissional->comissao_pct,
            'updated_at' => $profissional->updated_at->toIso8601String(),
        ]);
    }

    public function cor(Request $request, Profissional $profissional): JsonResponse
    {
        $this->authorize('update', $profissional);

        $request->validate([
            'cor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $profissional->update(['cor' => $request->input('cor')]);

        return response()->json([
            'cor' => $profissional->cor,
            'updated_at' => $profissional->updated_at->toIso8601String(),
        ]);
    }

    public function especialidade(Request $request, Profissional $profissional): JsonResponse
    {
        $this->authorize('update', $profissional);

        $request->validate(['especialidade' => ['nullable', 'string', 'max:100']]);

        $profissional->update(['especialidade' => $request->input('especialidade', '')]);

        return response()->json([
            'especialidade' => $profissional->especialidade ?? '',
            'updated_at' => $profissional->updated_at->toIso8601String(),
        ]);
    }

    public function contato(Request $request, Profissional $profissional): JsonResponse
    {
        $this->authorize('update', $profissional);

        $request->validate([
            'phone' => ['nullable', 'string', 'max:20'],
            'instagram' => ['nullable', 'string', 'max:60'],
            'tiktok' => ['nullable', 'string', 'max:60'],
            'facebook' => ['nullable', 'string', 'max:60'],
        ]);

        $profissional->update($request->only('phone', 'instagram', 'tiktok', 'facebook'));

        return response()->json([
            'phone' => $profissional->phone ?? '',
            'instagram' => $profissional->instagram ?? '',
            'tiktok' => $profissional->tiktok ?? '',
            'facebook' => $profissional->facebook ?? '',
            'updated_at' => $profissional->updated_at->toIso8601String(),
        ]);
    }

    public function admissao(Request $request, Profissional $profissional): JsonResponse
    {
        $this->authorize('update', $profissional);

        $request->validate([
            'admissao' => ['nullable', 'date', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);

        $profissional->update(['admissao' => $request->input('admissao')]);

        return response()->json([
            'admissao' => $profissional->admissao?->format('Y-m-d'),
            'updated_at' => $profissional->updated_at->toIso8601String(),
        ]);
    }

    public function proximos(Request $request, Profissional $profissional): JsonResponse
    {
        $this->authorize('view', $profissional);

        $limite = min((int) $request->input('limite', 5), 20);

        $agendamentos = $profissional->agendamentos()
            ->whereIn('status', [Agendamento::STATUS_PENDENTE, Agendamento::STATUS_CONFIRMADO])
            ->where('data_hora', '>=', now())
            ->with(['cliente:id,name,phone', 'servico:id,nome,cor'])
            ->orderBy('data_hora')
            ->limit($limite)
            ->get()
            ->map(fn (Agendamento $ag) => [
                'id' => $ag->id,
                'data_hora' => $ag->data_hora->toIso8601String(),
                'status' => $ag->status,
                'valor' => (float) $ag->valor,
                'cliente' => $ag->cliente ? ['id' => $ag->cliente->id, 'name' => $ag->cliente->name, 'phone' => $ag->cliente->phone ?? ''] : null,
                'servico' => $ag->servico ? ['id' => $ag->servico->id, 'nome' => $ag->servico->nome, 'cor' => $ag->servico->cor ?? '#999999'] : null,
            ]);

        return response()->json(['total' => $agendamentos->count(), 'items' => $agendamentos]);
    }

    public function avaliacoes(Request $request, Profissional $profissional): JsonResponse
    {
        $this->authorize('view', $profissional);

        $limite = min((int) $request->input('limite', 10), 50);

        $avaliacoes = Avaliacao::where('company_id', auth()->user()->empresa_id)
            ->whereHas('agendamento', fn ($q) => $q->where('profissional_id', $profissional->id))
            ->with(['agendamento.cliente:id,name', 'agendamento.servico:id,nome'])
            ->orderByDesc('created_at')
            ->limit($limite)
            ->get()
            ->map(fn (Avaliacao $av) => [
                'id' => $av->id,
                'nota' => $av->nota,
                'comentario' => $av->comentario ?? '',
                'data' => $av->created_at->toDateString(),
                'cliente_nome' => $av->agendamento?->cliente?->name ?? 'Cliente removido',
                'servico_nome' => $av->agendamento?->servico?->nome ?? 'Serviço removido',
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

    public function vendas(Request $request, Profissional $profissional): JsonResponse
    {
        $this->authorize('view', $profissional);

        $limite = min((int) $request->input('limite', 10), 50);

        $vendas = Venda::where('company_id', auth()->user()->empresa_id)
            ->where('profissional_id', $profissional->id)
            ->with('itens.produto:id,nome')
            ->orderByDesc('created_at')
            ->limit($limite)
            ->get()
            ->map(fn (Venda $v) => [
                'id' => $v->id,
                'data' => $v->created_at->toDateString(),
                'total' => (float) $v->total,
                'metodo_pagamento' => $v->metodo_pagamento,
                'total_itens' => $v->itens->sum('qtd'),
            ]);

        return response()->json([
            'total_vendas' => $vendas->count(),
            'total_receita' => (float) $vendas->sum('total'),
            'items' => $vendas->values(),
        ]);
    }

    public function nome(Request $request, Profissional $profissional): JsonResponse
    {
        $this->authorize('update', $profissional);

        $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $profissional->update(['name' => $request->input('name')]);

        return response()->json([
            'name' => $profissional->name,
            'updated_at' => $profissional->updated_at->toIso8601String(),
        ]);
    }

    public function cargo(Request $request, Profissional $profissional): JsonResponse
    {
        $this->authorize('update', $profissional);

        $request->validate(['cargo_id' => ['nullable', 'uuid', 'exists:cargos,id']]);

        $cargoId = $request->input('cargo_id');

        if ($cargoId !== null) {
            $cargo = Cargo::where('id', $cargoId)
                ->where('company_id', auth()->user()->empresa_id)
                ->firstOrFail();
            $profissional->update(['cargo_id' => $cargo->id]);
        } else {
            $profissional->update(['cargo_id' => null]);
        }

        return response()->json([
            'cargo_id' => $profissional->cargo_id,
            'cargo_nome' => $profissional->cargo?->nome,
            'updated_at' => $profissional->updated_at->toIso8601String(),
        ]);
    }

    public function destroy(Profissional $profissional): RedirectResponse
    {
        $this->authorize('delete', $profissional);

        $profissional->delete();

        return redirect()->route('profissionais.index')
            ->with('success', "Profissional {$profissional->name} removido.");
    }
}
