@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@if(!$stats)
{{-- Super admin sem empresa --}}
<div style="max-width:640px">
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:32px;text-align:center">
        <div style="width:56px;height:56px;border-radius:14px;background:rgba(212,165,116,.12);display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        </div>
        <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 8px">Bem-vindo, {{ auth()->user()->name }}!</h1>
        <p style="font-size:14px;color:var(--sa-text3);margin:0">Você está autenticado como <strong>super_admin</strong>. Selecione uma empresa para visualizar o painel.</p>
    </div>
</div>
@else

@php
    $hoje = \Carbon\Carbon::today();
@endphp

{{-- Título do dia --}}
<div style="margin-bottom:24px">
    <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">
        Olá, {{ explode(' ', auth()->user()->name)[0] }}
    </h1>
    <p style="font-size:14px;color:var(--sa-text3);margin:0">{{ $hoje->translatedFormat('l, d \d\e F \d\e Y') }}</p>
</div>

{{-- Linha 1: Stats do dia --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px">
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:20px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--sa-text3);margin-bottom:8px">Agenda Hoje</div>
        <div style="font-size:30px;font-weight:700;color:var(--sa-text1);font-family:'Poppins',sans-serif;line-height:1">{{ $stats['agendamentosHoje'] }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:6px">pendentes e confirmados</div>
    </div>

    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:20px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--sa-text3);margin-bottom:8px">Finalizados Hoje</div>
        <div style="font-size:30px;font-weight:700;color:#065f46;font-family:'Poppins',sans-serif;line-height:1">{{ $stats['finalizadosHoje'] }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:6px">atendimentos concluídos</div>
    </div>

    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:20px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--sa-text3);margin-bottom:8px">Receita Hoje</div>
        <div style="font-size:24px;font-weight:700;color:var(--sa-secondary);font-family:'Poppins',sans-serif;line-height:1">R$ {{ number_format((float)$stats['receitaHoje'], 2, ',', '.') }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:6px">de serviços finalizados</div>
    </div>

    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:20px">
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--sa-text3);margin-bottom:8px">Receita do Mês</div>
        <div style="font-size:24px;font-weight:700;color:var(--sa-secondary);font-family:'Poppins',sans-serif;line-height:1">R$ {{ number_format((float)$stats['receitaMes'], 2, ',', '.') }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:6px">{{ $hoje->format('F Y') }}</div>
    </div>
</div>

{{-- Linha 2: Totais de cadastro --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px">
    <a href="{{ route('clientes.index') }}" style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:16px 20px;text-decoration:none;display:flex;align-items:center;gap:14px;transition:border-color 180ms" onmouseover="this.style.borderColor='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)'">
        <div style="width:40px;height:40px;border-radius:10px;background:rgba(26,26,26,.06);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text2)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        </div>
        <div>
            <div style="font-size:22px;font-weight:700;color:var(--sa-text1);font-family:'Poppins',sans-serif">{{ $stats['totalClientes'] }}</div>
            <div style="font-size:12px;color:var(--sa-text3);margin-top:1px">Clientes</div>
        </div>
    </a>

    <a href="{{ route('profissionais.index') }}" style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:16px 20px;text-decoration:none;display:flex;align-items:center;gap:14px;transition:border-color 180ms" onmouseover="this.style.borderColor='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)'">
        <div style="width:40px;height:40px;border-radius:10px;background:rgba(26,26,26,.06);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text2)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div>
            <div style="font-size:22px;font-weight:700;color:var(--sa-text1);font-family:'Poppins',sans-serif">{{ $stats['totalProfissionais'] }}</div>
            <div style="font-size:12px;color:var(--sa-text3);margin-top:1px">Profissionais ativos</div>
        </div>
    </a>

    <a href="{{ route('servicos.index') }}" style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:16px 20px;text-decoration:none;display:flex;align-items:center;gap:14px;transition:border-color 180ms" onmouseover="this.style.borderColor='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)'">
        <div style="width:40px;height:40px;border-radius:10px;background:rgba(26,26,26,.06);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text2)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/></svg>
        </div>
        <div>
            <div style="font-size:22px;font-weight:700;color:var(--sa-text1);font-family:'Poppins',sans-serif">{{ $stats['totalServicos'] }}</div>
            <div style="font-size:12px;color:var(--sa-text3);margin-top:1px">Serviços ativos</div>
        </div>
    </a>
</div>

{{-- Linha 3: Próximos agendamentos + Status do dia --}}
<div style="display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start">

    {{-- Próximos agendamentos --}}
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <div style="padding:16px 20px;border-bottom:1px solid var(--sa-border);display:flex;align-items:center;justify-content:space-between">
            <h2 style="font-size:14px;font-weight:700;color:var(--sa-text1);margin:0">Próximos Agendamentos</h2>
            <a href="{{ route('agendamentos.index') }}" style="font-size:12px;color:var(--sa-secondary);text-decoration:none;font-weight:600">Ver todos</a>
        </div>

        @forelse($stats['proximosAgendamentos'] as $ag)
        @php
            $badgeStyle = match($ag->status) {
                'confirmado' => 'background:rgba(5,150,105,.1);color:#065f46',
                default      => 'background:rgba(245,158,11,.1);color:#92400e',
            };
        @endphp
        <div style="padding:14px 20px;border-bottom:1px solid var(--sa-border);display:flex;align-items:center;gap:12px" onmouseover="this.style.background='var(--sa-surface2)'" onmouseout="this.style.background='transparent'">
            <div style="flex-shrink:0;text-align:center;min-width:44px">
                <div style="font-size:18px;font-weight:700;color:var(--sa-text1);font-family:'Poppins',sans-serif;line-height:1">{{ $ag->data_hora->format('H:i') }}</div>
                <div style="font-size:10px;color:var(--sa-text3);margin-top:2px">{{ $ag->data_hora->format('d/m') }}</div>
            </div>
            <div style="flex:1;min-width:0">
                <div style="font-size:14px;font-weight:600;color:var(--sa-text1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $ag->cliente?->name ?? '—' }}</div>
                <div style="font-size:12px;color:var(--sa-text3);margin-top:2px">{{ $ag->servico?->nome ?? '—' }} • {{ $ag->profissional?->name ?? '—' }}</div>
            </div>
            <span style="flex-shrink:0;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;{{ $badgeStyle }}">{{ ucfirst($ag->status) }}</span>
        </div>
        @empty
        <div style="padding:40px 20px;text-align:center;color:var(--sa-text3);font-size:14px">
            Nenhum agendamento futuro encontrado.
            @can('create', \App\Models\Agendamento::class)
            <br><a href="{{ route('agendamentos.create') }}" style="color:var(--sa-secondary);font-weight:600;text-decoration:none;margin-top:6px;display:inline-block">Criar agendamento</a>
            @endcan
        </div>
        @endforelse
    </div>

    {{-- Status do dia --}}
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:20px">
        <h2 style="font-size:14px;font-weight:700;color:var(--sa-text1);margin:0 0 16px">Status de Hoje</h2>

        @php
            $statusLabels = [
                'pendente'   => ['label' => 'Pendente',   'bg' => 'rgba(245,158,11,.1)',  'color' => '#92400e'],
                'confirmado' => ['label' => 'Confirmado', 'bg' => 'rgba(5,150,105,.1)',   'color' => '#065f46'],
                'finalizado' => ['label' => 'Finalizado', 'bg' => 'rgba(107,114,128,.1)','color' => '#374151'],
                'cancelado'  => ['label' => 'Cancelado',  'bg' => 'rgba(239,68,68,.1)',   'color' => '#991b1b'],
            ];
            $totalHoje = array_sum($stats['statusDistribuicao']);
        @endphp

        @if($totalHoje > 0)
        <div style="display:flex;flex-direction:column;gap:10px">
            @foreach($statusLabels as $status => $cfg)
            @php $count = $stats['statusDistribuicao'][$status] ?? 0; @endphp
            @if($count > 0)
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px">
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:{{ $cfg['color'] }}"></span>
                    <span style="font-size:13px;color:var(--sa-text2)">{{ $cfg['label'] }}</span>
                </div>
                <span style="font-size:13px;font-weight:700;padding:2px 10px;border-radius:20px;background:{{ $cfg['bg'] }};color:{{ $cfg['color'] }}">{{ $count }}</span>
            </div>
            @endif
            @endforeach
        </div>

        {{-- Barra de progresso simples --}}
        <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--sa-border)">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                <span style="font-size:12px;color:var(--sa-text3)">Total hoje</span>
                <span style="font-size:13px;font-weight:700;color:var(--sa-text1)">{{ $totalHoje }}</span>
            </div>
            <div style="height:6px;background:var(--sa-surface2);border-radius:3px;overflow:hidden;border:1px solid var(--sa-border)">
                @php $pct = $totalHoje > 0 ? round(($stats['statusDistribuicao']['finalizado'] ?? 0) / $totalHoje * 100) : 0; @endphp
                <div style="height:100%;width:{{ $pct }}%;background:var(--sa-secondary);border-radius:3px;transition:width 600ms ease"></div>
            </div>
            <div style="font-size:11px;color:var(--sa-text3);margin-top:5px">{{ $pct }}% concluídos</div>
        </div>
        @else
        <p style="font-size:14px;color:var(--sa-text3);margin:0;text-align:center;padding:20px 0">Nenhum agendamento hoje.</p>
        @endif

        <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--sa-border)">
            <a href="{{ route('agendamentos.create') }}"
               style="display:block;text-align:center;padding:10px;border-radius:9px;background:var(--sa-primary);color:#fff;text-decoration:none;font-size:13px;font-weight:600;transition:filter 200ms"
               onmouseover="this.style.filter='brightness(1.15)'" onmouseout="this.style.filter='none'">
                + Novo Agendamento
            </a>
        </div>
    </div>
</div>

@endif
@endsection
