@extends('layouts.app')
@section('title', 'Meu Painel')
@section('page-title', 'Meu Painel')

@section('content')
@php
    $statusBadge = [
        'confirmado' => ['bg' => 'rgba(16,185,129,.12)', 'color' => '#059669', 'label' => 'Confirmado'],
        'pendente' => ['bg' => 'rgba(245,158,11,.12)', 'color' => '#d97706', 'label' => 'Pendente'],
        'em_atendimento' => ['bg' => 'rgba(99,102,241,.12)', 'color' => '#6366f1', 'label' => 'Em atendimento'],
        'finalizado' => ['bg' => 'rgba(107,114,128,.12)', 'color' => '#6b7280', 'label' => 'Finalizado'],
        'cancelado' => ['bg' => 'rgba(239,68,68,.1)', 'color' => '#dc2626', 'label' => 'Cancelado'],
    ];
@endphp

{{-- AppHeader --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div>
        <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">
            Olá, {{ explode(' ', auth()->user()->name)[0] }} <span style="color:var(--sa-secondary)">✦</span>
        </h1>
        <p style="font-size:14px;color:var(--sa-text3);margin:0">{{ now()->translatedFormat('l, d \d\e F') }} — sua agenda e seus números</p>
    </div>
</div>

@if($stats === null)
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:32px;box-shadow:0 1px 3px rgba(0,0,0,.05);text-align:center;max-width:520px">
        <p style="font-size:14px;color:var(--sa-text2);margin:0 0 6px;font-weight:600">Seu usuário ainda não está vinculado a um profissional.</p>
        <p style="font-size:13px;color:var(--sa-text3);margin:0">Peça ao administrador da empresa para vincular seu usuário em Permissões → Usuários &amp; Funções.</p>
    </div>
@else

{{-- Stat cards --}}
<div class="sa-grid-4" style="margin-bottom:20px">
    <div style="background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:148px;display:flex;flex-direction:column">
        <div style="font-size:11px;font-weight:700;color:var(--sa-primary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.75">HOJE</div>
        <div style="font-family:'Poppins',sans-serif;font-size:32px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">{{ $stats['agendaHoje']->count() }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:8px">agendamentos na sua agenda</div>
        <div style="position:absolute;bottom:-32px;right:-26px;opacity:.08;pointer-events:none">
            <svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
    </div>
    <div style="background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:148px;display:flex;flex-direction:column">
        <div style="font-size:11px;font-weight:700;color:var(--sa-primary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.75">ATENDIMENTOS (MÊS)</div>
        <div style="font-family:'Poppins',sans-serif;font-size:32px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">{{ $stats['atendimentosMes'] }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:8px">{{ $stats['clientesAtendidosMes'] }} clientes diferentes</div>
        <div style="position:absolute;bottom:-32px;right:-26px;opacity:.08;pointer-events:none">
            <svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
    </div>
    @if($stats['podeVerComissao'])
    <div style="background:color-mix(in srgb,var(--sa-secondary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-secondary) 14%,transparent);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:148px;display:flex;flex-direction:column">
        <div style="font-size:11px;font-weight:700;color:var(--sa-secondary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.9">COMISSÃO (MÊS)</div>
        <div style="font-family:'Poppins',sans-serif;font-size:32px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">R$ {{ number_format($stats['comissaoMes'], 2, ',', '.') }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:8px">{{ number_format($stats['comissaoPct'], 1, ',', '.') }}% sobre R$ {{ number_format($stats['receitaMes'], 2, ',', '.') }}</div>
        <div style="position:absolute;bottom:-32px;right:-26px;opacity:.08;pointer-events:none">
            <svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
    </div>
    @endif
    <div style="background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:148px;display:flex;flex-direction:column">
        <div style="font-size:11px;font-weight:700;color:var(--sa-primary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.75">NOTA MÉDIA (MÊS)</div>
        <div style="font-family:'Poppins',sans-serif;font-size:32px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">
            {{ $stats['notaMedia'] !== null ? number_format($stats['notaMedia'], 1, ',', '.') : '—' }}
        </div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:8px">{{ $stats['notaMedia'] !== null ? 'avaliação dos clientes' : 'sem avaliações no mês' }}</div>
        <div style="position:absolute;bottom:-32px;right:-26px;opacity:.08;pointer-events:none">
            <svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        </div>
    </div>
</div>

<div class="sa-grid-2-360">
    {{-- Agenda de hoje --}}
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <div style="padding:18px 20px;border-bottom:1px solid var(--sa-border);display:flex;justify-content:space-between;align-items:center">
            <div style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1)">Minha agenda de hoje</div>
            <span style="font-size:12px;color:var(--sa-text3)">{{ now()->format('d/m/Y') }}</span>
        </div>
        @forelse($stats['agendaHoje'] as $ag)
            <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--sa-border)">
                <div style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);min-width:46px">{{ $ag->data_hora->format('H:i') }}</div>
                <div style="width:4px;height:36px;border-radius:2px;background:{{ $ag->servico?->cor ?? 'var(--sa-border)' }};flex-shrink:0"></div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:14px;font-weight:600;color:var(--sa-text1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $ag->cliente?->name ?? 'Cliente avulso' }}</div>
                    <div style="font-size:12px;color:var(--sa-text3)">{{ $ag->servico?->nome ?? '—' }} · {{ $ag->duracao }}min</div>
                </div>
                @php $badge = $statusBadge[$ag->status] ?? $statusBadge['pendente']; @endphp
                <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $badge['bg'] }};color:{{ $badge['color'] }};flex-shrink:0">
                    <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                    {{ $badge['label'] }}
                </span>
            </div>
        @empty
            <div style="padding:32px 20px;text-align:center">
                <p style="font-size:14px;color:var(--sa-text3);margin:0">Nenhum agendamento para hoje. Aproveite o dia! ✦</p>
            </div>
        @endforelse
    </div>

    {{-- Próximos agendamentos --}}
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05);align-self:start">
        <div style="padding:18px 20px;border-bottom:1px solid var(--sa-border)">
            <div style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1)">Próximos agendamentos</div>
        </div>
        @forelse($stats['proximos'] as $ag)
            <div style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid var(--sa-border)">
                <div style="text-align:center;min-width:44px">
                    <div style="font-size:11px;color:var(--sa-text3);text-transform:uppercase">{{ $ag->data_hora->translatedFormat('D') }}</div>
                    <div style="font-family:'Poppins',sans-serif;font-size:14px;font-weight:700;color:var(--sa-text1)">{{ $ag->data_hora->format('d/m') }}</div>
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:13px;font-weight:600;color:var(--sa-text1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $ag->cliente?->name ?? 'Cliente avulso' }}</div>
                    <div style="font-size:11px;color:var(--sa-text3)">{{ $ag->data_hora->format('H:i') }} · {{ $ag->servico?->nome ?? '—' }}</div>
                </div>
            </div>
        @empty
            <div style="padding:24px 20px;text-align:center">
                <p style="font-size:13px;color:var(--sa-text3);margin:0">Nenhum agendamento futuro.</p>
            </div>
        @endforelse
    </div>
</div>
@endif
@endsection
