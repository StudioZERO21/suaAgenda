@extends('layouts.public')
@section('title', 'Meu Agendamento — ' . $company->name)

@section('header-right')
<div style="margin-left:auto;font-size:13px;color:rgba(255,255,255,.6)">{{ $company->name }}</div>
@endsection

@section('content')

@php
    $statusConfig = [
        'pendente'       => ['label' => 'Aguardando confirmação', 'color' => '#d97706', 'bg' => 'rgba(245,158,11,.1)', 'border' => 'rgba(245,158,11,.2)'],
        'confirmado'     => ['label' => 'Confirmado',             'color' => '#059669', 'bg' => 'rgba(16,185,129,.1)',  'border' => 'rgba(16,185,129,.2)'],
        'em_atendimento' => ['label' => 'Em atendimento',         'color' => '#6366f1', 'bg' => 'rgba(99,102,241,.1)', 'border' => 'rgba(99,102,241,.2)'],
        'finalizado'     => ['label' => 'Finalizado',             'color' => '#6b7280', 'bg' => 'rgba(107,114,128,.1)','border' => 'rgba(107,114,128,.2)'],
        'cancelado'      => ['label' => 'Cancelado',              'color' => '#dc2626', 'bg' => 'rgba(239,68,68,.1)',  'border' => 'rgba(239,68,68,.2)'],
    ];
    $sc = $statusConfig[$ag->status] ?? $statusConfig['pendente'];
    $isCanceled = $ag->status === 'cancelado';
@endphp

{{-- Flash messages --}}
@if(session('cancelado'))
<div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:10px">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    <span style="font-size:14px;color:#059669;font-weight:600">Agendamento cancelado com sucesso.</span>
</div>
@endif
@if(session('erro'))
<div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:10px">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    <span style="font-size:14px;color:#dc2626;font-weight:600">{{ session('erro') }}</span>
</div>
@endif

{{-- Header --}}
<div style="text-align:center;margin-bottom:28px">
    <div style="display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;border:1px solid {{ $sc['border'] }};background:{{ $sc['bg'] }};margin-bottom:16px">
        <span style="width:7px;height:7px;border-radius:50%;background:{{ $sc['color'] }};flex-shrink:0"></span>
        <span style="font-size:13px;font-weight:600;color:{{ $sc['color'] }}">{{ $sc['label'] }}</span>
    </div>
    <h1 style="font-family:var(--sa-font-heading);font-size:22px;font-weight:700;color:var(--sa-text1);margin-bottom:4px">Meu Agendamento</h1>
    <p style="font-size:14px;color:var(--sa-text3)">{{ $company->name }}</p>
</div>

{{-- Card de detalhes --}}
<div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05);margin-bottom:20px">
    <h2 style="font-size:13px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.06em;margin:0 0 18px">Detalhes do Agendamento</h2>

    <div style="display:flex;flex-direction:column;gap:14px">
        <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:13px;color:var(--sa-text3);display:flex;align-items:center;gap:6px">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/></svg>
                Serviço
            </span>
            <span style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $ag->servico?->nome ?? '—' }}</span>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:13px;color:var(--sa-text3);display:flex;align-items:center;gap:6px">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Profissional
            </span>
            <span style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $ag->profissional?->name ?? '—' }}</span>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:13px;color:var(--sa-text3);display:flex;align-items:center;gap:6px">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Data e Hora
            </span>
            <span style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $ag->data_hora->format('d/m/Y \à\s H:i') }}</span>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:13px;color:var(--sa-text3);display:flex;align-items:center;gap:6px">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Duração
            </span>
            <span style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $ag->duracao }} min</span>
        </div>

        @if($ag->valor)
        <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--sa-border);padding-top:14px">
            <span style="font-size:13px;color:var(--sa-text3)">Valor</span>
            <span style="font-size:18px;font-weight:700;color:var(--sa-secondary)">R$ {{ number_format((float) $ag->valor, 2, ',', '.') }}</span>
        </div>
        @endif
    </div>
</div>

{{-- Cliente --}}
@if($ag->cliente)
<div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:20px;margin-bottom:20px;display:flex;align-items:center;gap:14px">
    <div style="width:42px;height:42px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;flex-shrink:0">
        {{ strtoupper(mb_substr($ag->cliente->name, 0, 1)) }}
    </div>
    <div>
        <div style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $ag->cliente->name }}</div>
        <div style="font-size:12px;color:var(--sa-text3)">{{ $ag->cliente->phone }}</div>
    </div>
</div>
@endif

{{-- Ações --}}
@if($cancelavel && !$isCanceled)
<form method="POST" action="{{ route('agendamento.cancelar', $token) }}" id="form-cancelar">
    @csrf
</form>
<div style="text-align:center;margin-bottom:8px">
    <button type="button"
            onclick="confirmarCancelamento()"
            style="display:inline-flex;align-items:center;gap:7px;padding:11px 22px;border-radius:8px;border:1.5px solid #ef4444;background:transparent;color:#ef4444;font-size:14px;font-weight:600;cursor:pointer;font-family:var(--sa-font-body);transition:all 160ms"
            onmouseover="this.style.background='rgba(239,68,68,.08)'"
            onmouseout="this.style.background='transparent'">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        Cancelar meu agendamento
    </button>
</div>
@elseif($isCanceled)
<div style="background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.15);border-radius:10px;padding:14px 18px;text-align:center;margin-bottom:12px">
    <p style="font-size:14px;color:#dc2626;margin:0">Este agendamento foi cancelado.</p>
</div>
@endif

<div style="text-align:center;margin-top:16px">
    <a href="{{ route('agendar.show', $company->slug) }}"
       style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;text-decoration:none;transition:border-color 180ms,color 180ms"
       onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
       onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
        Novo agendamento
    </a>
</div>

<script>
function confirmarCancelamento() {
    Swal.fire({
        title: 'Cancelar agendamento?',
        text: 'Esta ação não pode ser desfeita.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, cancelar',
        cancelButtonText: 'Não',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: 'transparent',
        customClass: { cancelButton: 'swal-cancel-muted' },
    }).then(r => { if (r.isConfirmed) document.getElementById('form-cancelar').submit(); });
}
</script>
@endsection
