<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAgendamentoPublicoRequest;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\HorarioTrabalho;
use App\Models\Profissional;
use App\Models\Servico;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgendamentoPublicoController extends Controller
{
    public function show(string $slug): View
    {
        $company = Company::where('slug', $slug)->where('ativo', true)->firstOrFail();

        $servicos = Servico::where('company_id', $company->id)
            ->ativo()
            ->with('profissionais:id')
            ->orderBy('nome')
            ->get();

        $profissionais = Profissional::where('company_id', $company->id)
            ->ativo()
            ->orderBy('name')
            ->get();

        $servicosMap = $servicos->mapWithKeys(fn ($s) => [
            $s->id => [
                'nome' => $s->nome,
                'duracao_minutos' => $s->duracao_minutos,
                'preco' => (float) $s->preco,
                'cor' => $s->cor,
                'profissionais' => $s->profissionais->pluck('id')->values()->toArray(),
            ],
        ]);

        return view('public.agendar', compact('company', 'servicos', 'profissionais', 'servicosMap'));
    }

    /**
     * Landing pública (vitrine de marketing) da empresa.
     *
     * Exibe hero, serviços, equipe e depoimentos com dados reais; os botões
     * de ação encaminham para o fluxo de agendamento (rota agendar.show).
     */
    public function landing(string $slug): View
    {
        $company = Company::where('slug', $slug)->where('ativo', true)->firstOrFail();

        $servicos = Servico::where('company_id', $company->id)
            ->ativo()
            ->orderBy('nome')
            ->get();

        $profissionais = Profissional::where('company_id', $company->id)
            ->ativo()
            ->withCount('agendamentos')
            ->orderBy('name')
            ->get();

        $siteCfg = $company->resolvedSettings()['site'] ?? [];

        return view('public.vitrine', compact('company', 'servicos', 'profissionais', 'siteCfg'));
    }

    public function slots(string $slug, Request $request): JsonResponse
    {
        $company = Company::where('slug', $slug)->firstOrFail();

        $request->validate([
            'profissional_id' => ['required', 'uuid'],
            'servico_id' => ['required', 'uuid'],
            'data' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $profissional = Profissional::where('company_id', $company->id)
            ->findOrFail($request->profissional_id);

        $servico = Servico::where('company_id', $company->id)
            ->findOrFail($request->servico_id);

        $data = Carbon::parse($request->data)->startOfDay();
        $diaSemana = (int) $data->format('w'); // 0=Dom,1=Seg,...,6=Sáb

        $horario = HorarioTrabalho::where('profissional_id', $profissional->id)
            ->where('dia_semana', $diaSemana)
            ->where('ativo', true)
            ->first();

        if (! $horario) {
            return response()->json([]);
        }

        $duracao = $servico->duracao_minutos;
        $inicio = Carbon::parse($data->format('Y-m-d').' '.$horario->hora_inicio);
        $fim = Carbon::parse($data->format('Y-m-d').' '.$horario->hora_fim);

        $ocupados = Agendamento::where('profissional_id', $profissional->id)
            ->whereDate('data_hora', $data)
            ->whereIn('status', ['pendente', 'confirmado'])
            ->get()
            ->map(fn ($ag) => $ag->data_hora->format('H:i'));

        $slots = [];
        $current = $inicio->copy();

        while ($current->copy()->addMinutes($duracao)->lte($fim)) {
            $hora = $current->format('H:i');
            $slots[] = ['hora' => $hora, 'disponivel' => ! $ocupados->contains($hora)];
            $current->addMinutes($duracao);
        }

        return response()->json($slots);
    }

    public function store(StoreAgendamentoPublicoRequest $request, string $slug): RedirectResponse
    {
        $company = Company::where('slug', $slug)->firstOrFail();

        $cliente = Cliente::firstOrCreate(
            ['company_id' => $company->id, 'phone' => $request->cliente_phone],
            ['name' => $request->cliente_nome, 'email' => $request->cliente_email]
        );

        $servico = Servico::find($request->servico_id);

        $agendamento = Agendamento::create([
            'company_id' => $company->id,
            'cliente_id' => $cliente->id,
            'profissional_id' => $request->profissional_id,
            'servico_id' => $request->servico_id,
            'data_hora' => $request->data_hora,
            'duracao' => $servico?->duracao_minutos ?? 30,
            'valor' => $servico?->preco,
            'status' => Agendamento::STATUS_PENDENTE,
            'observacao' => $request->observacao,
        ]);

        return redirect()->route('agendar.confirmado', ['slug' => $slug, 'agendamento' => $agendamento->id]);
    }

    public function confirmado(string $slug, string $agendamento): View
    {
        $company = Company::where('slug', $slug)->firstOrFail();
        $ag = Agendamento::with(['servico', 'profissional', 'cliente'])
            ->where('company_id', $company->id)
            ->findOrFail($agendamento);

        return view('public.agendado', compact('company', 'ag'));
    }
}
