@extends('layouts.portal')
@section('title', 'Minha área')

@section('content')
@php
    $statusBadge = [
        'confirmado' => ['bg' => 'rgba(16,185,129,.12)', 'color' => '#059669', 'label' => 'Confirmado'],
        'pendente' => ['bg' => 'rgba(245,158,11,.12)', 'color' => '#d97706', 'label' => 'Aguardando'],
        'finalizado' => ['bg' => 'rgba(107,114,128,.12)', 'color' => '#6b7280', 'label' => 'Finalizado'],
    ];
@endphp

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px">
    <div>
        <h1 style="font-family:'Poppins',sans-serif;font-size:20px;font-weight:700;margin:0">Olá, {{ explode(' ', $cliente->name)[0] }} ✦</h1>
        <p style="font-size:13px;color:var(--sa-text3);margin:2px 0 0">Seus agendamentos e histórico</p>
    </div>
    <a href="{{ route('agendar.show', $company->slug) }}"
       style="display:inline-flex;align-items:center;gap:7px;padding:11px 18px;border-radius:8px;background:var(--sa-primary);color:#fff;font-size:14px;font-weight:600;text-decoration:none">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Agendar
    </a>
</div>

{{-- Resumo --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">
    <div style="background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:14px;padding:18px">
        <div style="font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Atendimentos</div>
        <div style="font-family:'Poppins',sans-serif;font-size:26px;font-weight:800;line-height:1">{{ $totalAtendimentos }}</div>
    </div>
    <div style="background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:14px;padding:18px">
        <div style="font-size:11px;font-weight:700;color:var(--sa-secondary);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Total gasto</div>
        <div style="font-family:'Poppins',sans-serif;font-size:26px;font-weight:800;line-height:1">R$ {{ number_format($totalGasto, 2, ',', '.') }}</div>
    </div>
</div>

{{-- Próximos --}}
<h2 style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;margin:0 0 12px">Próximos agendamentos</h2>
@forelse($proximos as $item)
    @php $ag = $item['model']; $badge = $statusBadge[$ag->status] ?? $statusBadge['pendente']; @endphp
    <div style="background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:14px;padding:16px;margin-bottom:12px">
        <div style="display:flex;align-items:flex-start;gap:12px">
            <div style="width:4px;align-self:stretch;border-radius:2px;background:{{ $ag->servico?->cor ?? 'var(--sa-border)' }}"></div>
            <div style="flex:1;min-width:0">
                <div style="font-size:15px;font-weight:600">{{ $ag->servico?->nome ?? 'Serviço' }}</div>
                <div style="font-size:13px;color:var(--sa-text3);margin-top:3px">
                    {{ $ag->data_hora->translatedFormat('l, d/m \à\s H:i') }}
                    @if($ag->profissional) · {{ $ag->profissional->name }} @endif
                </div>
            </div>
            <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $badge['bg'] }};color:{{ $badge['color'] }};flex-shrink:0">
                <span style="width:5px;height:5px;border-radius:50%;background:currentColor"></span>{{ $badge['label'] }}
            </span>
        </div>
        @if($item['pode_cancelar'])
        <form method="POST" action="{{ route('portal.cancelar', ['slug' => $company->slug, 'agendamento' => $ag->id]) }}" style="margin-top:12px;text-align:right" onsubmit="return confirmarCancel(event)">
            @csrf
            <button type="submit" style="font-size:13px;color:#dc2626;background:none;border:none;cursor:pointer;font-weight:600;padding:4px">Cancelar</button>
        </form>
        @endif
    </div>
@empty
    <div style="background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:14px;padding:28px;text-align:center;margin-bottom:20px">
        <p style="font-size:14px;color:var(--sa-text3);margin:0 0 12px">Você não tem agendamentos futuros.</p>
        <a href="{{ route('agendar.show', $company->slug) }}" style="font-size:14px;color:var(--sa-secondary);font-weight:600;text-decoration:none">Agendar agora →</a>
    </div>
@endforelse

@if($politica)
<p style="font-size:12px;color:var(--sa-text3);text-align:center;margin:4px 0 20px;line-height:1.6">{{ $politica }}</p>
@endif

{{-- Histórico --}}
<h2 style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;margin:24px 0 12px">Histórico</h2>
@forelse($historico as $ag)
    <div style="background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:12px;padding:14px 16px;margin-bottom:10px;display:flex;align-items:center;gap:12px">
        <div style="flex:1;min-width:0">
            <div style="font-size:14px;font-weight:600">{{ $ag->servico?->nome ?? 'Serviço' }}</div>
            <div style="font-size:12px;color:var(--sa-text3)">{{ $ag->data_hora->format('d/m/Y') }} @if($ag->profissional)· {{ $ag->profissional->name }}@endif</div>
        </div>
        <div style="text-align:right;flex-shrink:0">
            <div style="font-size:14px;font-weight:700">R$ {{ number_format((float) $ag->valor, 2, ',', '.') }}</div>
            @if($ag->avaliacao)
            <div style="font-size:12px;color:var(--sa-secondary)">{{ str_repeat('★', $ag->avaliacao->nota) }}</div>
            @endif
        </div>
    </div>
@empty
    <p style="font-size:13px;color:var(--sa-text3);text-align:center;padding:16px 0">Nenhum atendimento finalizado ainda.</p>
@endforelse

<div style="text-align:center;margin-top:24px">
    <a href="{{ route('portal.dados', $company->slug) }}" style="font-size:13px;color:var(--sa-text3);text-decoration:none">Meus dados e privacidade →</a>
</div>

<script>
function confirmarCancel(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Cancelar agendamento?',
        text: 'Esta ação não pode ser desfeita.',
        icon: 'warning', showCancelButton: true,
        confirmButtonText: 'Sim, cancelar', cancelButtonText: 'Voltar',
        confirmButtonColor: '#ef4444',
    }).then(r => { if (r.isConfirmed) e.target.submit(); });
    return false;
}
</script>
@endsection
