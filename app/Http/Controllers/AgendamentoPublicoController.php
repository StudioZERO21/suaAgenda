<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAgendamentoPublicoRequest;
use App\Models\Agendamento;
use App\Models\BloqueioAgenda;
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

    /**
     * Retorna disponibilidade de todos os profissionais para um serviço + data.
     * Usada pela seção de horários na vitrine pública.
     *
     * GET /vitrine/{slug}/disponibilidade?servico_id=X&data=Y
     */
    public function disponibilidade(string $slug, Request $request): JsonResponse
    {
        $company = Company::where('slug', $slug)->firstOrFail();

        $request->validate([
            'servico_id' => ['required', 'uuid'],
            'data' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $servico = Servico::where('company_id', $company->id)
            ->findOrFail($request->servico_id);

        $data = Carbon::parse($request->data)->startOfDay();
        $diaSemana = (int) $data->format('w');
        $duracao = $servico->duracao_minutos;

        $profissionaisIds = $servico->profissionais()->pluck('profissionais.id');
        $profissionais = Profissional::where('company_id', $company->id)
            ->ativo()
            ->when($profissionaisIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $profissionaisIds))
            ->orderBy('name')
            ->get();

        $ocupadosPorProf = Agendamento::whereIn('profissional_id', $profissionais->pluck('id'))
            ->whereDate('data_hora', $data)
            ->whereIn('status', ['pendente', 'confirmado', 'em_atendimento'])
            ->get()
            ->groupBy('profissional_id')
            ->map(fn ($ags) => $ags->map(fn ($ag) => $ag->data_hora->format('H:i'))->values());

        $result = $profissionais->map(function (Profissional $prof) use ($data, $diaSemana, $duracao, $ocupadosPorProf) {
            if (BloqueioAgenda::blockedOn($prof->id, $data->format('Y-m-d'))) {
                return ['profissional' => ['id' => $prof->id, 'name' => $prof->name, 'cor' => $prof->cor ?? '#1a1a1a'], 'slots' => []];
            }

            $horario = HorarioTrabalho::where('profissional_id', $prof->id)
                ->where('dia_semana', $diaSemana)
                ->where('ativo', true)
                ->first();

            if (! $horario) {
                return ['profissional' => ['id' => $prof->id, 'name' => $prof->name, 'cor' => $prof->cor ?? '#1a1a1a'], 'slots' => []];
            }

            $inicio = Carbon::parse($data->format('Y-m-d').' '.$horario->hora_inicio);
            $fim = Carbon::parse($data->format('Y-m-d').' '.$horario->hora_fim);
            $ocupados = $ocupadosPorProf->get($prof->id, collect());

            $slots = [];
            $current = $inicio->copy();
            while ($current->copy()->addMinutes($duracao)->lte($fim)) {
                $hora = $current->format('H:i');
                $slots[] = ['hora' => $hora, 'disponivel' => ! $ocupados->contains($hora)];
                $current->addMinutes($duracao);
            }

            return ['profissional' => ['id' => $prof->id, 'name' => $prof->name, 'cor' => $prof->cor ?? '#1a1a1a'], 'slots' => $slots];
        });

        return response()->json($result->values());
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

        if (BloqueioAgenda::blockedOn($profissional->id, $data->format('Y-m-d'))) {
            return response()->json([]);
        }

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
            'cancel_token' => Agendamento::generateCancelToken(),
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

    /**
     * Página pública de status do agendamento (acesso via cancel_token).
     * GET /meu-agendamento/{token}
     */
    public function meuAgendamento(string $token): View
    {
        $ag = Agendamento::with(['servico', 'profissional', 'cliente', 'company'])
            ->where('cancel_token', $token)
            ->firstOrFail();

        $company = $ag->company;
        $cancelavel = in_array($ag->status, [Agendamento::STATUS_PENDENTE, Agendamento::STATUS_CONFIRMADO])
            && $ag->data_hora->isFuture();

        return view('public.meu-agendamento', compact('ag', 'company', 'cancelavel', 'token'));
    }

    /**
     * Cancela agendamento via token público (sem autenticação).
     * POST /meu-agendamento/{token}/cancelar
     */
    public function cancelarMeuAgendamento(string $token): RedirectResponse
    {
        $ag = Agendamento::where('cancel_token', $token)->firstOrFail();

        $cancelavel = in_array($ag->status, [Agendamento::STATUS_PENDENTE, Agendamento::STATUS_CONFIRMADO])
            && $ag->data_hora->isFuture();

        if (! $cancelavel) {
            return redirect()->route('agendamento.meu', $token)
                ->with('erro', 'Este agendamento não pode ser cancelado.');
        }

        $ag->update(['status' => Agendamento::STATUS_CANCELADO]);

        return redirect()->route('agendamento.meu', $token)
            ->with('cancelado', true);
    }
}
