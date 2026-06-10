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

    public function destroy(Cliente $cliente): RedirectResponse
    {
        $this->authorize('delete', $cliente);

        $cliente->delete();

        return redirect()->route('clientes.index')
            ->with('success', "Cliente {$cliente->name} removido.");
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
