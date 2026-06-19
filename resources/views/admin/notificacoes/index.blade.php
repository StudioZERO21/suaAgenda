@extends('layouts.app')
@section('title', 'Notificações — Twilio')
@section('page-title', 'Notificações — Twilio')

@section('content')
{{-- Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="{{ route('admin.dashboard') }}"
           style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text3);text-decoration:none;transition:all 150ms"
           onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
           onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div>
            <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">Notificações</h1>
            <p style="font-size:14px;color:var(--sa-text3);margin:0">Configuração Twilio — WhatsApp e SMS da plataforma</p>
        </div>
    </div>
    <a href="{{ route('admin.notificacoes.conversas') }}"
       style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;text-decoration:none;transition:border-color 180ms,color 180ms"
       onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
       onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        Ver Conversas
    </a>
</div>

<div style="max-width:760px;display:flex;flex-direction:column;gap:20px">

    {{-- ── Status da integração ─────────────────────────────────── --}}
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <h2 style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin:0 0 20px;display:flex;align-items:center;gap:8px">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.5a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.09a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0121.15 15z"/></svg>
            Status Twilio
        </h2>

        <div style="display:flex;flex-direction:column;gap:12px">
            {{-- Credenciais principais --}}
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;background:var(--sa-surface2);border-radius:10px;border:1px solid var(--sa-border)">
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:36px;height:36px;border-radius:9px;background:var(--sa-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                    </div>
                    <div>
                        <div style="font-size:14px;font-weight:600;color:var(--sa-text1)">Credenciais (SID / Token)</div>
                        <div style="font-size:12px;color:var(--sa-text3)">
                            @if($twilioConfigured)
                                SID: <code style="font-size:11px;background:var(--sa-surface);padding:1px 5px;border-radius:4px">{{ $twilioSid }}…</code>
                            @else
                                Configure TWILIO_SID e TWILIO_TOKEN no .env
                            @endif
                        </div>
                    </div>
                </div>
                @if($twilioConfigured)
                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(16,185,129,.12);color:#059669">
                    <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                    Configurado
                </span>
                @else
                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(107,114,128,.12);color:#6b7280">
                    <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                    Não configurado
                </span>
                @endif
            </div>

            {{-- WhatsApp --}}
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;background:var(--sa-surface2);border-radius:10px;border:1px solid var(--sa-border)">
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:36px;height:36px;border-radius:9px;background:#25d366;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="#fff"><path d="M20.52 3.449C18.24 1.245 15.24 0 12.045 0 5.463 0 .104 5.334.101 11.893c0 2.096.549 4.14 1.595 5.945L0 24l6.335-1.652c1.746.943 3.71 1.444 5.71 1.445h.006c6.585 0 11.946-5.336 11.949-11.896.001-3.176-1.24-6.165-3.48-8.448zm-8.475 18.3h-.004c-1.774 0-3.513-.474-5.03-1.37l-.36-.214-3.742.976.999-3.648-.235-.374c-.99-1.574-1.512-3.393-1.511-5.26.002-5.45 4.437-9.884 9.889-9.884 2.64 0 5.122 1.03 6.988 2.898 1.866 1.869 2.893 4.352 2.892 6.993-.003 5.451-4.437 9.883-9.886 9.883zm5.43-7.403c-.297-.149-1.758-.867-2.03-.967-.271-.099-.47-.148-.669.15-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414-.074-.124-.272-.198-.57-.347z"/></svg>
                    </div>
                    <div>
                        <div style="font-size:14px;font-weight:600;color:var(--sa-text1)">WhatsApp</div>
                        <div style="font-size:12px;color:var(--sa-text3)">
                            @if($twilioWhatsapp)
                                Número: <code style="font-size:11px;background:var(--sa-surface);padding:1px 5px;border-radius:4px">{{ $twilioWhatsappNumber }}</code>
                            @else
                                Configure TWILIO_WHATSAPP_NUMBER no .env
                            @endif
                        </div>
                    </div>
                </div>
                @if($twilioWhatsapp)
                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(16,185,129,.12);color:#059669">
                    <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                    Ativo
                </span>
                @else
                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(107,114,128,.12);color:#6b7280">
                    <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                    Inativo
                </span>
                @endif
            </div>

            {{-- SMS --}}
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;background:var(--sa-surface2);border-radius:10px;border:1px solid var(--sa-border)">
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:36px;height:36px;border-radius:9px;background:#4f46e5;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                    </div>
                    <div>
                        <div style="font-size:14px;font-weight:600;color:var(--sa-text1)">SMS</div>
                        <div style="font-size:12px;color:var(--sa-text3)">
                            @if($twilioSms)
                                Número: <code style="font-size:11px;background:var(--sa-surface);padding:1px 5px;border-radius:4px">{{ $twilioSmsNumber }}</code>
                            @else
                                Configure TWILIO_SMS_NUMBER no .env
                            @endif
                        </div>
                    </div>
                </div>
                @if($twilioSms)
                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(16,185,129,.12);color:#059669">
                    <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                    Ativo
                </span>
                @else
                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(107,114,128,.12);color:#6b7280">
                    <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                    Inativo
                </span>
                @endif
            </div>
        </div>

        {{-- Testar conexão --}}
        @if($twilioConfigured)
        <div style="margin-top:18px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <button type="button" id="btn-testar-conexao" onclick="testarConexao()"
                    style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:13px;font-weight:600;cursor:pointer;transition:border-color 180ms,color 180ms"
                    onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                    onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
                Testar Conexão
            </button>
            <span id="result-conexao" style="font-size:13px"></span>
        </div>
        @endif
    </div>

    {{-- ── Como configurar ─────────────────────────────────────── --}}
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <h2 style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin:0 0 16px;display:flex;align-items:center;gap:8px">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Como configurar
        </h2>

        <ol style="margin:0;padding:0 0 0 18px;display:flex;flex-direction:column;gap:10px;font-size:14px;color:var(--sa-text2);line-height:1.6">
            <li>Acesse <strong>console.twilio.com</strong> → <em>Account Info</em> para obter <code style="font-size:12px;background:var(--sa-surface2);padding:1px 5px;border-radius:4px">Account SID</code> e <code style="font-size:12px;background:var(--sa-surface2);padding:1px 5px;border-radius:4px">Auth Token</code></li>
            <li>Para WhatsApp sandbox: vá em <em>Messaging → Try it out → Send a WhatsApp message</em> e use o número <code style="font-size:12px;background:var(--sa-surface2);padding:1px 5px;border-radius:4px">+1 415 523 8886</code></li>
            <li>Para WhatsApp produção: solicite aprovação de número em <em>Messaging → Senders → WhatsApp senders</em></li>
            <li>Para SMS: compre um número em <em>Phone Numbers → Buy a number</em></li>
            <li>Adicione as variáveis abaixo ao <code style="font-size:12px;background:var(--sa-surface2);padding:1px 5px;border-radius:4px">.env</code> e reinicie o servidor:</li>
        </ol>

        <div style="margin-top:14px;background:var(--sa-primary);border-radius:8px;padding:14px 16px">
            <code style="font-size:12px;color:#e5e5e5;font-family:'Courier New',monospace;white-space:pre-line;line-height:1.8">TWILIO_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_TOKEN=seu_auth_token
TWILIO_WHATSAPP_NUMBER=14155238886
TWILIO_SMS_NUMBER=5511999990000</code>
        </div>
    </div>

    {{-- ── Envio de mensagem de teste ────────────────────────────── --}}
    @if($twilioConfigured)
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <h2 style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin:0 0 20px;display:flex;align-items:center;gap:8px">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            Enviar mensagem de teste
        </h2>

        <div style="display:flex;flex-direction:column;gap:20px">
            {{-- Campo de número --}}
            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
                    Número de destino <span style="color:var(--sa-secondary)">*</span>
                </label>
                <input type="tel" id="numero-teste" placeholder="55119999999999"
                       style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms"
                       onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'"
                       onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                <p style="font-size:12px;color:var(--sa-text3);margin-top:4px">Apenas dígitos, incluindo código do país e DDD (ex: 5511999990000)</p>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                {{-- Botão WhatsApp --}}
                @if($twilioWhatsapp)
                <button type="button" onclick="testarMensagem('whatsapp')"
                        style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:#25d366;color:#fff;transition:filter 200ms"
                        onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M20.52 3.449C18.24 1.245 15.24 0 12.045 0 5.463 0 .104 5.334.101 11.893c0 2.096.549 4.14 1.595 5.945L0 24l6.335-1.652c1.746.943 3.71 1.444 5.71 1.445h.006c6.585 0 11.946-5.336 11.949-11.896.001-3.176-1.24-6.165-3.48-8.448zm-8.475 18.3h-.004c-1.774 0-3.513-.474-5.03-1.37l-.36-.214-3.742.976.999-3.648-.235-.374c-.99-1.574-1.512-3.393-1.511-5.26.002-5.45 4.437-9.884 9.889-9.884 2.64 0 5.122 1.03 6.988 2.898 1.866 1.869 2.893 4.352 2.892 6.993-.003 5.451-4.437 9.883-9.886 9.883zm5.43-7.403c-.297-.149-1.758-.867-2.03-.967-.271-.099-.47-.148-.669.15-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414-.074-.124-.272-.198-.57-.347z"/></svg>
                    Testar WhatsApp
                </button>
                @else
                <button type="button" disabled
                        style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:1.5px solid var(--sa-border);cursor:not-allowed;font-size:14px;font-weight:600;background:transparent;color:var(--sa-text3);opacity:.5">
                    WhatsApp não configurado
                </button>
                @endif

                {{-- Botão SMS --}}
                @if($twilioSms)
                <button type="button" onclick="testarMensagem('sms')"
                        style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:#4f46e5;color:#fff;transition:filter 200ms"
                        onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                    Testar SMS
                </button>
                @else
                <button type="button" disabled
                        style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:1.5px solid var(--sa-border);cursor:not-allowed;font-size:14px;font-weight:600;background:transparent;color:var(--sa-text3);opacity:.5">
                    SMS não configurado
                </button>
                @endif

                <span id="result-mensagem" style="font-size:13px"></span>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Teste de e-mail SMTP ────────────────────────────────── --}}
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <h2 style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin:0 0 6px;display:flex;align-items:center;gap:8px">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            Testar envio de e-mail (SMTP)
        </h2>
        <p style="font-size:13px;color:var(--sa-text3);margin:0 0 18px">
            Usa as credenciais de e-mail salvas em
            <a href="{{ route('admin.configuracoes.index') }}" style="color:var(--sa-secondary);text-decoration:none">Configurações → E-mail</a>.
            Mailer atual: <code style="font-size:12px;background:var(--sa-surface2);padding:1px 5px;border-radius:4px">{{ config('mail.default', 'log') }}</code>
        </p>

        <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
            <div style="flex:1;min-width:220px">
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
                    Destinatário <span style="color:var(--sa-secondary)">*</span>
                </label>
                <input type="email" id="email-teste" value="{{ auth()->user()->email }}"
                       style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms;box-sizing:border-box"
                       onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'"
                       onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
            </div>
            <button type="button" id="btn-testar-email" onclick="testarEmail()"
                    style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:var(--sa-primary);color:#fff;transition:filter 200ms;white-space:nowrap"
                    onmouseover="this.style.filter='brightness(1.1)'"
                    onmouseout="this.style.filter='none'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Enviar e-mail de teste
            </button>
        </div>
        <span id="result-email" style="display:block;margin-top:10px;font-size:13px"></span>
    </div>

    {{-- ── Nota sobre fallback por empresa ─────────────────────── --}}
    <div style="padding:14px 18px;background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.25);border-radius:10px">
        <div style="display:flex;align-items:flex-start;gap:10px">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <p style="font-size:13px;color:#92400e;margin:0;line-height:1.6">
                <strong>Prioridade de envio:</strong> as credenciais Twilio da plataforma (acima) têm prioridade sobre as credenciais configuradas por empresa em
                <em>Configurações → Integrações</em>. Se a plataforma estiver configurada, as credenciais da empresa são ignoradas para WhatsApp.
            </p>
        </div>
    </div>

</div>

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

async function testarConexao() {
    const btn = document.getElementById('btn-testar-conexao');
    const res = document.getElementById('result-conexao');
    btn.disabled = true;
    res.style.color = 'var(--sa-text3)';
    res.textContent = 'Verificando…';

    try {
        const r = await fetch('{{ route('admin.notificacoes.testar-conexao') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const data = await r.json();
        if (data.ok) {
            res.style.color = '#059669';
            res.textContent = '✓ Conectado — ' + (data.friendly_name ?? '');
        } else {
            res.style.color = '#dc2626';
            res.textContent = '✗ ' + (data.erro ?? 'falha');
        }
    } catch {
        res.style.color = '#dc2626';
        res.textContent = '✗ Erro de rede';
    } finally {
        btn.disabled = false;
    }
}

async function testarEmail() {
    const email = document.getElementById('email-teste').value.trim();
    if (!email) {
        Swal.fire({ icon: 'warning', title: 'E-mail obrigatório', text: 'Digite o endereço de destino.', confirmButtonColor: 'var(--sa-primary)' });
        return;
    }

    const btn = document.getElementById('btn-testar-email');
    const res = document.getElementById('result-email');
    btn.disabled = true;
    res.style.color = 'var(--sa-text3)';
    res.textContent = 'Enviando…';

    try {
        const r = await fetch('{{ route('admin.notificacoes.testar-email') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ email }),
        });
        const data = await r.json();
        if (data.ok) {
            res.style.color = '#059669';
            res.textContent = '✓ E-mail enviado via ' + (data.mailer ?? '') + '!';
        } else {
            res.style.color = '#dc2626';
            res.textContent = '✗ Erro: ' + (data.erro ?? 'falha');
        }
    } catch {
        res.style.color = '#dc2626';
        res.textContent = '✗ Erro de rede';
    } finally {
        btn.disabled = false;
    }
}

async function testarMensagem(canal) {
    const numero = document.getElementById('numero-teste').value.trim();
    if (!numero) {
        Swal.fire({ icon: 'warning', title: 'Número obrigatório', text: 'Digite o número de destino antes de testar.', confirmButtonColor: 'var(--sa-primary)' });
        return;
    }

    const res = document.getElementById('result-mensagem');
    res.style.color = 'var(--sa-text3)';
    res.textContent = 'Enviando…';

    const url = canal === 'whatsapp'
        ? '{{ route('admin.notificacoes.testar-whatsapp') }}'
        : '{{ route('admin.notificacoes.testar-sms') }}';

    try {
        const r = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ numero }),
        });
        const data = await r.json();
        if (data.ok) {
            res.style.color = '#059669';
            res.textContent = '✓ Enviado! SID: ' + (data.sid ?? '');
        } else {
            res.style.color = '#dc2626';
            res.textContent = '✗ Erro: ' + (data.erro ?? 'falha');
        }
    } catch {
        res.style.color = '#dc2626';
        res.textContent = '✗ Erro de rede';
    }
}
</script>
@endpush
@endsection
