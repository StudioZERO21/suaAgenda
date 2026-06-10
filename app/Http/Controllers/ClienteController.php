<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Avaliacao;
use App\Models\Cliente;
use App\Models\ClienteFoto;
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
}
