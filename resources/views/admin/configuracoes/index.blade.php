@extends('layouts.app')
@section('title', 'Configurações de Plataforma')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="{{ route('admin.dashboard') }}"
           style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text3);text-decoration:none;transition:all 150ms"
           onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
           onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div>
            <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">Configurações de Plataforma</h1>
            <p style="font-size:14px;color:var(--sa-text3);margin:0">APIs e integrações — credenciais salvas criptografadas no banco</p>
        </div>
    </div>
</div>

<div x-data="{ aba: '{{ session()->hasAny(['success_stripe','success_twilio','success_mercadopago','success_asaas','success_email']) ? collect(['stripe','twilio','mercadopago','asaas','email'])->first(fn($g) => session()->has('success_'.$g)) : 'stripe' }}' }" style="display:grid;grid-template-columns:220px 1fr;gap:20px;max-width:1100px">

    {{-- ── Sidebar de abas ────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:4px">
        @foreach([
            ['stripe',      'Stripe',        '#635bff', '<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>'],
            ['mercadopago', 'Mercado Pago',  '#009ee3', '<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>'],
            ['asaas',       'Asaas',         '#ff6b35', '<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>'],
            ['twilio',      'Twilio',        '#f22f46', '<path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.5a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.09a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0121.15 15z"/>'],
            ['email',       'E-mail (SMTP)', '#059669', '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>'],
        ] as [$id, $label, $cor, $icon])
        <button @click="aba = '{{ $id }}'"
                :style="aba === '{{ $id }}'
                    ? 'background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:1.5px solid color-mix(in srgb,var(--sa-primary) 20%,transparent);color:var(--sa-text1)'
                    : 'background:var(--sa-surface);border:1.5px solid var(--sa-border);color:var(--sa-text2)'"
                style="display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:10px;cursor:pointer;font-size:13px;font-weight:600;font-family:'Inter',sans-serif;text-align:left;transition:all 150ms;width:100%">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" :stroke="aba === '{{ $id }}' ? 'var(--sa-primary)' : '{{ $cor }}'" stroke-width="2">{!! $icon !!}</svg>
            {{ $label }}
            @if(session('success_'.$id))
            <span style="margin-left:auto;width:7px;height:7px;border-radius:50%;background:#059669;flex-shrink:0"></span>
            @elseif($settings[$id] ?? [])
            <span style="margin-left:auto;width:7px;height:7px;border-radius:50%;background:rgba(16,185,129,.5);flex-shrink:0"></span>
            @else
            <span style="margin-left:auto;width:7px;height:7px;border-radius:50%;background:var(--sa-border);flex-shrink:0"></span>
            @endif
        </button>
        @endforeach
    </div>

    {{-- ── Conteúdo das abas ───────────────────────────── --}}
    <div>

        {{-- ════════ STRIPE ════════ --}}
        <div x-show="aba === 'stripe'" x-cloak>
            @if(session('success_stripe'))
            <div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:10px;padding:12px 18px;margin-bottom:16px;font-size:14px;color:#059669;display:flex;align-items:center;gap:8px">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                {{ session('success_stripe') }}
            </div>
            @endif
            <form method="POST" action="{{ route('admin.configuracoes.save', 'stripe') }}" style="display:flex;flex-direction:column;gap:16px">
                @csrf
                <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
                    <h2 style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin:0 0 20px;display:flex;align-items:center;gap:8px">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#635bff" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        Stripe — Faturamento SaaS
                    </h2>
                    <div style="display:flex;flex-direction:column;gap:16px">
                        @foreach([
                            ['secret','Secret Key','sk_live_... ou sk_test_...', true],
                            ['webhook_secret','Webhook Secret','whsec_...', true],
                        ] as [$key, $label, $ph, $secret])
                        <x-admin.setting-field :name="'stripe['.$key.']'" :label="$label" :placeholder="$ph" :secret="$secret" :value="$settings['stripe'][$key] ?? ''" />
                        @endforeach

                        <div style="border-top:1px solid var(--sa-border);padding-top:16px">
                            <p style="font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.5px;margin:0 0 12px">Price IDs dos Planos</p>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                                @foreach(['starter'=>'Starter','crescimento'=>'Crescimento','profissional'=>'Profissional','enterprise'=>'Enterprise'] as $slug => $nome)
                                <x-admin.setting-field :name="'stripe[price_'.$slug.']'" :label="$nome" placeholder="price_..." :secret="false" :value="$settings['stripe']['price_'.$slug] ?? ''" />
                                @endforeach
                            </div>
                        </div>

                        <div style="display:flex;align-items:center;gap:12px;padding-top:4px">
                            <button type="button" onclick="testar('stripe')"
                                    style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:13px;font-weight:600;cursor:pointer;transition:all 150ms"
                                    onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                                    onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
                                Testar conexão
                            </button>
                            <span id="result-stripe" style="font-size:13px"></span>
                        </div>
                    </div>
                </div>
                <x-admin.save-bar />
            </form>
        </div>

        {{-- ════════ MERCADO PAGO ════════ --}}
        <div x-show="aba === 'mercadopago'" x-cloak>
            @if(session('success_mercadopago'))
            <div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:10px;padding:12px 18px;margin-bottom:16px;font-size:14px;color:#059669;display:flex;align-items:center;gap:8px">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                {{ session('success_mercadopago') }}
            </div>
            @endif
            <form method="POST" action="{{ route('admin.configuracoes.save', 'mercadopago') }}" style="display:flex;flex-direction:column;gap:16px">
                @csrf
                <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
                    <h2 style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin:0 0 20px;display:flex;align-items:center;gap:8px">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#009ee3" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        Mercado Pago — OAuth Connect
                    </h2>

                    <div style="margin-bottom:16px">
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:6px">Ambiente</label>
                        <div style="display:flex;gap:16px">
                            @foreach(['sandbox'=>'Sandbox (testes)','producao'=>'Produção'] as $val => $lbl)
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;color:var(--sa-text2)">
                                <input type="radio" name="mercadopago[ambiente]" value="{{ $val }}"
                                       {{ ($settings['mercadopago']['ambiente'] ?? 'sandbox') === $val ? 'checked' : '' }}
                                       style="accent-color:var(--sa-primary);width:16px;height:16px">
                                {{ $lbl }}
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        @foreach([
                            ['client_id','Client ID','',false],
                            ['client_secret','Client Secret','',true],
                            ['public_key','Public Key (prod)','APP_USR-...',false],
                            ['access_token','Access Token (prod)','APP_USR-...',true],
                            ['public_key_test','Public Key (test)','TEST-...',false],
                            ['access_token_test','Access Token (test)','TEST-...',true],
                            ['webhook_secret','Webhook Secret','',true],
                        ] as [$key, $label, $ph, $secret])
                        <x-admin.setting-field :name="'mercadopago['.$key.']'" :label="$label" :placeholder="$ph" :secret="$secret" :value="$settings['mercadopago'][$key] ?? ''" />
                        @endforeach
                    </div>

                    <div style="margin-top:16px">
                        <x-admin.setting-field name="mercadopago[redirect_uri]" label="Redirect URI (OAuth)" :placeholder="url('/configuracoes/integracoes/mercadopago/callback')" :secret="false" :value="$settings['mercadopago']['redirect_uri'] ?? ''" />
                        <p style="font-size:11px;color:var(--sa-text3);margin-top:4px">Configure esta URL exatamente em: MP Developers → sua app → Redirect URL</p>
                    </div>

                    <div style="display:flex;align-items:center;gap:12px;margin-top:16px">
                        <button type="button" onclick="testar('mercadopago')"
                                style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:13px;font-weight:600;cursor:pointer;transition:all 150ms"
                                onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                                onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
                            Testar Token
                        </button>
                        <span id="result-mercadopago" style="font-size:13px"></span>
                    </div>
                </div>
                <x-admin.save-bar />
            </form>
        </div>

        {{-- ════════ ASAAS ════════ --}}
        <div x-show="aba === 'asaas'" x-cloak>
            @if(session('success_asaas'))
            <div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:10px;padding:12px 18px;margin-bottom:16px;font-size:14px;color:#059669;display:flex;align-items:center;gap:8px">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                {{ session('success_asaas') }}
            </div>
            @endif
            <form method="POST" action="{{ route('admin.configuracoes.save', 'asaas') }}" style="display:flex;flex-direction:column;gap:16px">
                @csrf
                <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
                    <h2 style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin:0 0 20px;display:flex;align-items:center;gap:8px">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ff6b35" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        Asaas — Gateway de Cobrança
                    </h2>

                    <div style="margin-bottom:16px">
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:6px">Ambiente</label>
                        <div style="display:flex;gap:16px">
                            @foreach(['sandbox'=>'Sandbox (testes)','producao'=>'Produção'] as $val => $lbl)
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;color:var(--sa-text2)">
                                <input type="radio" name="asaas[ambiente]" value="{{ $val }}"
                                       {{ ($settings['asaas']['ambiente'] ?? 'sandbox') === $val ? 'checked' : '' }}
                                       style="accent-color:var(--sa-primary);width:16px;height:16px">
                                {{ $lbl }}
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:12px">
                        <x-admin.setting-field name="asaas[api_key]" label="API Key" placeholder='$aact_...' :secret="true" :value="$settings['asaas']['api_key'] ?? ''" />
                        <x-admin.setting-field name="asaas[webhook_token]" label="Webhook Token" placeholder="Token de validação dos webhooks" :secret="false" :value="$settings['asaas']['webhook_token'] ?? ''" />
                        <p style="font-size:12px;color:var(--sa-text3);margin:0">
                            URL do webhook: <code style="background:var(--sa-surface2);padding:2px 6px;border-radius:4px;font-size:11px">{{ url('/webhooks/asaas') }}</code>
                        </p>
                    </div>
                </div>
                <x-admin.save-bar />
            </form>
        </div>

        {{-- ════════ TWILIO ════════ --}}
        <div x-show="aba === 'twilio'" x-cloak>
            @if(session('success_twilio'))
            <div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:10px;padding:12px 18px;margin-bottom:16px;font-size:14px;color:#059669;display:flex;align-items:center;gap:8px">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                {{ session('success_twilio') }}
            </div>
            @endif
            <form method="POST" action="{{ route('admin.configuracoes.save', 'twilio') }}" style="display:flex;flex-direction:column;gap:16px">
                @csrf
                <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
                    <h2 style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin:0 0 20px;display:flex;align-items:center;gap:8px">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f22f46" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.5a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.09a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0121.15 15z"/></svg>
                        Twilio — WhatsApp &amp; SMS
                    </h2>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        @foreach([
                            ['sid','Account SID','ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',false],
                            ['token','Auth Token','',true],
                            ['whatsapp_number','Número WhatsApp (FROM)','14155238886 — sandbox',false],
                            ['sms_number','Número SMS (FROM)','+5511999990000',false],
                        ] as [$key, $label, $ph, $secret])
                        <x-admin.setting-field :name="'twilio['.$key.']'" :label="$label" :placeholder="$ph" :secret="$secret" :value="$settings['twilio'][$key] ?? ''" />
                        @endforeach
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;margin-top:16px">
                        <button type="button" onclick="testar('twilio')"
                                style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:13px;font-weight:600;cursor:pointer;transition:all 150ms"
                                onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                                onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
                            Testar conexão
                        </button>
                        <span id="result-twilio" style="font-size:13px"></span>
                    </div>
                </div>
                <x-admin.save-bar />
            </form>
        </div>

        {{-- ════════ EMAIL ════════ --}}
        <div x-show="aba === 'email'" x-cloak>
            @if(session('success_email'))
            <div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:10px;padding:12px 18px;margin-bottom:16px;font-size:14px;color:#059669;display:flex;align-items:center;gap:8px">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                {{ session('success_email') }}
            </div>
            @endif
            <form method="POST" action="{{ route('admin.configuracoes.save', 'email') }}" style="display:flex;flex-direction:column;gap:16px">
                @csrf
                <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
                    <h2 style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin:0 0 20px;display:flex;align-items:center;gap:8px">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        E-mail — Configurações SMTP
                    </h2>

                    <div style="margin-bottom:16px">
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:6px">Driver</label>
                        <div style="display:flex;gap:16px;flex-wrap:wrap">
                            @foreach(['smtp'=>'SMTP','mailgun'=>'Mailgun','ses'=>'Amazon SES','log'=>'Log (dev)'] as $val => $lbl)
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;color:var(--sa-text2)">
                                <input type="radio" name="email[mailer]" value="{{ $val }}"
                                       {{ ($settings['email']['mailer'] ?? 'smtp') === $val ? 'checked' : '' }}
                                       style="accent-color:var(--sa-primary);width:16px;height:16px">
                                {{ $lbl }}
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        @foreach([
                            ['host','Host SMTP','smtp.gmail.com',false],
                            ['port','Porta','587',false],
                            ['username','Usuário / E-mail','',false],
                            ['password','Senha / App Password','',true],
                            ['from_address','Remetente (e-mail)','noreply@suaagenda.pro',false],
                            ['from_name','Remetente (nome)','suaAgenda',false],
                        ] as [$key, $label, $ph, $secret])
                        <x-admin.setting-field :name="'email['.$key.']'" :label="$label" :placeholder="$ph" :secret="$secret" :value="$settings['email'][$key] ?? ''" />
                        @endforeach

                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Criptografia</label>
                            <select name="email[encryption]"
                                    style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;cursor:pointer;transition:border-color 180ms"
                                    onfocus="this.style.borderColor='var(--sa-primary)'"
                                    onblur="this.style.borderColor='var(--sa-border)'">
                                @foreach(['tls'=>'TLS','ssl'=>'SSL',''=>'Nenhuma'] as $val => $lbl)
                                <option value="{{ $val }}" {{ ($settings['email']['encryption'] ?? 'tls') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="display:flex;align-items:center;gap:12px;margin-top:16px">
                        <button type="button" onclick="testar('email')"
                                style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:13px;font-weight:600;cursor:pointer;transition:all 150ms"
                                onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                                onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
                            Testar SMTP
                        </button>
                        <span id="result-email" style="font-size:13px"></span>
                    </div>
                </div>
                <x-admin.save-bar />
            </form>
        </div>

    </div>
</div>

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const ROUTES = {
    stripe:      '{{ route('admin.configuracoes.testar.stripe') }}',
    twilio:      '{{ route('admin.configuracoes.testar.twilio') }}',
    mercadopago: '{{ route('admin.configuracoes.testar.mercadopago') }}',
    email:       '{{ route('admin.configuracoes.testar.email') }}',
};

async function testar(canal) {
    const res = document.getElementById('result-' + canal);
    res.style.color = 'var(--sa-text3)';
    res.textContent = 'Verificando…';

    try {
        const r = await fetch(ROUTES[canal], {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await r.json();
        res.style.color = data.ok ? '#059669' : '#dc2626';
        res.textContent = data.ok ? '✓ ' + (data.nome ?? 'OK') : '✗ ' + (data.erro ?? 'Erro');
    } catch {
        res.style.color = '#dc2626';
        res.textContent = '✗ Erro de rede';
    }
}

// Mostra/oculta campos de senha
function toggleSecret(btn) {
    const inp = btn.previousElementSibling;
    inp.type = inp.type === 'password' ? 'text' : 'password';
}
</script>
@endpush
@endsection
