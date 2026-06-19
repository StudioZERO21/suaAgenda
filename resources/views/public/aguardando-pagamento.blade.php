@extends('layouts.public')
@section('title', 'Aguardando Pagamento do Sinal')

@section('content')
<div style="text-align:center;padding:32px 0 20px">
    <div style="width:72px;height:72px;border-radius:50%;background:rgba(245,158,11,.12);display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </div>
    <h1 style="font-family:var(--sa-font-heading);font-size:24px;font-weight:700;color:var(--sa-text1);margin-bottom:8px">Aguardando pagamento do sinal</h1>
    <p style="font-size:15px;color:var(--sa-text3)">Seu horário está reservado por até 10 minutos.</p>
</div>

<div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05);margin-bottom:20px">
    <h2 style="font-size:13px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.06em;margin:0 0 16px">Resumo do Agendamento</h2>

    <div style="display:flex;flex-direction:column;gap:12px">
        <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:13px;color:var(--sa-text3)">Serviço</span>
            <span style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $ag->servico?->nome ?? '—' }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:13px;color:var(--sa-text3)">Profissional</span>
            <span style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $ag->profissional?->name ?? '—' }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:13px;color:var(--sa-text3)">Data e Hora</span>
            <span style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $ag->data_hora->format('d/m/Y \à\s H:i') }}</span>
        </div>
        <div style="border-top:1px solid var(--sa-border);padding-top:12px;display:flex;flex-direction:column;gap:8px">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:13px;color:var(--sa-text3)">Valor total</span>
                <span style="font-size:14px;font-weight:600;color:var(--sa-text1)">R$ {{ number_format((float)$ag->valor, 2, ',', '.') }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:13px;color:var(--sa-text3)">Sinal a pagar ({{ number_format((float)$ag->sinal_pct, 0) }}%)</span>
                <span style="font-size:18px;font-weight:700;color:var(--sa-secondary)">R$ {{ number_format((float)$ag->sinal_valor, 2, ',', '.') }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:13px;color:var(--sa-text3)">Restante no dia</span>
                <span style="font-size:14px;font-weight:600;color:var(--sa-text2)">R$ {{ number_format($ag->saldoDevido(), 2, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div style="margin-top:14px;padding:10px 14px;border-radius:8px;background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2)">
        <p style="font-size:12px;color:#d97706;margin:0">
            <strong>Atenção:</strong> o horário será liberado automaticamente se o sinal não for pago em 10 minutos.
        </p>
    </div>
</div>

<div style="display:flex;flex-direction:column;align-items:center;gap:12px">
    @if($ag->sinal_payment_url)
    <a href="{{ $ag->sinal_payment_url }}" target="_blank"
       style="display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border-radius:8px;border:none;background:var(--sa-secondary);color:#fff;font-size:15px;font-weight:700;text-decoration:none;transition:filter 200ms"
       onmouseover="this.style.filter='brightness(1.08)'" onmouseout="this.style.filter='none'">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Pagar sinal agora — R$ {{ number_format((float)$ag->sinal_valor, 2, ',', '.') }}
    </a>
    @endif

    <a href="{{ route('agendar.sinal.callback', ['slug' => $company->slug, 'agendamento' => $ag->id]) }}"
       style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;text-decoration:none;transition:border-color 180ms,color 180ms"
       onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
       onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.5"/></svg>
        Já paguei — verificar status
    </a>

    <a href="{{ route('vitrine.show', $company->slug) }}"
       style="font-size:13px;color:var(--sa-text3);text-decoration:none"
       onmouseover="this.style.color='var(--sa-text2)'" onmouseout="this.style.color='var(--sa-text3)'">
        Cancelar e voltar
    </a>
</div>
@endsection
