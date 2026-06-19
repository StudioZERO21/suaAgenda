@extends('layouts.app')
@section('title', 'Config. Gateway')
@section('page-title', 'Config. Gateway')

@section('content')
{{-- Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="{{ route('admin.billing.index') }}"
           style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text3);text-decoration:none;transition:all 150ms"
           onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
           onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div>
            <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">Configuração de Gateway</h1>
            <p style="font-size:14px;color:var(--sa-text3);margin:0">Credenciais Asaas, períodos de graça e canais de notificação</p>
        </div>
    </div>
</div>

@if(session('success'))
<div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:10px;padding:12px 18px;margin-bottom:20px;font-size:14px;color:#059669;display:flex;align-items:center;gap:8px">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    {{ session('success') }}
</div>
@endif

<div style="max-width:760px">
    <form method="POST" action="{{ route('admin.billing.gateway.save') }}" style="display:flex;flex-direction:column;gap:20px">
        @csrf

        {{-- Gateway Asaas --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <h2 style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin:0 0 20px;display:flex;align-items:center;gap:8px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                Asaas
            </h2>

            <div style="display:flex;flex-direction:column;gap:18px">
                {{-- Ambiente --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
                        Ambiente <span style="color:var(--sa-secondary)">*</span>
                    </label>
                    <div style="display:flex;gap:12px">
                        @foreach(['sandbox' => 'Sandbox (testes)', 'producao' => 'Produção'] as $val => $label)
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;color:var(--sa-text2)">
                            <input type="radio" name="asaas_ambiente" value="{{ $val }}"
                                   {{ (($billingConfig->credentials['asaas_ambiente'] ?? 'sandbox') === $val) ? 'checked' : '' }}
                                   style="accent-color:var(--sa-primary);width:16px;height:16px">
                            {{ $label }}
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- API Key --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
                        API Key <span style="color:var(--sa-secondary)">*</span>
                    </label>
                    <div style="position:relative">
                        <input type="password" name="asaas_api_key" id="api-key-input"
                               value="{{ $billingConfig->credentials['asaas_api_key'] ?? '' }}"
                               placeholder="$aact_..."
                               autocomplete="off"
                               style="width:100%;padding:10px 42px 10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:monospace;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms"
                               onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'"
                               onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                        <button type="button" onclick="toggleVisibility()"
                                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--sa-text3);display:flex;align-items:center">
                            <svg id="eye-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <p style="font-size:12px;color:var(--sa-text3);margin-top:5px">Encontre em Conta Asaas → Configurações → Integrações</p>
                </div>

                {{-- Webhook token --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
                        Webhook Token
                    </label>
                    <input type="text" name="webhook_token"
                           value="{{ $billingConfig->credentials['webhook_token'] ?? '' }}"
                           placeholder="Token de validação dos webhooks do Asaas"
                           autocomplete="off"
                           style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:monospace;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms"
                           onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'"
                           onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    <p style="font-size:12px;color:var(--sa-text3);margin-top:5px">
                        URL do webhook: <code style="background:var(--sa-surface2);padding:2px 6px;border-radius:4px;font-size:11px">{{ url('/webhooks/asaas') }}</code>
                    </p>
                </div>

                {{-- Testar conexão --}}
                <div>
                    <button type="button" id="btn-testar"
                            onclick="testarGateway()"
                            style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:13px;font-weight:600;cursor:pointer;transition:border-color 180ms,color 180ms"
                            onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                            onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
                        Testar Conexão
                    </button>
                    <span id="test-result" style="margin-left:10px;font-size:13px"></span>
                </div>
            </div>
        </div>

        {{-- Períodos de graça --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <h2 style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin:0 0 20px;display:flex;align-items:center;gap:8px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Períodos de Graça
            </h2>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
                @foreach([
                    ['grace_warning_days', 'Aviso (dias)', 'Dias após vencimento para enviar alerta', 3],
                    ['grace_suspend_days', 'Suspender (dias)', 'Dias até suspender o acesso', 7],
                    ['grace_cancel_days', 'Cancelar (dias)', 'Dias até cancelar definitivamente', 30],
                ] as [$field, $label, $hint, $default])
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">{{ $label }}</label>
                    <input type="number" name="{{ $field }}" min="1" max="90"
                           value="{{ $billingConfig->{$field} ?? $default }}"
                           style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms"
                           onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'"
                           onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    <p style="font-size:11px;color:var(--sa-text3);margin-top:4px">{{ $hint }}</p>
                </div>
                @endforeach
            </div>

            <div style="margin-top:18px;padding:14px 16px;background:var(--sa-surface2);border-radius:8px;border:1px solid var(--sa-border)">
                <p style="font-size:12px;color:var(--sa-text3);margin:0;line-height:1.6">
                    <strong style="color:var(--sa-text2)">Fluxo:</strong>
                    fatura vencida → aviso em <span id="preview-warn">{{ $billingConfig->grace_warning_days ?? 3 }}</span>d →
                    suspensão em <span id="preview-sus">{{ $billingConfig->grace_suspend_days ?? 7 }}</span>d →
                    cancelamento em <span id="preview-can">{{ $billingConfig->grace_cancel_days ?? 30 }}</span>d.
                    Trial expira diretamente para suspensão.
                </p>
            </div>
        </div>

        {{-- Canais de notificação de cobrança --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <h2 style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin:0 0 20px;display:flex;align-items:center;gap:8px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.5a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.09a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0121.15 15z"/></svg>
                Notificações de Cobrança
            </h2>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
                        Canal — Aviso/Cobrança normal
                    </label>
                    <select name="notification_channel_billing"
                            style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;cursor:pointer;transition:border-color 180ms"
                            onfocus="this.style.borderColor='var(--sa-primary)'"
                            onblur="this.style.borderColor='var(--sa-border)'">
                        <option value="email" @selected(($billingConfig->notification_channel_billing ?? 'email') === 'email')>E-mail</option>
                        <option value="whatsapp" @selected(($billingConfig->notification_channel_billing ?? '') === 'whatsapp')>WhatsApp</option>
                        <option value="sms" @selected(($billingConfig->notification_channel_billing ?? '') === 'sms')>SMS</option>
                    </select>
                    <p style="font-size:11px;color:var(--sa-text3);margin-top:4px">Usado para aviso de vencimento e cobrança recorrente</p>
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
                        Canal — Cancelamento
                    </label>
                    <select name="notification_channel_cancel"
                            style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;cursor:pointer;transition:border-color 180ms"
                            onfocus="this.style.borderColor='var(--sa-primary)'"
                            onblur="this.style.borderColor='var(--sa-border)'">
                        <option value="email" @selected(($billingConfig->notification_channel_cancel ?? 'whatsapp') === 'email')>E-mail</option>
                        <option value="whatsapp" @selected(($billingConfig->notification_channel_cancel ?? 'whatsapp') === 'whatsapp')>WhatsApp</option>
                        <option value="sms" @selected(($billingConfig->notification_channel_cancel ?? '') === 'sms')>SMS</option>
                    </select>
                    <p style="font-size:11px;color:var(--sa-text3);margin-top:4px">Usado ao cancelar após período de inadimplência</p>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div style="display:flex;gap:10px;padding-top:4px">
            <button type="submit"
                    style="display:inline-flex;align-items:center;gap:7px;padding:11px 22px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:var(--sa-primary);color:#fff;transition:filter 200ms"
                    onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                Salvar Configurações
            </button>
            <a href="{{ route('admin.billing.index') }}"
               style="display:inline-flex;align-items:center;gap:7px;padding:11px 22px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;text-decoration:none;transition:border-color 180ms,color 180ms"
               onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
               onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">Cancelar</a>
        </div>
    </form>

    {{-- ── MERCADO PAGO — AMBIENTE ──────────────────────────────── --}}
    @php
        $mpAmbiente = $billingConfig->credentials['mp_ambiente']
            ?? config('services.mercadopago.ambiente', 'sandbox');
        $isSandbox = $mpAmbiente === 'sandbox';
    @endphp
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
            <div>
                <div style="display:flex;align-items:center;gap:9px;margin-bottom:4px">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#009ee3" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    <span style="font-size:15px;font-weight:700;color:var(--sa-text1);font-family:var(--sa-font-heading)">Mercado Pago — Ambiente</span>
                </div>
                <p style="font-size:13px;color:var(--sa-text2);margin:0;line-height:1.5">
                    Em <strong>Sandbox</strong> nenhuma cobrança real é feita — use para testes. Mude para <strong>Produção</strong> ao subir para o ar.
                </p>
            </div>

            {{-- Badge ambiente atual --}}
            <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:13px;font-weight:700;
                         {{ $isSandbox ? 'background:rgba(245,158,11,.12);color:#d97706' : 'background:rgba(16,185,129,.12);color:#059669' }}">
                <span style="width:7px;height:7px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                {{ $isSandbox ? 'Sandbox (testes)' : 'Produção' }}
            </span>
        </div>

        <div style="border-top:1px solid var(--sa-border);margin:18px 0"></div>

        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            {{-- Botão → Sandbox --}}
            <form method="POST" action="{{ route('billing.gateway.mp-ambiente') }}" style="display:inline">
                @csrf
                <input type="hidden" name="mp_ambiente" value="sandbox">
                <button type="submit"
                    style="display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;font-family:var(--sa-font-body);transition:all 150ms;
                           {{ $isSandbox ? 'background:rgba(245,158,11,.12);color:#d97706;border:1.5px solid rgba(245,158,11,.4)' : 'background:transparent;color:var(--sa-text2);border:1.5px solid var(--sa-border)' }}"
                    {{ $isSandbox ? 'disabled' : '' }}
                    @if(!$isSandbox)
                    onmouseover="this.style.borderColor='#d97706';this.style.color='#d97706'"
                    onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'"
                    @endif>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Sandbox (testes)
                </button>
            </form>

            {{-- Botão → Produção --}}
            <form method="POST" action="{{ route('billing.gateway.mp-ambiente') }}" style="display:inline"
                  onsubmit="return confirm('Mudar para Produção? Cobranças reais serão realizadas!')">
                @csrf
                <input type="hidden" name="mp_ambiente" value="producao">
                <button type="submit"
                    style="display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;font-family:var(--sa-font-body);transition:all 150ms;
                           {{ !$isSandbox ? 'background:rgba(16,185,129,.12);color:#059669;border:1.5px solid rgba(16,185,129,.4)' : 'background:transparent;color:var(--sa-text2);border:1.5px solid var(--sa-border)' }}"
                    {{ !$isSandbox ? 'disabled' : '' }}
                    @if($isSandbox)
                    onmouseover="this.style.borderColor='#059669';this.style.color='#059669'"
                    onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'"
                    @endif>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    Produção
                </button>
            </form>

            @if(!$isSandbox)
            <span style="font-size:12px;color:#dc2626;display:flex;align-items:center;gap:5px">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Modo produção ativo — pagamentos reais habilitados
            </span>
            @endif
        </div>
    </div>
</div>

<script>
function toggleVisibility() {
    const inp = document.getElementById('api-key-input');
    inp.type = inp.type === 'password' ? 'text' : 'password';
}

function testarGateway() {
    const btn = document.getElementById('btn-testar');
    const res = document.getElementById('test-result');
    btn.disabled = true;
    res.style.color = 'var(--sa-text3)';
    res.textContent = 'Testando…';

    fetch('{{ route('admin.billing.gateway.testar') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            res.style.color = '#059669';
            res.textContent = '✓ Conectado — ' + (data.nome ?? '');
        } else {
            res.style.color = '#dc2626';
            res.textContent = '✗ Erro: ' + (data.erro ?? 'falha na conexão');
        }
    })
    .catch(() => {
        res.style.color = '#dc2626';
        res.textContent = '✗ Erro de rede';
    })
    .finally(() => { btn.disabled = false; });
}

// Live preview grace period
(function() {
    const fields = {
        grace_warning_days: 'preview-warn',
        grace_suspend_days: 'preview-sus',
        grace_cancel_days: 'preview-can',
    };
    Object.entries(fields).forEach(([name, id]) => {
        const el = document.querySelector(`[name="${name}"]`);
        if (el) el.addEventListener('input', () => {
            document.getElementById(id).textContent = el.value;
        });
    });
})();
</script>
@endsection
