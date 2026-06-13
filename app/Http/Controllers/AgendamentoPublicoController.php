<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAgendamentoPublicoRequest;
use App\Mail\AgendamentoConfirmado;
use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\BloqueioAgenda;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\HorarioTrabalho;
use App\Models\Profissional;
use App\Models\Servico;
use App\Services\AgendamentoCancelamentoService;
use App\Services\AgendamentoDisponibilidadeService;
use App\Services\RegraService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AgendamentoPublicoController extends Controller
{
    /**
     * O agendamento agora acontece num modal na própria vitrine — esta rota
     * apenas redireciona para a vitrine com o modal aberto (?book=1),
     * repassando eventuais parâmetros de pré-seleção.
     */
    public function show(string $slug, Request $request): RedirectResponse
    {
        $prefill = array_filter($request->only(['servico_id', 'profissional_id', 'data', 'hora']));

        return redirect()->route('vitrine.show', array_merge(['slug' => $slug, 'book' => 1], $prefill));
    }

    /**
     * Landing pública (vitrine de marketing) da empresa.
     *
     * Exibe hero, serviços, equipe e depoimentos com dados reais; os botões
     * de ação encaminham para o fluxo de agendamento (rota agendar.show).
     */
    public function landing(string $slug, AgendamentoCancelamentoService $cancelamento): View
    {
        $company = Company::where('slug', $slug)->where('ativo', true)->firstOrFail();
        $politicaAgendamento = $cancelamento->descricaoPolitica($company->id);

        $servicos = Servico::where('company_id', $company->id)
            ->ativo()
            ->with('profissionais:id')
            ->orderBy('nome')
            ->get();

        $profissionais = Profissional::where('company_id', $company->id)
            ->ativo()
            ->withCount('agendamentos')
            ->orderBy('name')
            ->get();

        // Mapa serviço→{nome,preco,profissionais[]} para o modal "Ver Horários".
        $servicosMap = $servicos->mapWithKeys(fn (Servico $s) => [
            $s->id => [
                'nome' => $s->nome,
                'preco' => $s->precoFormatado(),
                'profissionais' => $s->profissionais->pluck('id')->values()->toArray(),
            ],
        ]);

        $siteCfg = $company->resolvedSettings()['site'] ?? [];

        $notaMediaReal = Avaliacao::whereHas('agendamento', fn ($q) => $q->where('company_id', $company->id))->avg('nota');
        $totalAvaliacoesReal = Avaliacao::whereHas('agendamento', fn ($q) => $q->where('company_id', $company->id))->count();

        $avaliacoesPublicas = Avaliacao::whereHas('agendamento', fn ($q) => $q->where('company_id', $company->id))
            ->with(['agendamento.cliente', 'agendamento.profissional', 'agendamento.servico'])
            ->whereNotNull('comentario')
            ->where('nota', '>=', 4)
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Avaliacao $av) => [
                'name' => $av->agendamento?->cliente?->name ?? 'Cliente',
                'svc' => $av->agendamento?->servico?->nome ?? '',
                'text' => $av->comentario,
                'nota' => $av->nota,
                'profissional' => $av->agendamento?->profissional?->name ?? '',
            ]);

        return view('public.vitrine', compact('company', 'servicos', 'servicosMap', 'profissionais', 'siteCfg', 'avaliacoesPublicas', 'notaMediaReal', 'totalAvaliacoesReal', 'politicaAgendamento'));
    }

    /**
     * Retorna disponibilidade de todos os profissionais para um serviço + data.
     * Usada pela seção de horários na vitrine pública.
     *
     * GET /vitrine/{slug}/disponibilidade?servico_id=X&data=Y
     */
    public function disponibilidade(string $slug, Request $request, AgendamentoDisponibilidadeService $disponibilidade): JsonResponse
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
        $agora = now();

        $profissionaisIds = $servico->profissionais()->pluck('profissionais.id');
        $profissionais = Profissional::where('company_id', $company->id)
            ->ativo()
            ->when($profissionaisIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $profissionaisIds))
            ->orderBy('name')
            ->get();

        $result = $profissionais->map(function (Profissional $prof) use ($data, $diaSemana, $duracao, $agora, $disponibilidade) {
            $vazio = ['profissional' => ['id' => $prof->id, 'name' => $prof->name, 'cor' => $prof->cor ?? '#1a1a1a'], 'slots' => []];

            if (BloqueioAgenda::blockedOn($prof->id, $data->format('Y-m-d'))) {
                return $vazio;
            }

            $horario = HorarioTrabalho::where('profissional_id', $prof->id)
                ->where('dia_semana', $diaSemana)
                ->where('ativo', true)
                ->first();

            if (! $horario) {
                return $vazio;
            }

            $inicio = Carbon::parse($data->format('Y-m-d').' '.$horario->hora_inicio);
            $fim = Carbon::parse($data->format('Y-m-d').' '.$horario->hora_fim);

            $slots = [];
            $current = $inicio->copy();
            while ($current->copy()->addMinutes($duracao)->lte($fim)) {
                $livre = $current->gt($agora)
                    && ! $disponibilidade->temConflito($prof->id, $current, $duracao);
                $slots[] = ['hora' => $current->format('H:i'), 'disponivel' => $livre];
                $current->addMinutes($duracao);
            }

            return ['profissional' => ['id' => $prof->id, 'name' => $prof->name, 'cor' => $prof->cor ?? '#1a1a1a'], 'slots' => $slots];
        });

        return response()->json($result->values());
    }

    public function slots(string $slug, Request $request, AgendamentoDisponibilidadeService $disponibilidade): JsonResponse
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
        $agora = now();

        $slots = [];
        $current = $inicio->copy();

        while ($current->copy()->addMinutes($duracao)->lte($fim)) {
            // Considera sobreposição de duração (não só hora exata) e descarta horários passados.
            $disponivel = $current->gt($agora)
                && ! $disponibilidade->temConflito($profissional->id, $current, $duracao);

            $slots[] = ['hora' => $current->format('H:i'), 'disponivel' => $disponivel];
            $current->addMinutes($duracao);
        }

        return response()->json($slots);
    }

    public function store(
        StoreAgendamentoPublicoRequest $request,
        string $slug,
        RegraService $regras,
        AgendamentoDisponibilidadeService $disponibilidade
    ): RedirectResponse {
        $company = Company::where('slug', $slug)->firstOrFail();

        $servico = Servico::where('company_id', $company->id)
            ->findOrFail($request->servico_id);

        // Revalida o horário no servidor — o front só sugere; a agenda do
        // profissional (expediente, bloqueio, sobreposição) é a fonte da verdade.
        $inicio = Carbon::parse($request->data_hora);
        $valida = $disponibilidade->validar($request->profissional_id, $servico, $inicio);

        if (! $valida['ok']) {
            return back()->withInput()->withErrors(['data_hora' => $valida['motivo']]);
        }

        // Telefone normalizado (só dígitos) para evitar cadastros duplicados
        // por formatação diferente; a busca no portal já casa ambos os formatos.
        $phone = preg_replace('/\D/', '', (string) $request->cliente_phone) ?: $request->cliente_phone;

        $cliente = Cliente::firstOrCreate(
            ['company_id' => $company->id, 'phone' => $phone],
            [
                'name' => $request->cliente_nome,
                'email' => $request->cliente_email,
                'lgpd_consent' => true,
                'lgpd_consent_at' => now(),
                'lgpd_consent_ip' => $request->ip(),
            ]
        );

        // Cliente recorrente sem consentimento registrado: grava agora (deu opt-in no form)
        if (! $cliente->lgpd_consent) {
            $cliente->update([
                'lgpd_consent' => true,
                'lgpd_consent_at' => now(),
                'lgpd_consent_ip' => $request->ip(),
            ]);
        }

        // Regra no_show: bloqueia agendamento online de cliente faltante recorrente
        if ($regras->enabled('no_show', $company->id)) {
            $limite = (int) $regras->param('no_show', 'bloquear_apos', 3, $company->id);
            $faltas = Agendamento::where('company_id', $company->id)
                ->where('cliente_id', $cliente->id)
                ->where('status', Agendamento::STATUS_NO_SHOW)
                ->count();

            if ($faltas >= $limite) {
                return back()->withInput()->withErrors([
                    'cliente_phone' => 'Não foi possível concluir o agendamento online. Entre em contato com o estabelecimento.',
                ]);
            }
        }

        $agendamento = Agendamento::create([
            'company_id' => $company->id,
            'cliente_id' => $cliente->id,
            'profissional_id' => $request->profissional_id,
            'servico_id' => $request->servico_id,
            'data_hora' => $request->data_hora,
            'duracao' => $servico->duracao_minutos,
            'valor' => $servico->preco,
            'status' => ($company->resolvedSettings()['advanced']['confirm_required'] ?? true)
                ? Agendamento::STATUS_PENDENTE
                : Agendamento::STATUS_CONFIRMADO,
            'observacao' => $request->observacao,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $agendamento->load(['cliente', 'profissional', 'servico', 'company']);

        if ($agendamento->cliente?->email) {
            Mail::to($agendamento->cliente->email)
                ->queue(new AgendamentoConfirmado($agendamento));
        }

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
     * Aposentado: a busca por telefone (sem autenticação) expunha agenda de
     * qualquer cliente. Redireciona para o portal autenticado por link mágico.
     * GET /vitrine/{slug}/minhas-reservas
     */
    public function minhasReservas(string $slug): RedirectResponse
    {
        return redirect()->route('portal.entrar', $slug);
    }

    /**
     * Página pública de status do agendamento (acesso via cancel_token).
     * GET /meu-agendamento/{token}
     */
    public function meuAgendamento(string $token, AgendamentoCancelamentoService $cancelamento): View
    {
        $ag = Agendamento::with(['servico', 'profissional', 'cliente', 'company', 'avaliacao'])
            ->where('cancel_token', $token)
            ->firstOrFail();

        $company = $ag->company;
        $podeCancelar = $cancelamento->podeCancelar($ag);
        $cancelavel = $podeCancelar['ok'];
        $motivoBloqueio = $podeCancelar['motivo'];
        $politica = $cancelamento->descricaoPolitica($ag->company_id);

        return view('public.meu-agendamento', compact('ag', 'company', 'cancelavel', 'motivoBloqueio', 'politica', 'token'));
    }

    /**
     * Cancela agendamento via token público (sem autenticação),
     * respeitando a política de cancelamento da empresa.
     * POST /meu-agendamento/{token}/cancelar
     */
    public function cancelarMeuAgendamento(string $token, AgendamentoCancelamentoService $cancelamento): RedirectResponse
    {
        $ag = Agendamento::where('cancel_token', $token)->firstOrFail();

        $podeCancelar = $cancelamento->podeCancelar($ag);

        if (! $podeCancelar['ok']) {
            return redirect()->route('agendamento.meu', $token)
                ->with('erro', $podeCancelar['motivo'] ?? 'Este agendamento não pode ser cancelado.');
        }

        $ag->update(['status' => Agendamento::STATUS_CANCELADO]);

        return redirect()->route('agendamento.meu', $token)
            ->with('cancelado', true);
    }
}
