<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\Cliente;
use App\Models\ClienteFoto;
use App\Models\Venda;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Cliente::class);

        $empresa = auth()->user()->empresa_id;

        $clientes = Cliente::where('company_id', $empresa)
            ->with('fotos')
            ->withCount('agendamentos')
            ->orderBy('name')
            ->get();

        $clientesJson = $clientes->map(fn (Cliente $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'email' => $c->email ?? '',
            'phone' => $c->phone ?? '',
            'status' => $c->ativo ? 'active' : 'inactive',
            'last_date' => $c->agendamentos()->latest('data_hora')->value('data_hora'),
            'total' => $c->agendamentos_count,
            'fotos' => $c->fotos->map(fn (ClienteFoto $f) => [
                'id' => $f->id,
                'url' => Storage::url($f->imagem_path),
                'tipo' => $f->tipo,
                'legenda' => $f->legenda,
            ]),
        ]);

        return view('clientes.index', compact('clientes', 'clientesJson'));
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

        $cliente->load([
            'agendamentos' => fn ($q) => $q->with('servico', 'profissional', 'avaliacao')->latest('data_hora')->limit(20),
            'fotos' => fn ($q) => $q->orderBy('created_at'),
        ]);

        $totalAgendamentos = $cliente->agendamentos()->count();
        $receitaTotal = (float) $cliente->agendamentos()->where('status', 'finalizado')->sum('valor');
        $notaMedia = Avaliacao::whereHas('agendamento', fn ($q) => $q->where('cliente_id', $cliente->id))->avg('nota');

        return view('clientes.show', compact('cliente', 'totalAgendamentos', 'receitaTotal', 'notaMedia'));
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

    public function lgpd(Request $request, Cliente $cliente): JsonResponse
    {
        $this->authorize('update', $cliente);

        $request->validate([
            'consent' => ['required', 'boolean'],
        ]);

        $cliente->update(['lgpd_consent' => $request->boolean('consent')]);

        return response()->json([
            'lgpd_consent' => $cliente->lgpd_consent,
            'updated_at' => $cliente->updated_at->toIso8601String(),
        ]);
    }

    public function toggle(Cliente $cliente): JsonResponse
    {
        $this->authorize('update', $cliente);

        $cliente->update(['ativo' => ! $cliente->ativo]);

        return response()->json(['ativo' => $cliente->ativo]);
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        $this->authorize('delete', $cliente);

        $cliente->delete();

        return redirect()->route('clientes.index')
            ->with('success', "Cliente {$cliente->name} removido.");
    }

    public function destroyBulk(Request $request): JsonResponse
    {
        $this->authorize('deleteAny', Cliente::class);

        $ids = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['uuid']])['ids'];

        $deleted = Cliente::where('company_id', auth()->user()->empresa_id)
            ->whereIn('id', $ids)
            ->delete();

        return response()->json(['deleted' => $deleted]);
    }

    public function exportarCsv(): StreamedResponse
    {
        $this->authorize('viewAny', Cliente::class);

        $empresa = auth()->user()->empresa_id;

        $clientes = Cliente::where('company_id', $empresa)
            ->withCount('agendamentos')
            ->with(['agendamentos' => fn ($q) => $q->latest('data_hora')->limit(1)])
            ->orderBy('name')
            ->get();

        return response()->streamDownload(function () use ($clientes): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nome', 'E-mail', 'Telefone', 'Total Agendamentos', 'Último Agendamento', 'Status'], ';');

            foreach ($clientes as $c) {
                $ultimo = $c->agendamentos->first()?->data_hora?->format('d/m/Y H:i') ?? '';
                fputcsv($out, [
                    $c->name,
                    $c->email ?? '',
                    $c->phone ?? '',
                    $c->agendamentos_count,
                    $ultimo,
                    $c->ativo ? 'Ativo' : 'Inativo',
                ], ';');
            }

            fclose($out);
        }, 'clientes-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportarSegmento(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Cliente::class);

        $tipo = $request->input('tipo', 'top');
        $empresa = auth()->user()->empresa_id;
        $limite90 = now()->subDays(90);

        $query = Cliente::where('company_id', $empresa)->withCount('agendamentos');

        $clientes = match ($tipo) {
            'inativos' => $query
                ->whereDoesntHave('agendamentos', fn ($q) => $q->where('data_hora', '>=', $limite90))
                ->orderBy('name')
                ->get(),
            'aniversariantes' => $query
                ->whereNotNull('data_nasc')
                ->get()
                ->sortBy(fn ($c) => $c->data_nasc->format('m-d'))
                ->values(),
            default => $query
                ->get()
                ->filter(fn ($c) => $c->agendamentos_count >= 3)
                ->sortByDesc('agendamentos_count')
                ->values(),
        };

        $filename = "clientes-{$tipo}-".now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($clientes, $tipo): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            $headers = ['Nome', 'E-mail', 'Telefone', 'Total Agendamentos'];
            if ($tipo === 'aniversariantes') {
                $headers[] = 'Data de Nascimento';
            }
            fputcsv($out, $headers, ';');

            foreach ($clientes as $c) {
                $row = [
                    $c->name,
                    $c->email ?? '',
                    $c->phone ?? '',
                    $c->agendamentos_count,
                ];
                if ($tipo === 'aniversariantes') {
                    $row[] = $c->data_nasc?->format('d/m') ?? '';
                }
                fputcsv($out, $row, ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function agendamentos(Request $request, Cliente $cliente): JsonResponse
    {
        $this->authorize('view', $cliente);

        $perPage = min((int) $request->input('per_page', 10), 50);

        $paginado = $cliente->agendamentos()
            ->with(['servico:id,nome,cor', 'profissional:id,name'])
            ->orderByDesc('data_hora')
            ->paginate($perPage);

        return response()->json([
            'total' => $paginado->total(),
            'per_page' => $paginado->perPage(),
            'page' => $paginado->currentPage(),
            'data' => $paginado->map(fn ($ag) => [
                'id' => $ag->id,
                'data_hora' => $ag->data_hora->toIso8601String(),
                'servico_nome' => $ag->servico?->nome ?? '',
                'servico_cor' => $ag->servico?->cor ?? '#999999',
                'profissional_nome' => $ag->profissional?->name ?? '',
                'status' => $ag->status,
                'valor' => (float) $ag->valor,
                'duracao' => (int) $ag->duracao,
            ])->values(),
        ]);
    }

    public function segmentos(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Cliente::class);

        $empresa = auth()->user()->empresa_id;
        $dias = min((int) $request->input('dias', 90), 365);
        $desde = now()->subDays($dias);

        $clientes = Cliente::where('company_id', $empresa)
            ->where('ativo', true)
            ->withCount([
                'agendamentos as total_agendamentos',
                'agendamentos as recentes' => fn ($q) => $q->where('data_hora', '>=', $desde)
                    ->whereNotIn('status', ['cancelado']),
            ])
            ->get();

        $vip = 0;
        $recorrente = 0;
        $novo = 0;
        $inativo = 0;

        foreach ($clientes as $c) {
            $recentes = (int) $c->recentes;

            if ($recentes >= 5) {
                $vip++;
            } elseif ($recentes >= 2) {
                $recorrente++;
            } elseif ($recentes === 1) {
                $novo++;
            } else {
                $inativo++;
            }
        }

        return response()->json([
            'periodo_dias' => $dias,
            'total_ativos' => $clientes->count(),
            'segmentos' => [
                ['nome' => 'VIP', 'descricao' => "≥5 agendamentos nos últimos {$dias} dias", 'total' => $vip],
                ['nome' => 'Recorrente', 'descricao' => "2-4 agendamentos nos últimos {$dias} dias", 'total' => $recorrente],
                ['nome' => 'Novo', 'descricao' => "1 agendamento nos últimos {$dias} dias", 'total' => $novo],
                ['nome' => 'Inativo', 'descricao' => "0 agendamentos nos últimos {$dias} dias", 'total' => $inativo],
            ],
        ]);
    }

    public function detalhe(Cliente $cliente): JsonResponse
    {
        $this->authorize('view', $cliente);

        $cliente->load(['fotos' => fn ($q) => $q->orderBy('created_at')]);

        $totalAgendamentos = $cliente->agendamentos()->count();
        $finalizados = $cliente->agendamentos()->where('status', 'finalizado')->count();
        $receita = (float) $cliente->agendamentos()->where('status', 'finalizado')->sum('valor');
        $notaMedia = round(
            (float) (Avaliacao::whereHas('agendamento', fn ($q) => $q->where('cliente_id', $cliente->id))->avg('nota') ?? 0.0),
            2
        );
        $ultimoAg = $cliente->agendamentos()->latest('data_hora')->value('data_hora');

        return response()->json([
            'id' => $cliente->id,
            'name' => $cliente->name,
            'email' => $cliente->email ?? '',
            'phone' => $cliente->phone ?? '',
            'data_nasc' => $cliente->data_nasc?->format('Y-m-d'),
            'observacao' => $cliente->observacao ?? '',
            'ativo' => $cliente->ativo,
            'lgpd_consent' => $cliente->lgpd_consent,
            'stats' => [
                'total_agendamentos' => $totalAgendamentos,
                'finalizados' => $finalizados,
                'receita_total' => $receita,
                'nota_media' => $notaMedia,
                'ultimo_agendamento' => $ultimoAg?->toIso8601String(),
            ],
            'fotos' => $cliente->fotos->map(fn (ClienteFoto $f) => [
                'id' => $f->id,
                'url' => Storage::url($f->imagem_path),
                'tipo' => $f->tipo,
                'legenda' => $f->legenda ?? '',
            ])->values(),
        ]);
    }

    public function aniversariantes(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Cliente::class);

        $empresa = auth()->user()->empresa_id;
        $periodo = $request->input('periodo', 'hoje');

        $hoje = now();

        $query = Cliente::where('company_id', $empresa)
            ->where('ativo', true)
            ->whereNotNull('data_nasc');

        if ($periodo === 'mes') {
            $query->whereMonth('data_nasc', $hoje->month);
        } else {
            $query->whereMonth('data_nasc', $hoje->month)
                ->whereDay('data_nasc', $hoje->day);
        }

        $clientes = $query->get(['id', 'name', 'phone', 'email', 'data_nasc'])
            ->sortBy(fn (Cliente $c) => $c->data_nasc->format('m-d'))
            ->values()
            ->map(fn (Cliente $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone ?? '',
                'email' => $c->email ?? '',
                'data_nasc' => $c->data_nasc?->format('Y-m-d'),
                'idade' => $c->data_nasc ? $c->data_nasc->age : null,
            ]);

        return response()->json($clientes);
    }

    public function stats(Cliente $cliente): JsonResponse
    {
        $this->authorize('view', $cliente);

        $total = $cliente->agendamentos()->count();
        $finalizados = $cliente->agendamentos()->where('status', 'finalizado')->count();
        $receita = (float) $cliente->agendamentos()->where('status', 'finalizado')->sum('valor');
        $notaMedia = round(
            (float) (Avaliacao::whereHas('agendamento', fn ($q) => $q->where('cliente_id', $cliente->id))->avg('nota') ?? 0.0),
            2
        );
        $ultimos90Dias = $cliente->agendamentos()
            ->where('data_hora', '>=', now()->subDays(90))
            ->count();
        $primeiroAg = $cliente->agendamentos()->oldest('data_hora')->value('data_hora');
        $ultimoAg = $cliente->agendamentos()->latest('data_hora')->value('data_hora');

        return response()->json([
            'total' => $total,
            'finalizados' => $finalizados,
            'receita_total' => $receita,
            'nota_media' => $notaMedia,
            'ultimos_90_dias' => $ultimos90Dias,
            'primeiro_agendamento' => $primeiroAg?->toIso8601String(),
            'ultimo_agendamento' => $ultimoAg?->toIso8601String(),
        ]);
    }

    public function servicosFavoritos(Cliente $cliente): JsonResponse
    {
        $this->authorize('view', $cliente);

        $servicos = $cliente->agendamentos()
            ->where('status', 'finalizado')
            ->with('servico:id,nome,cor,preco')
            ->get(['servico_id', 'valor'])
            ->groupBy('servico_id')
            ->map(function ($items): array {
                $servico = $items->first()->servico;

                return [
                    'servico_id' => $servico?->id ?? '',
                    'nome' => $servico?->nome ?? 'Sem serviço',
                    'cor' => $servico?->cor ?? '#999999',
                    'total_agendamentos' => $items->count(),
                    'receita_total' => (float) $items->sum('valor'),
                ];
            })
            ->sortByDesc('total_agendamentos')
            ->values();

        return response()->json($servicos);
    }

    public function proximos(Request $request, Cliente $cliente): JsonResponse
    {
        $this->authorize('view', $cliente);

        $limite = min((int) $request->input('limite', 5), 20);

        $agendamentos = $cliente->agendamentos()
            ->whereIn('status', ['pendente', 'confirmado'])
            ->where('data_hora', '>=', now())
            ->with(['servico:id,nome,cor', 'profissional:id,name'])
            ->orderBy('data_hora')
            ->limit($limite)
            ->get()
            ->map(fn (Agendamento $ag) => [
                'id' => $ag->id,
                'data_hora' => $ag->data_hora->toIso8601String(),
                'servico_nome' => $ag->servico?->nome ?? '',
                'servico_cor' => $ag->servico?->cor ?? '#999999',
                'profissional_nome' => $ag->profissional?->name ?? '',
                'status' => $ag->status,
                'valor' => (float) $ag->valor,
                'duracao' => (int) $ag->duracao,
            ]);

        return response()->json(['total' => $agendamentos->count(), 'items' => $agendamentos]);
    }

    public function observacao(Request $request, Cliente $cliente): JsonResponse
    {
        $this->authorize('update', $cliente);

        $request->validate([
            'observacao' => ['nullable', 'string', 'max:1000'],
        ]);

        $cliente->update(['observacao' => $request->input('observacao')]);

        return response()->json([
            'observacao' => $cliente->observacao,
            'updated_at' => $cliente->updated_at->toIso8601String(),
        ]);
    }

    public function contato(Request $request, Cliente $cliente): JsonResponse
    {
        $this->authorize('update', $cliente);

        $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
        ]);

        $cliente->update(array_filter($request->only('name', 'phone', 'email'), fn ($v) => $v !== null));

        return response()->json([
            'name' => $cliente->name,
            'phone' => $cliente->phone ?? '',
            'email' => $cliente->email ?? '',
            'updated_at' => $cliente->updated_at->toIso8601String(),
        ]);
    }

    public function nascimento(Request $request, Cliente $cliente): JsonResponse
    {
        $this->authorize('update', $cliente);

        $request->validate([
            'data_nasc' => ['nullable', 'date', 'date_format:Y-m-d', 'before:today'],
        ]);

        $cliente->update(['data_nasc' => $request->input('data_nasc')]);

        return response()->json([
            'data_nasc' => $cliente->data_nasc?->format('Y-m-d'),
            'updated_at' => $cliente->updated_at->toIso8601String(),
        ]);
    }

    public function recentes(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Cliente::class);

        $empresa = auth()->user()->empresa_id;
        $limite = min((int) $request->input('limite', 10), 50);
        $dias = min((int) $request->input('dias', 30), 180);

        $clientes = Cliente::where('company_id', $empresa)
            ->where('created_at', '>=', now()->subDays($dias))
            ->orderByDesc('created_at')
            ->limit($limite)
            ->get(['id', 'name', 'phone', 'email', 'ativo', 'created_at'])
            ->map(fn (Cliente $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone ?? '',
                'email' => $c->email ?? '',
                'ativo' => $c->ativo,
                'cadastrado_em' => $c->created_at->toIso8601String(),
            ]);

        return response()->json(['total' => $clientes->count(), 'dias' => $dias, 'items' => $clientes]);
    }

    public function topGastadores(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Cliente::class);

        $empresa = auth()->user()->empresa_id;
        $limite = min((int) $request->input('limite', 10), 50);
        $dias = $request->input('dias');

        $agQuery = Agendamento::where('company_id', $empresa)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->when($dias !== null, fn ($q) => $q->where('data_hora', '>=', now()->subDays((int) $dias)))
            ->selectRaw('cliente_id, COUNT(*) as total_agendamentos, SUM(valor) as receita')
            ->groupBy('cliente_id')
            ->orderByDesc('receita')
            ->limit($limite)
            ->get();

        $clienteIds = $agQuery->pluck('cliente_id');
        $clientes = Cliente::whereIn('id', $clienteIds)->get(['id', 'name', 'phone', 'email', 'ativo'])->keyBy('id');

        $items = $agQuery->map(fn ($row) => [
            'cliente_id' => $row->cliente_id,
            'nome' => $clientes->get($row->cliente_id)?->name ?? '',
            'phone' => $clientes->get($row->cliente_id)?->phone ?? '',
            'total_agendamentos' => (int) $row->total_agendamentos,
            'receita_total' => (float) $row->receita,
        ])->values();

        return response()->json(['total' => $items->count(), 'items' => $items]);
    }

    public function buscar(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Cliente::class);

        $q = trim((string) $request->input('q', ''));
        $empresa = auth()->user()->empresa_id;

        if ($q === '') {
            return response()->json([]);
        }

        $clientes = Cliente::where('company_id', $empresa)
            ->where(function ($query) use ($q): void {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'phone', 'email'])
            ->map(fn (Cliente $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone ?? '',
                'email' => $c->email ?? '',
            ]);

        return response()->json($clientes);
    }

    public function importarCsv(Request $request): JsonResponse
    {
        $this->authorize('create', Cliente::class);

        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $empresa = auth()->user()->empresa_id;
        $handle = fopen($request->file('arquivo')->getRealPath(), 'r');

        $header = null;
        $importados = 0;
        $erros = 0;

        while (($row = fgetcsv($handle, 1000, ';')) !== false) {
            if ($header === null) {
                $header = array_map('trim', $row);

                continue;
            }

            $data = array_combine($header, array_map('trim', $row));
            $nome = $data['nome'] ?? $data['name'] ?? null;
            $phone = $data['telefone'] ?? $data['phone'] ?? null;

            if (! $nome || ! $phone) {
                $erros++;

                continue;
            }

            $created = Cliente::firstOrCreate(
                ['company_id' => $empresa, 'phone' => $phone],
                [
                    'name' => $nome,
                    'email' => $data['email'] ?? null,
                    'data_nasc' => ! empty($data['data_nasc']) ? $data['data_nasc'] : null,
                    'observacao' => $data['observacao'] ?? null,
                    'ativo' => true,
                ]
            );
            if ($created->wasRecentlyCreated) {
                $importados++;
            }
        }

        fclose($handle);

        return response()->json(['importados' => $importados, 'erros' => $erros]);
    }

    public function fotos(Cliente $cliente): JsonResponse
    {
        $this->authorize('view', $cliente);

        $fotos = $cliente->fotos()
            ->orderBy('created_at')
            ->get()
            ->map(fn (ClienteFoto $f) => [
                'id' => $f->id,
                'url' => Storage::url($f->imagem_path),
                'tipo' => $f->tipo,
                'legenda' => $f->legenda,
                'criado_em' => $f->created_at->toIso8601String(),
            ]);

        return response()->json($fotos);
    }

    public function storeFoto(Request $request, Cliente $cliente): JsonResponse
    {
        $this->authorize('update', $cliente);

        $request->validate([
            'imagem' => ['required', 'image', 'max:5120'],
            'legenda' => ['nullable', 'string', 'max:100'],
            'tipo' => ['nullable', 'in:antes,depois,outro'],
        ]);

        $companyId = auth()->user()->empresa_id;
        $path = $request->file('imagem')->store(
            "cliente_fotos/{$companyId}",
            'public'
        );

        $foto = $cliente->fotos()->create([
            'imagem_path' => $path,
            'legenda' => $request->input('legenda'),
            'tipo' => $request->input('tipo', 'outro'),
        ]);

        return response()->json([
            'id' => $foto->id,
            'url' => Storage::url($foto->imagem_path),
            'tipo' => $foto->tipo,
            'legenda' => $foto->legenda,
        ], 201);
    }

    public function destroyFoto(ClienteFoto $foto): Response
    {
        $this->authorize('update', $foto->cliente);

        if ($foto->cliente->company_id !== auth()->user()->empresa_id) {
            abort(403);
        }

        Storage::disk('public')->delete($foto->imagem_path);
        $foto->delete();

        return response()->noContent();
    }

    public function frequencia(Cliente $cliente): JsonResponse
    {
        $this->authorize('view', $cliente);

        $agendamentos = $cliente->agendamentos()
            ->where('status', 'finalizado')
            ->orderBy('data_hora')
            ->get(['data_hora', 'valor']);

        $total = $agendamentos->count();
        $ltv = (float) $agendamentos->sum('valor');

        if ($total === 0) {
            return response()->json([
                'total_visitas' => 0,
                'ltv' => 0.0,
                'media_dias_entre_visitas' => null,
                'proxima_visita_prevista' => null,
                'frequencia_mensal' => 0.0,
            ]);
        }

        $ultima = $agendamentos->last()->data_hora;

        $mediaDias = null;
        $proximaPrevista = null;
        $frequenciaMensal = 0.0;

        if ($total >= 2) {
            $primeira = $agendamentos->first()->data_hora;
            $totalDias = (int) $primeira->diffInDays($ultima);
            $mediaDias = $totalDias > 0 ? round($totalDias / ($total - 1), 1) : null;

            if ($mediaDias !== null && $mediaDias > 0) {
                $proximaPrevista = $ultima->copy()->addDays((int) round($mediaDias))->toDateString();
                $frequenciaMensal = round(30 / $mediaDias, 2);
            }
        }

        return response()->json([
            'total_visitas' => $total,
            'ltv' => $ltv,
            'media_dias_entre_visitas' => $mediaDias,
            'proxima_visita_prevista' => $proximaPrevista,
            'frequencia_mensal' => $frequenciaMensal,
        ]);
    }

    public function avaliacoes(Request $request, Cliente $cliente): JsonResponse
    {
        $this->authorize('view', $cliente);

        $limite = min((int) $request->input('limite', 10), 50);

        $avaliacoes = Avaliacao::where('company_id', auth()->user()->empresa_id)
            ->whereHas('agendamento', fn ($q) => $q->where('cliente_id', $cliente->id))
            ->with(['agendamento.servico:id,nome', 'agendamento.profissional:id,name'])
            ->orderByDesc('created_at')
            ->limit($limite)
            ->get()
            ->map(fn (Avaliacao $av) => [
                'id' => $av->id,
                'nota' => $av->nota,
                'comentario' => $av->comentario ?? '',
                'data' => $av->created_at->toDateString(),
                'servico_nome' => $av->agendamento?->servico?->nome ?? 'Serviço removido',
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

    public function compras(Request $request, Cliente $cliente): JsonResponse
    {
        $this->authorize('view', $cliente);

        $limite = min((int) $request->input('limite', 10), 50);

        $vendas = Venda::where('company_id', auth()->user()->empresa_id)
            ->where('cliente_id', $cliente->id)
            ->with('itens.produto:id,nome,categoria')
            ->orderByDesc('created_at')
            ->limit($limite)
            ->get()
            ->map(fn (Venda $v) => [
                'id' => $v->id,
                'data' => $v->created_at->toDateString(),
                'total' => (float) $v->total,
                'metodo_pagamento' => $v->metodo_pagamento,
                'itens' => $v->itens->map(fn ($item) => [
                    'produto_nome' => $item->produto?->nome ?? 'Produto removido',
                    'qtd' => $item->qtd,
                    'preco_unit' => (float) $item->preco_unit,
                    'total' => (float) $item->total,
                ])->values(),
            ]);

        return response()->json([
            'total_compras' => $vendas->count(),
            'total_gasto' => (float) $vendas->sum('total'),
            'items' => $vendas->values(),
        ]);
    }

    public function nome(Request $request, Cliente $cliente): JsonResponse
    {
        $this->authorize('update', $cliente);

        $request->validate(['nome' => ['required', 'string', 'max:120']]);

        $cliente->update(['name' => $request->input('nome')]);

        return response()->json([
            'nome' => $cliente->name,
            'updated_at' => $cliente->updated_at->toIso8601String(),
        ]);
    }

    public function timeline(Request $request, Cliente $cliente): JsonResponse
    {
        $this->authorize('view', $cliente);

        $limite = min((int) $request->input('limite', 20), 50);

        $agendamentos = collect($cliente->agendamentos()
            ->with(['servico:id,nome,cor', 'profissional:id,name,cor'])
            ->orderByDesc('data_hora')
            ->limit($limite)
            ->get()
            ->map(fn (Agendamento $a) => [
                'tipo' => 'agendamento',
                'id' => $a->id,
                'data' => $a->data_hora->toIso8601String(),
                'status' => $a->status,
                'servico' => $a->servico?->nome,
                'profissional' => $a->profissional?->name,
                'valor' => (float) ($a->valor ?? 0),
            ])->all());

        $compras = collect(Venda::where('company_id', auth()->user()->empresa_id)
            ->where('cliente_id', $cliente->id)
            ->with('itens.produto:id,nome')
            ->orderByDesc('created_at')
            ->limit($limite)
            ->get()
            ->map(fn (Venda $v) => [
                'tipo' => 'compra',
                'id' => $v->id,
                'data' => $v->created_at->toIso8601String(),
                'total' => (float) $v->total,
                'itens_count' => $v->itens->count(),
            ])->all());

        $avaliacoes = collect(Avaliacao::whereHas('agendamento', fn ($q) => $q->where('cliente_id', $cliente->id))
            ->where('company_id', auth()->user()->empresa_id)
            ->orderByDesc('created_at')
            ->limit($limite)
            ->get()
            ->map(fn (Avaliacao $av) => [
                'tipo' => 'avaliacao',
                'id' => $av->id,
                'data' => $av->created_at->toIso8601String(),
                'nota' => $av->nota,
                'comentario' => $av->comentario ?? '',
            ])->all());

        $timeline = $agendamentos
            ->merge($compras)
            ->merge($avaliacoes)
            ->sortByDesc('data')
            ->take($limite)
            ->values();

        return response()->json([
            'total' => $timeline->count(),
            'items' => $timeline,
        ]);
    }

    public function ticketMedio(Cliente $cliente): JsonResponse
    {
        $this->authorize('view', $cliente);

        $base = $cliente->agendamentos()->where('status', Agendamento::STATUS_FINALIZADO);

        $calcular = fn (int $dias): array => (function () use ($base, $dias): array {
            $q = (clone $base)->when($dias > 0, fn ($q) => $q->where('data_hora', '>=', now()->subDays($dias)));
            $total = $q->count();
            $receita = (float) $q->sum('valor');

            return [
                'total' => $total,
                'receita' => $receita,
                'ticket_medio' => $total > 0 ? round($receita / $total, 2) : null,
            ];
        })();

        return response()->json([
            'cliente_id' => $cliente->id,
            'cliente_nome' => $cliente->name,
            'ultimos_7_dias' => $calcular(7),
            'ultimos_30_dias' => $calcular(30),
            'ultimos_90_dias' => $calcular(90),
            'geral' => $calcular(0),
        ]);
    }

    public function tempoSemVisita(Cliente $cliente): JsonResponse
    {
        $this->authorize('view', $cliente);

        $ultimoAgendamento = $cliente->agendamentos()
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->latest('data_hora')
            ->first(['data_hora']);

        if ($ultimoAgendamento === null) {
            return response()->json([
                'cliente_id' => $cliente->id,
                'cliente_nome' => $cliente->name,
                'dias_sem_visita' => null,
                'ultima_visita' => null,
                'risco_churn' => 'sem_historico',
            ]);
        }

        $dias = (int) $ultimoAgendamento->data_hora->diffInDays(now());

        $risco = match (true) {
            $dias <= 30 => 'baixo',
            $dias <= 60 => 'medio',
            $dias <= 90 => 'alto',
            default => 'critico',
        };

        return response()->json([
            'cliente_id' => $cliente->id,
            'cliente_nome' => $cliente->name,
            'dias_sem_visita' => $dias,
            'ultima_visita' => $ultimoAgendamento->data_hora->toIso8601String(),
            'risco_churn' => $risco,
        ]);
    }

    public function produtosFavoritos(Request $request, Cliente $cliente): JsonResponse
    {
        $this->authorize('view', $cliente);

        $limite = min((int) $request->input('limite', 10), 50);

        $itens = Venda::where('company_id', auth()->user()->empresa_id)
            ->where('cliente_id', $cliente->id)
            ->with('itens.produto:id,nome,categoria,preco')
            ->get()
            ->pluck('itens')
            ->flatten()
            ->filter(fn ($item) => $item->produto_id !== null)
            ->groupBy('produto_id')
            ->map(function ($items) {
                $produto = $items->first()->produto;

                return [
                    'produto_id' => $produto?->id ?? '',
                    'produto_nome' => $produto?->nome ?? 'Produto removido',
                    'categoria' => $produto?->categoria ?? '',
                    'total_comprado' => (int) $items->sum('qtd'),
                    'total_gasto' => (float) $items->sum('total'),
                ];
            })
            ->sortByDesc('total_comprado')
            ->take($limite)
            ->values();

        return response()->json([
            'cliente_id' => $cliente->id,
            'cliente_nome' => $cliente->name,
            'total' => $itens->count(),
            'items' => $itens,
        ]);
    }
}
