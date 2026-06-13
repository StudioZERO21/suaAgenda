@extends('layouts.public')
@section('title', 'Agendamento Confirmado')

@section('content')
<div style="text-align:center;padding:32px 0 20px">
    <div style="width:72px;height:72px;border-radius:50%;background:rgba(16,185,129,.12);display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <h1 style="font-family:var(--sa-font-heading);font-size:24px;font-weight:700;color:var(--sa-text1);margin-bottom:8px">Agendamento Recebido!</h1>
    <p style="font-size:15px;color:var(--sa-text3)">Em breve você receberá uma confirmação.</p>
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
        @if($ag->valor)
        <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--sa-border);padding-top:12px">
            <span style="font-size:13px;color:var(--sa-text3)">Valor</span>
            <span style="font-size:16px;font-weight:700;color:var(--sa-secondary)">R$ {{ number_format((float)$ag->valor, 2, ',', '.') }}</span>
        </div>
        @endif
    </div>

    <div style="margin-top:14px;padding:10px 14px;border-radius:8px;background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2)">
        <p style="font-size:12px;color:#d97706;margin:0">
            <strong>Aguardando confirmação</strong> — {{ $company->name }} entrará em contato para confirmar seu horário.
        </p>
    </div>
</div>

<div style="display:flex;flex-direction:column;align-items:center;gap:12px">
    @if($ag->cancel_token)
    <a href="{{ route('agendamento.meu', $ag->cancel_token) }}"
       style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;border-radius:8px;border:none;background:var(--sa-primary);color:#fff;font-size:14px;font-weight:600;text-decoration:none;transition:filter 200ms"
       onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Ver meu agendamento
    </a>
    @endif
    <a href="{{ route('agendar.show', $company->slug) }}"
       style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;text-decoration:none;transition:border-color 180ms,color 180ms"
       onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
       onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
        Fazer outro agendamento
    </a>
</div>
@endsection
