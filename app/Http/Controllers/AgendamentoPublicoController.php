<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAgendamentoPublicoRequest;
use App\Mail\AgendamentoConfirmado;
use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\LinkVisit;
use App\Models\PortfolioItem;
use App\Models\Profissional;
use App\Models\Servico;
use App\Services\AgendamentoCancelamentoService;
use App\Services\AgendamentoDisponibilidadeService;
use App\Services\Pagamento\AsaasService;
use App\Services\RegraService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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

        LinkVisit::track($company->id, LinkVisit::TYPE_VIEW);

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
                'duracao' => $s->duracaoFormatada(),
                'profissionais' => $s->profissionais->pluck('id')->values()->toArray(),
            ],
        ]);

        // Galeria pública: fotos do portfólio publicadas e com imagem (destaques primeiro).
        $portfolio = PortfolioItem::where('company_id', $company->id)
            ->where('publicado', true)
            ->whereNotNull('imagem_path')
            ->with('profissional:id,name')
            ->orderByDesc('destaque')
            ->orderByDesc('created_at')
            ->limit(24)
            ->get()
            ->filter(fn (PortfolioItem $item): bool => Storage::disk('public')->exists($item->imagem_path))
            ->map(fn (PortfolioItem $item): array => [
                'titulo' => $item->titulo,
                'categoria' => $item->categoria,
                'prof' => $item->profissional?->name,
                'destaque' => (bool) $item->destaque,
                'url' => Storage::url($item->imagem_path),
            ])->values();

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

        return view('public.vitrine', compact('company', 'servicos', 'servicosMap', 'profissionais', 'portfolio', 'siteCfg', 'avaliacoesPublicas', 'notaMediaReal', 'totalAvaliacoesReal', 'politicaAgendamento'));
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

        $profissionaisIds = $servico->profissionais()->pluck('profissionais.id');
        $profissionais = Profissional::where('company_id', $company->id)
            ->ativo()
            ->when($profissionaisIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $profissionaisIds))
            ->orderBy('name')
            ->get();

        $result = $profissionais->map(function (Profissional $prof) use ($company, $servico, $data, $disponibilidade) {
            $slots = $disponibilidade->gerarSlots($prof, $company, $servico, $data);

            return [
                'profissional' => [
                    'id' => $prof->id,
                    'name' => $prof->name,
                    'cor' => $prof->cor ?? '#1a1a1a',
                ],
                'slots' => $slots,
            ];
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

        $slots = $disponibilidade->gerarSlots($profissional, $company, $servico, $data);

        return response()->json($slots);
    }

    /**
     * Retorna os próximos dias de funcionamento do profissional.
     *
     * GET /agendar/{slug}/dias?profissional_id=X
     */
    public function dias(string $slug, Request $request, AgendamentoDisponibilidadeService $disponibilidade): JsonResponse
    {
        $company = Company::where('slug', $slug)->firstOrFail();

        $request->validate([
            'profissional_id' => ['required', 'uuid'],
        ]);

        $profissional = Profissional::where('company_id', $company->id)
            ->ativo()
            ->findOrFail($request->profissional_id);

        return response()->json(
            $disponibilidade->diasFuncionamento($profissional, $company)
        );
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

        $inicio = Carbon::parse($request->data_hora);

        $lock = $disponibilidade->acquireLock($request->profissional_id, $inicio);

        if (! $lock->block(3)) {
            return back()->withInput()->withErrors([
                'data_hora' => 'Este horário está sendo reservado por outro cliente. Tente novamente em instantes.',
            ]);
        }

        try {
            $valida = $disponibilidade->validar($request->profissional_id, $servico, $inicio);

            if (! $valida['ok']) {
                return back()->withInput()->withErrors(['data_hora' => $valida['motivo']]);
            }

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

            if (! $cliente->lgpd_consent) {
                $cliente->update([
                    'lgpd_consent' => true,
                    'lgpd_consent_at' => now(),
                    'lgpd_consent_ip' => $request->ip(),
                ]);
            }

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

            $settings = $company->resolvedSettings();
            $sinalPct = (float) ($settings['advanced']['sinal_pct'] ?? 0);
            $asaasConfig = $settings['integrations']['asaas'] ?? [];
            $apiKey = trim($asaasConfig['api_key'] ?? '');
            $ambiente = $asaasConfig['ambiente'] ?? 'sandbox';
            $gatewayAtivo = ($settings['integrations']['gateway'] ?? 'nenhum') === 'asaas' && $apiKey !== '';

            // Status inicial: aguardando_sinal (com gateway) ou pendente (sem gateway)
            $statusInicial = ($sinalPct > 0 && $gatewayAtivo)
                ? Agendamento::STATUS_AGUARDANDO_SINAL
                : Agendamento::STATUS_PENDENTE;

            $sinalValor = $sinalPct > 0 ? round((float) $servico->preco * $sinalPct / 100, 2) : 0.0;

            $agendamento = Agendamento::create([
                'company_id' => $company->id,
                'cliente_id' => $cliente->id,
                'profissional_id' => $request->profissional_id,
                'servico_id' => $request->servico_id,
                'data_hora' => $request->data_hora,
                'duracao' => $servico->duracao_minutos,
                'valor' => $servico->preco,
                'status' => $statusInicial,
                'observacao' => $request->observacao,
                'cancel_token' => Agendamento::generateCancelToken(),
                'sinal_pct' => $sinalPct,
                'sinal_valor' => $sinalValor,
                'sinal_status' => $sinalPct > 0 ? Agendamento::SINAL_PENDENTE : Agendamento::SINAL_NENHUM,
            ]);
        } finally {
            $lock->release();
        }

        LinkVisit::track($company->id, LinkVisit::TYPE_BOOKING);

        // Fluxo com sinal via Asaas: criar cliente + cobrança e redirecionar
        if ($agendamento->status === Agendamento::STATUS_AGUARDANDO_SINAL) {
            $customerId = $this->garantirClienteAsaas($cliente, $apiKey, $ambiente);

            if ($customerId !== null) {
                $cliente->update(['asaas_customer_id' => $customerId]);

                $descricao = 'Sinal - '.$servico->nome.' em '.$agendamento->data_hora->format('d/m/Y H:i');
                $cobranca = AsaasService::criarCobrancaSinal($apiKey, $ambiente, $customerId, $agendamento->sinal_valor, $descricao, $agendamento->id);

                if ($cobranca['ok'] && ! empty($cobranca['payment_url'])) {
                    $agendamento->update([
                        'sinal_payment_id' => $cobranca['payment_id'],
                        'sinal_payment_url' => $cobranca['payment_url'],
                    ]);

                    return redirect()->away($cobranca['payment_url']);
                }
            }

            // Fallback se Asaas falhou: reverter para pendente com aprovação manual
            Log::warning('sinal: falha ao criar cobrança Asaas, revertendo para pendente', ['ag' => $agendamento->id]);
            $agendamento->update([
                'status' => Agendamento::STATUS_PENDENTE,
                'sinal_status' => Agendamento::SINAL_NENHUM,
                'aprovacao_manual' => true,
            ]);
        } else {
            if ($sinalPct > 0) {
                // Sinal configurado mas sem gateway Asaas → aprovação manual obrigatória.
                // Não auto-confirma: empresa precisa aprovar manualmente.
                $agendamento->update(['aprovacao_manual' => true]);
            } elseif (! ($settings['advanced']['confirm_required'] ?? false)) {
                // Sem sinal E sem exigência de confirmação → confirma automaticamente.
                $agendamento->update(['status' => Agendamento::STATUS_CONFIRMADO]);

                $agendamento->load(['cliente', 'profissional', 'servico', 'company']);

                if ($agendamento->cliente?->email) {
                    Mail::to($agendamento->cliente->email)
                        ->queue(new AgendamentoConfirmado($agendamento));
                }
            }
        }

        return redirect()->route('agendar.confirmado', ['slug' => $slug, 'agendamento' => $agendamento->id]);
    }

    /**
     * Callback após retorno do Asaas — exibe status do sinal.
     * GET /agendar/{slug}/sinal/{agendamento}/callback
     */
    public function pagamentoSinalCallback(string $slug, string $agendamento): View|RedirectResponse
    {
        $company = Company::where('slug', $slug)->firstOrFail();
        $ag = Agendamento::with(['servico', 'profissional', 'cliente'])
            ->where('company_id', $company->id)
            ->findOrFail($agendamento);

        // Se o webhook já confirmou → redirecionar para a tela de confirmado
        if ($ag->sinalPago() || $ag->status === Agendamento::STATUS_CONFIRMADO) {
            $ag->load('company');

            if ($ag->cliente?->email) {
                Mail::to($ag->cliente->email)
                    ->queue(new AgendamentoConfirmado($ag));
            }

            return redirect()->route('agendar.confirmado', ['slug' => $slug, 'agendamento' => $ag->id]);
        }

        return view('public.aguardando-pagamento', compact('company', 'ag'));
    }

    private function garantirClienteAsaas(Cliente $cliente, string $apiKey, string $ambiente): ?string
    {
        if ($cliente->asaas_customer_id) {
            return $cliente->asaas_customer_id;
        }

        $resultado = AsaasService::criarOuBuscarCliente($apiKey, $ambiente, $cliente->email ?? '', $cliente->name);

        return $resultado['ok'] ? $resultado['customer_id'] : null;
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
