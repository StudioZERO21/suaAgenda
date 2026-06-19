@extends('layouts.app')
@section('title', 'WhatsApp da Empresa')

@section('content')
@php
    $statusLabel = match($status) {
        'open'           => ['Conectado', '#059669', 'rgba(16,185,129,.12)'],
        'connecting'     => ['Conectando...', '#d97706', 'rgba(245,158,11,.12)'],
        'close'          => ['Desconectado', '#dc2626', 'rgba(239,68,68,.1)'],
        'not_configured' => ['Evolution não configurado', '#6b7280', 'rgba(107,114,128,.1)'],
        default          => ['Desconhecido', '#6b7280', 'rgba(107,114,128,.1)'],
    };
@endphp

<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
    <a href="{{ route('configuracoes') }}"
       style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text3);text-decoration:none"
       onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
       onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <div>
        <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">WhatsApp da Empresa</h1>
        <p style="font-size:14px;color:var(--sa-text3);margin:0">Conecte o próprio número da empresa para enviar notificações</p>
    </div>
</div>

@if(session('success'))
<div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:10px;padding:12px 18px;margin-bottom:16px;font-size:14px;color:#059669;display:flex;align-items:center;gap:8px">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    {{ session('success') }}
</div>
@endif

<div style="max-width:800px;display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">

    {{-- ── Painel de conexão ─────────────────────────────────── --}}
    <div x-data="evolutionPanel()" x-init="init()" style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px;box-shadow:0 1px 3px rgba(0,0,0,.05)">

        <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#25d366">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
            <h2 style="font-family:'Poppins',sans-serif;font-size:16px;font-weight:700;color:var(--sa-text1);margin:0">Conexão WhatsApp</h2>
        </div>

        {{-- Status atual --}}
        <div style="margin-bottom:20px">
            <span x-bind:style="`display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;font-size:13px;font-weight:600;background:${statusBg};color:${statusCor}`">
                <span x-bind:style="`width:6px;height:6px;border-radius:50%;background:currentColor;flex-shrink:0`"></span>
                <span x-text="statusLabel"></span>
            </span>
        </div>

        {{-- QR Code --}}
        <div x-show="qr" style="margin-bottom:20px;text-align:center">
            <p style="font-size:13px;color:var(--sa-text2);margin:0 0 12px">Abra o <strong>WhatsApp</strong> no celular → <strong>Aparelhos conectados</strong> → <strong>Conectar aparelho</strong> → escaneie o QR:</p>
            <div style="display:inline-block;padding:12px;background:#fff;border-radius:12px;border:1px solid var(--sa-border)">
                <img x-bind:src="`data:image/png;base64,${qr}`" width="200" height="200" alt="QR Code WhatsApp" style="display:block">
            </div>
            <p style="font-size:11px;color:var(--sa-text3);margin-top:8px">O QR expira em ~30 segundos. Aguardando conexão...</p>
        </div>

        {{-- Conectado --}}
        <div x-show="status === 'open'" style="background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.2);border-radius:10px;padding:14px;margin-bottom:16px">
            <p style="font-size:13px;color:#059669;margin:0;font-weight:600">✓ WhatsApp conectado! As notificações da empresa serão enviadas pelo seu próprio número.</p>
        </div>

        {{-- Botões --}}
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <button x-show="status !== 'open'" @click="conectar()" x-bind:disabled="carregando"
                    style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:var(--sa-primary);color:#fff;transition:filter 200ms"
                    onmouseover="this.style.filter='brightness(1.1)'"
                    onmouseout="this.style.filter='none'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07"/><path d="M2 2l20 20"/></svg>
                <span x-text="carregando ? 'Aguarde...' : 'Conectar WhatsApp'"></span>
            </button>

            @if($company->evolution_connected)
            <form method="POST" action="{{ route('configuracoes.whatsapp.desconectar') }}" onsubmit="return confirm('Desconectar o WhatsApp desta empresa?')">
                @csrf @method('DELETE')
                <button type="submit"
                        style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:1.5px solid rgba(239,68,68,.4);background:transparent;cursor:pointer;font-size:14px;font-weight:600;color:#dc2626;transition:all 150ms"
                        onmouseover="this.style.borderColor='#dc2626'"
                        onmouseout="this.style.borderColor='rgba(239,68,68,.4)'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 11-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
                    Desconectar
                </button>
            </form>
            @endif
        </div>

        <p x-show="erro" x-text="erro" style="font-size:13px;color:#dc2626;margin-top:10px"></p>
    </div>

    {{-- ── Como funciona ─────────────────────────────────────── --}}
    <div>
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05);margin-bottom:16px">
            <h3 style="font-family:'Poppins',sans-serif;font-size:14px;font-weight:700;color:var(--sa-text1);margin:0 0 14px">Como funciona?</h3>
            <div style="display:flex;flex-direction:column;gap:12px">
                @foreach([
                    ['1', 'Clique em "Conectar WhatsApp"', 'Um QR code será gerado'],
                    ['2', 'Abra o WhatsApp no celular', 'Aparelhos conectados → Conectar aparelho'],
                    ['3', 'Escaneie o QR code', 'Seu número fica vinculado ao sistema'],
                    ['4', 'Pronto!', 'Notificações saem pelo seu número — sem custo por mensagem'],
                ] as [$n, $titulo, $desc])
                <div style="display:flex;gap:10px">
                    <div style="width:22px;height:22px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;margin-top:1px">{{ $n }}</div>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--sa-text1)">{{ $titulo }}</div>
                        <div style="font-size:12px;color:var(--sa-text3)">{{ $desc }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Uso do mês --}}
        @php
            $usageStatus = \App\Services\NotificationUsageService::statusMes(auth()->user()->company);
        @endphp
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <h3 style="font-family:'Poppins',sans-serif;font-size:14px;font-weight:700;color:var(--sa-text1);margin:0 0 14px">Uso este mês</h3>
            @foreach(['whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'email' => 'E-mail'] as $canal => $label)
            @php $u = $usageStatus[$canal]; @endphp
            <div style="margin-bottom:12px">
                <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                    <span style="font-size:12px;font-weight:600;color:var(--sa-text2)">{{ $label }}</span>
                    <span style="font-size:12px;color:{{ $u['esgotado'] ? '#dc2626' : ($u['alerta'] ? '#d97706' : 'var(--sa-text3)') }};font-weight:600">
                        {{ $u['usado'] }} / {{ $u['limite'] === -1 ? '∞' : $u['limite'] }}
                    </span>
                </div>
                <div style="background:var(--sa-border);border-radius:4px;height:6px;overflow:hidden">
                    @php
                        $pct = $u['limite'] === -1 ? 0 : min(100, $u['percentual']);
                        $cor = $u['esgotado'] ? '#ef4444' : ($u['alerta'] ? '#f59e0b' : '#059669');
                    @endphp
                    <div style="width:{{ $pct }}%;height:6px;background:{{ $cor }};border-radius:4px;transition:width 400ms"></div>
                </div>
                @if($u['esgotado'])
                <p style="font-size:11px;color:#dc2626;margin:3px 0 0">Limite atingido. Contate o suporte para ampliar.</p>
                @elseif($u['alerta'])
                <p style="font-size:11px;color:#d97706;margin:3px 0 0">Atenção: {{ $u['percentual'] }}% do limite usado.</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script>
function evolutionPanel() {
    return {
        status:      '{{ $status }}',
        statusLabel: '{{ $statusLabel[0] }}',
        statusCor:   '{{ $statusLabel[1] }}',
        statusBg:    '{{ $statusLabel[2] }}',
        qr:          null,
        carregando:  false,
        erro:        null,
        poll:        null,

        init() {
            if (this.status === 'connecting') {
                this.iniciarPoll();
            }
        },

        async conectar() {
            this.carregando = true;
            this.erro = null;
            this.qr = null;
            try {
                const r = await fetch('{{ route('configuracoes.whatsapp.conectar') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                });
                const data = await r.json();
                if (data.ok && data.qr) {
                    this.qr = data.qr;
                    this.status = 'connecting';
                    this.statusLabel = 'Aguardando scan...';
                    this.statusCor = '#d97706';
                    this.statusBg = 'rgba(245,158,11,.12)';
                    this.iniciarPoll();
                } else {
                    this.erro = data.erro ?? 'Erro ao gerar QR code.';
                }
            } catch {
                this.erro = 'Erro de rede.';
            }
            this.carregando = false;
        },

        iniciarPoll() {
            clearInterval(this.poll);
            this.poll = setInterval(() => this.verificarStatus(), 4000);
        },

        async verificarStatus() {
            try {
                const r = await fetch('{{ route('configuracoes.whatsapp.status') }}', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await r.json();
                this.status = data.status;

                if (data.status === 'open') {
                    clearInterval(this.poll);
                    this.qr = null;
                    this.statusLabel = 'Conectado';
                    this.statusCor = '#059669';
                    this.statusBg = 'rgba(16,185,129,.12)';
                    // Recarrega para mostrar botão desconectar
                    setTimeout(() => location.reload(), 1500);
                } else if (data.qr) {
                    this.qr = data.qr;
                }
            } catch {}
        },
    };
}
</script>
@endpush
@endsection
