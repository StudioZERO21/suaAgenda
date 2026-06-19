@extends('layouts.app')
@section('title', 'Planos & Assinatura')
@section('page-title', 'Planos & Assinatura')

@section('content')
<x-sa.page>
    <x-sa.app-header title="Planos & Assinatura" subtitle="Gerencie seu plano e uso de mensagens" />
    <x-sa.body padding="24px 32px 0">

    {{-- Feedback do checkout Stripe --}}
    @if(session('billing_success'))
    <div style="display:flex;align-items:center;gap:12px;background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.25);border-radius:10px;padding:14px 18px;margin-bottom:20px">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><polyline points="20 6 9 17 4 12"/></svg>
        <span style="font-size:14px;font-weight:600;color:#059669">{{ session('billing_success') }}</span>
    </div>
    @endif
    @if(session('billing_info'))
    <div style="display:flex;align-items:center;gap:12px;background:rgba(107,114,128,.08);border:1px solid rgba(107,114,128,.2);border-radius:10px;padding:14px 18px;margin-bottom:20px">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span style="font-size:14px;color:var(--sa-text2)">{{ session('billing_info') }}</span>
    </div>
    @endif

    {{-- Banner plano atual — 4 colunas conforme PlansScreen.jsx --}}
    @if($company && $currentPlan)
    <div style="background:color-mix(in srgb,var(--sa-primary) 6%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 15%,transparent);border-radius:12px;padding:24px;margin-bottom:24px">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:24px;align-items:center">
            <div>
                <div style="font-size:11px;font-weight:700;color:var(--sa-text3);letter-spacing:1px;text-transform:uppercase;margin-bottom:6px">Plano Atual</div>
                <div style="font-family:var(--sa-font-heading);font-size:22px;font-weight:800;color:var(--sa-text1)">{{ $currentPlan->nome }}</div>
                <div style="font-size:13px;color:var(--sa-text3)">{{ $currentPlan->precoFormatado() }}/mês</div>
            </div>
            <div>
                <div style="font-size:11px;font-weight:700;color:var(--sa-text3);letter-spacing:1px;text-transform:uppercase;margin-bottom:6px">
                    {{ $emTrial ? 'Trial encerra em' : 'Próxima Cobrança' }}
                </div>
                <div style="font-size:18px;font-weight:700;color:var(--sa-text1)">{{ $proximaCobranca }}</div>
                <div style="font-size:13px;color:var(--sa-text3)">{{ $emTrial ? 'Período gratuito' : 'Renovação automática' }}</div>
            </div>
            <div>
                <div style="font-size:11px;font-weight:700;color:var(--sa-text3);letter-spacing:1px;text-transform:uppercase;margin-bottom:6px">Status</div>
                <div style="font-size:16px;font-weight:700;color:{{ $statusAssinatura['color'] }}">{{ $statusAssinatura['label'] }}</div>
                <div style="font-size:13px;color:var(--sa-text3)">{{ $statusAssinatura['sub'] }}</div>
            </div>
            <div style="display:flex;gap:8px;flex-direction:column">
                @if($company->stripe_subscription_id)
                <a href="https://billing.stripe.com" target="_blank" rel="noopener noreferrer"
                   style="display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:8px 14px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;transition:border-color 180ms,color 180ms;white-space:nowrap"
                   onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                   onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    Portal de Pagamento
                </a>
                @else
                <button type="button"
                        onclick="document.getElementById('comparar-planos')?.scrollIntoView({behavior:'smooth'})"
                        style="display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:8px 14px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:12px;font-weight:600;cursor:pointer;transition:border-color 180ms,color 180ms;white-space:nowrap"
                        onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                        onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    Escolher Plano
                </button>
                @endif
                <button type="button"
                        onclick="Swal.fire({title:'Cancelar assinatura?',text:'Esta ação requer confirmação. Entre em contato com o suporte em contato@suaagenda.com.br.',icon:'warning',confirmButtonColor:'#1a1a1a'})"
                        style="display:inline-flex;align-items:center;justify-content:center;padding:8px 14px;border-radius:8px;border:none;background:transparent;color:var(--sa-text3);font-size:12px;font-weight:600;cursor:pointer;transition:color 180ms"
                        onmouseover="this.style.color='var(--sa-text1)'"
                        onmouseout="this.style.color='var(--sa-text3)'">
                    Cancelar Assinatura
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Grid principal: uso + faturas (esq) | comparar planos (dir) --}}
    <div style="display:grid;grid-template-columns:380px 1fr;gap:24px;margin-bottom:40px;align-items:start">

        {{-- Coluna esquerda --}}
        <div>
            {{-- Uso de mensagens --}}
            <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05);margin-bottom:16px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
                    <h3 style="font-family:var(--sa-font-heading);font-size:15px;font-weight:600;color:var(--sa-text1);margin:0">Uso de Mensagens — {{ now()->translatedFormat('M/Y') }}</h3>
                    <span style="font-size:11px;color:var(--sa-text3)">Renova em {{ $diasRestantes }} dias</span>
                </div>

                @foreach($usage as $key => $u)
                @php
                    $unlimited = $u['limite'] < 0;
                    $noQuota = $u['limite'] === 0;
                    $pct = $noQuota ? 0 : ($unlimited ? 0 : min(round($u['usado'] / max($u['limite'], 1) * 100), 100));
                    $isDanger = !$unlimited && !$noQuota && $pct > 90;
                    $isWarn = !$unlimited && !$noQuota && $pct > 70 && !$isDanger;
                    $barColor = $isDanger ? '#ef4444' : ($isWarn ? '#f59e0b' : $u['cor']);
                    $restantes = $noQuota ? 0 : max($u['limite'] - $u['usado'], 0);
                @endphp
                <div style="margin-bottom:24px">
                    <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:8px">
                        <div>
                            <span style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $u['label'] }}</span>
                            @if($isWarn && !$isDanger)
                            <span style="margin-left:8px;font-size:11px;font-weight:600;color:#d97706;background:rgba(245,158,11,.1);border-radius:20px;padding:2px 8px">⚠ Atenção</span>
                            @elseif($isDanger)
                            <span style="margin-left:8px;font-size:11px;font-weight:600;color:#dc2626;background:rgba(239,68,68,.1);border-radius:20px;padding:2px 8px">🔴 Limite próximo</span>
                            @endif
                        </div>
                        <span style="font-size:13px;color:var(--sa-text3)">
                            @if($unlimited)
                                <span style="color:#10b981;font-weight:600">Ilimitado</span>
                            @elseif($noQuota)
                                <span style="color:var(--sa-text3)">Não incluso</span>
                            @else
                                {{ $u['usado'] }} / {{ $u['limite'] }} mensagens
                            @endif
                        </span>
                    </div>
                    @if(!$unlimited && !$noQuota)
                    <div style="height:10px;border-radius:5px;background:var(--sa-surface2);overflow:hidden;border:1px solid var(--sa-border)">
                        <div style="height:100%;border-radius:5px;background:{{ $barColor }};width:{{ $pct }}%;transition:width 900ms cubic-bezier(.4,0,.2,1)"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-top:5px">
                        <span style="font-size:12px;color:{{ $isDanger ? '#ef4444' : ($isWarn ? '#f59e0b' : 'var(--sa-text3)') }}">{{ $pct }}% utilizado</span>
                        <span style="font-size:12px;color:var(--sa-text3)">{{ $restantes }} restantes este mês</span>
                    </div>
                    @if($isWarn)
                    <div style="margin-top:8px;padding:8px 12px;border-radius:8px;background:{{ $isDanger ? 'rgba(239,68,68,.06)' : 'rgba(245,158,11,.06)' }};border:1px solid {{ $isDanger ? '#ef444430' : '#f59e0b30' }}">
                        <p style="font-size:12px;color:{{ $isDanger ? '#dc2626' : '#d97706' }};margin:0;line-height:1.6">
                            @if($isDanger)
                                ⚠️ Você atingirá o limite em ~{{ $diasRestantes }} dias. Considere fazer upgrade ou usar SMS.
                            @else
                                💡 Sugestão: Use SMS (R$ 0,08/msg) para notificações adicionais e preserve seu limite.
                            @endif
                        </p>
                    </div>
                    @endif
                    @elseif($noQuota)
                    <div style="height:10px;border-radius:5px;background:var(--sa-surface2);border:1px solid var(--sa-border)"></div>
                    <div style="margin-top:5px;font-size:12px;color:var(--sa-text3)">Disponível no plano Pro ou superior</div>
                    @endif
                </div>
                @endforeach

                <div style="background:var(--sa-surface2);border-radius:10px;padding:14px 16px;border:1px solid var(--sa-border)">
                    <div style="font-size:12px;font-weight:700;color:var(--sa-text1);margin-bottom:8px">💡 Dica para economizar</div>
                    <p style="font-size:12px;color:var(--sa-text3);margin:0 0 10px;line-height:1.6">
                        Use SMS (R$ 0,08/msg) como alternativa ao WhatsApp para lembretes simples.
                        @if(($usage['sms']['limite'] ?? 0) > 0)
                            Você tem {{ max(($usage['sms']['limite'] ?? 0) - ($usage['sms']['usado'] ?? 0), 0) }} SMS restantes.
                        @else
                            Notificações via e-mail são ilimitadas.
                        @endif
                    </p>
                    <div style="display:flex;gap:8px">
                        <button type="button"
                                onclick="Swal.fire({title:'Em breve',text:'Configuração de SMS em breve.',icon:'info',confirmButtonColor:'#1a1a1a'})"
                                style="padding:7px 14px;border-radius:8px;border:1.5px solid var(--sa-border);background:var(--sa-surface2);color:var(--sa-text2);font-size:12px;font-weight:600;cursor:pointer;transition:border-color 180ms,color 180ms"
                                onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                                onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                            Usar SMS
                        </button>
                        <button type="button"
                                onclick="document.getElementById('comparar-planos')?.scrollIntoView({behavior:'smooth'})"
                                style="padding:7px 14px;border-radius:8px;border:none;background:var(--sa-primary);color:#fff;font-size:12px;font-weight:600;cursor:pointer;transition:filter 200ms"
                                onmouseover="this.style.filter='brightness(1.1)'"
                                onmouseout="this.style.filter='none'">
                            Upgrade Pro
                        </button>
                    </div>
                </div>
            </div>

            {{-- Histórico de faturas --}}
            <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
                <h3 style="font-family:var(--sa-font-heading);font-size:14px;font-weight:600;color:var(--sa-text1);margin:0 0 14px">Histórico de Faturas</h3>
                @if($company && ($company->plano === 'trial' || $emTrial))
                <div style="text-align:center;padding:20px 8px;color:var(--sa-text3)">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 10px;display:block;opacity:.4"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <div style="font-size:14px;font-weight:600;color:var(--sa-text2);margin-bottom:4px">Período de Teste</div>
                    <p style="font-size:13px;margin:0;line-height:1.5">
                        Você está no período gratuito. Escolha um plano ao lado para ativar sua assinatura.
                    </p>
                </div>
                @else
                <div style="font-size:13px;color:var(--sa-text3);font-style:italic;padding:8px 0 12px">
                    Histórico de faturas em breve.
                </div>
                @endif
            </div>
        </div>

        {{-- Coluna direita: comparar planos --}}
        <div id="comparar-planos">
            <h3 style="font-family:var(--sa-font-heading);font-size:16px;font-weight:600;color:var(--sa-text1);margin:0 0 16px">Comparar Planos</h3>

            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px">
                @foreach($plans as $plan)
                @php
                    $isCurrent = $currentPlan?->slug === $plan->slug;
                    $planColor = $plan->color;
                @endphp
                <div style="background:{{ $isCurrent ? 'color-mix(in srgb,var(--sa-primary) 6%,transparent)' : 'var(--sa-surface)' }};border:2px solid {{ $isCurrent ? 'var(--sa-primary)' : ($plan->popular ? $planColor : 'var(--sa-border)') }};border-radius:16px;padding:24px;position:relative;overflow:hidden">

                    @if($plan->popular && !$isCurrent)
                    <div style="position:absolute;top:12px;right:-22px;background:{{ $planColor }};color:#fff;font-size:10px;font-weight:700;padding:3px 28px;transform:rotate(45deg);letter-spacing:1px;text-transform:uppercase">POPULAR</div>
                    @endif

                    @if($isCurrent)
                    <div style="position:absolute;top:14px;right:14px;font-size:10px;font-weight:700;color:var(--sa-primary);background:color-mix(in srgb,var(--sa-primary) 10%,transparent);border-radius:20px;padding:3px 10px;border:1px solid color-mix(in srgb,var(--sa-primary) 20%,transparent)">ATUAL</div>
                    @endif

                    <div style="width:36px;height:36px;border-radius:9px;background:{{ $planColor }}18;display:flex;align-items:center;justify-content:center;margin-bottom:14px">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="{{ $planColor }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>

                    <div style="font-family:var(--sa-font-heading);font-size:16px;font-weight:700;color:var(--sa-text1);margin-bottom:4px">{{ $plan->nome }}</div>
                    <div style="margin-bottom:16px">
                        <span style="font-family:var(--sa-font-heading);font-size:28px;font-weight:800;color:{{ $planColor }}">{{ $plan->precoFormatado() }}</span>
                        <span style="font-size:12px;color:var(--sa-text3)">/mês</span>
                    </div>

                    <div style="font-size:12px;color:var(--sa-text3);margin-bottom:14px;display:flex;flex-direction:column;gap:4px">
                        <div>👤 {{ $plan->max_profissionais === -1 ? 'Ilimitado' : $plan->max_profissionais }} profissional{{ $plan->max_profissionais !== 1 ? 'is' : '' }}</div>
                        <div>💬 {{ $plan->whatsapp_mensal === -1 ? 'WhatsApp ilimitado' : $plan->whatsapp_mensal.' msgs WhatsApp/mês' }}</div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:7px;margin-bottom:20px">
                        @foreach(array_slice($plan->features, 0, 5) as $feature)
                        <div style="display:flex;align-items:flex-start;gap:7px;font-size:12px;color:var(--sa-text2)">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><polyline points="20 6 9 17 4 12"/></svg>
                            {{ $feature }}
                        </div>
                        @endforeach
                        @if(count($plan->features) > 5)
                        <div style="font-size:11px;color:var(--sa-text3);padding-left:20px">+{{ count($plan->features) - 5 }} mais...</div>
                        @endif
                    </div>

                    @if($isCurrent)
                    <button disabled style="width:100%;padding:9px 16px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text3);font-size:13px;font-weight:600;cursor:not-allowed">
                        Plano Atual
                    </button>
                    @elsecan('update', $company)
                    @if($plan->stripe_price_id)
                    {{-- Checkout via Stripe --}}
                    <form method="POST" action="{{ route('planos.checkout', $plan->slug) }}">
                        @csrf
                        <button type="submit"
                                style="width:100%;padding:9px 16px;border-radius:8px;border:none;background:{{ $planColor }};color:#fff;font-size:13px;font-weight:600;cursor:pointer;transition:filter 200ms"
                                onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                            {{ $currentPlan && $plan->ordem > $currentPlan->ordem ? 'Fazer Upgrade' : 'Fazer Downgrade' }}
                        </button>
                    </form>
                    @else
                    {{-- Sem stripe_price_id: troca manual (dev/staging) --}}
                    <form method="POST" action="{{ route('planos.update') }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="plan_slug" value="{{ $plan->slug }}">
                        <button type="submit"
                                style="width:100%;padding:9px 16px;border-radius:8px;border:none;background:{{ $planColor }};color:#fff;font-size:13px;font-weight:600;cursor:pointer;transition:filter 200ms"
                                onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                            {{ $currentPlan && $plan->ordem > $currentPlan->ordem ? 'Fazer Upgrade' : 'Fazer Downgrade' }}
                        </button>
                    </form>
                    @endif
                    @endcan
                </div>
                @endforeach
            </div>

            <p style="font-size:12px;color:var(--sa-text3);text-align:center;margin-top:12px">
                Cancelamento sem multa a qualquer momento. Cobrança mensal via cartão de crédito.
            </p>
        </div>
    </div>

    </x-sa.body>
</x-sa.page>
@endsection
