@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@if(!$stats)
{{-- Super admin sem empresa --}}
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:32px;text-align:center">
        <div style="width:56px;height:56px;border-radius:14px;background:rgba(212,165,116,.12);display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        </div>
        <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 8px">Bem-vindo, {{ auth()->user()->name }}!</h1>
        <p style="font-size:14px;color:var(--sa-text3);margin:0">Você está autenticado como <strong>super_admin</strong>. Selecione uma empresa para visualizar o painel.</p>
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

{{-- Linha 1: Stats do dia (TintCard) --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px">

    {{-- Agenda Hoje --}}
    <div style="background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:148px;display:flex;flex-direction:column">
        <div style="font-size:11px;font-weight:700;color:var(--sa-primary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.75">Agenda Hoje</div>
        <div style="font-family:'Poppins',sans-serif;font-size:34px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">{{ $stats['agendamentosHoje'] }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:8px">pendentes e confirmados</div>
        <div style="position:absolute;bottom:-32px;right:-26px;opacity:.08;pointer-events:none">
            <svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
    </div>

    {{-- Finalizados Hoje --}}
    <div style="background:color-mix(in srgb,#10b981 8%,transparent);border:1px solid color-mix(in srgb,#10b981 14%,transparent);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:148px;display:flex;flex-direction:column">
        <div style="font-size:11px;font-weight:700;color:#059669;letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.75">Finalizados Hoje</div>
        <div style="font-family:'Poppins',sans-serif;font-size:34px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">{{ $stats['finalizadosHoje'] }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:8px">atendimentos concluídos</div>
        <div style="position:absolute;bottom:-32px;right:-26px;opacity:.08;pointer-events:none">
            <svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
    </div>

    {{-- Receita Hoje --}}
    <div style="background:color-mix(in srgb,var(--sa-secondary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-secondary) 14%,transparent);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:148px;display:flex;flex-direction:column">
        <div style="font-size:11px;font-weight:700;color:var(--sa-secondary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.85">Receita Hoje</div>
        <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">R$ {{ number_format((float)$stats['receitaHoje'], 2, ',', '.') }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:8px">de serviços finalizados</div>
        <div style="position:absolute;bottom:-32px;right:-26px;opacity:.1;pointer-events:none">
            <svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
    </div>

    {{-- Receita do Mês --}}
    <div style="background:color-mix(in srgb,var(--sa-secondary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-secondary) 14%,transparent);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:148px;display:flex;flex-direction:column">
        <div style="font-size:11px;font-weight:700;color:var(--sa-secondary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.85">Receita do Mês</div>
        <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">R$ {{ number_format((float)$stats['receitaMes'], 2, ',', '.') }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:8px">{{ $hoje->translatedFormat('F Y') }}</div>
        <div style="position:absolute;bottom:-32px;right:-26px;opacity:.1;pointer-events:none">
            <svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
        </div>
    </div>

</div>

{{-- Linha 2: Timeline + Painel Direito --}}
<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">

    {{-- Timeline: Próximos Agendamentos --}}
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <div style="padding:20px 20px 16px;border-bottom:1px solid var(--sa-border);display:flex;align-items:center;justify-content:space-between">
            <div>
                <h2 style="font-family:'Poppins',sans-serif;font-size:16px;font-weight:600;color:var(--sa-text1);margin:0">Próximos Agendamentos</h2>
                <p style="font-size:13px;color:var(--sa-text3);margin:3px 0 0">Linha do tempo</p>
            </div>
            <a href="{{ route('agendamentos.index') }}"
               style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:13px;font-weight:600;text-decoration:none;transition:border-color 180ms,color 180ms"
               onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
               onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Agenda
            </a>
        </div>

        {{-- Timeline container --}}
        <div style="padding:20px 16px;position:relative">
            <div style="position:absolute;left:31px;top:24px;bottom:24px;width:2px;background:linear-gradient(to bottom,var(--sa-secondary),var(--sa-border) 80%);border-radius:1px;opacity:.5"></div>

            @forelse($stats['proximosAgendamentos'] as $ag)
            @php
                $dotColor = match($ag->status) {
                    'confirmado' => '#10b981',
                    'finalizado' => '#6b7280',
                    'cancelado'  => '#ef4444',
                    default      => '#f59e0b',
                };
            @endphp
            <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:10px;padding-left:6px">
                <div style="position:relative;flex-shrink:0;margin-top:10px;z-index:1">
                    <div style="width:20px;height:20px;border-radius:50%;background:{{ $dotColor }}18;border:2px solid {{ $dotColor }};display:flex;align-items:center;justify-content:center">
                        <div style="width:6px;height:6px;border-radius:50%;background:{{ $dotColor }}"></div>
                    </div>
                </div>
                <div style="flex:1;background:var(--sa-surface2);border-radius:10px;padding:10px 14px;border:1px solid var(--sa-border);border-left:3px solid {{ $dotColor }};transition:box-shadow 150ms"
                     onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,.08)'"
                     onmouseout="this.style.boxShadow='none'">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                        <div style="display:flex;align-items:center;gap:8px;flex:1;min-width:0">
                            <div style="width:28px;height:28px;border-radius:50%;background:{{ $dotColor }};color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0">{{ strtoupper(substr($ag->cliente?->name ?? '?', 0, 1)) }}</div>
                            <div style="min-width:0">
                                <div style="font-size:13px;font-weight:700;color:var(--sa-text1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $ag->cliente?->name ?? '—' }}</div>
                                <div style="font-size:11px;color:var(--sa-text3);margin-top:1px">{{ $ag->servico?->nome ?? '—' }} · {{ $ag->profissional?->name ?? '—' }}</div>
                            </div>
                        </div>
                        <div style="flex-shrink:0;text-align:right">
                            <div style="font-family:'Poppins',sans-serif;font-size:13px;font-weight:800;color:var(--sa-secondary)">{{ $ag->data_hora->format('H:i') }}</div>
                            <div style="font-size:10px;color:var(--sa-text3)">{{ $ag->data_hora->format('d/m') }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div style="padding:40px 0;text-align:center;color:var(--sa-text3);font-size:14px;padding-left:30px">
                Nenhum agendamento futuro.
                @can('create', \App\Models\Agendamento::class)
                <br><a href="{{ route('agendamentos.create') }}" style="color:var(--sa-secondary);font-weight:600;text-decoration:none;margin-top:6px;display:inline-block">Criar agendamento</a>
                @endcan
            </div>
            @endforelse

            @if(count($stats['proximosAgendamentos']) >= 8)
            <div style="padding-left:30px;margin-top:8px">
                <a href="{{ route('agendamentos.index') }}" style="font-size:13px;font-weight:600;color:var(--sa-secondary);text-decoration:none;display:inline-flex;align-items:center;gap:4px">
                    Ver agenda completa <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </a>
            </div>
            @endif
        </div>
    </div>

    {{-- Painel Direito --}}
    <div style="display:flex;flex-direction:column;gap:16px">

        {{-- Resumo de Hoje --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <h3 style="font-family:'Poppins',sans-serif;font-size:14px;font-weight:600;color:var(--sa-text1);margin:0 0 14px">Resumo de Hoje</h3>
            @php
                $totalHoje = array_sum($stats['statusDistribuicao']);
                $resumoHoje = [
                    ['label'=>'Agendamentos',    'value'=>$stats['agendamentosHoje'],                                                 'icon'=>'<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>'],
                    ['label'=>'Receita Prevista','value'=>'R$ '.number_format((float)$stats['receitaHoje'],2,',','.'),                'icon'=>'<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>'],
                    ['label'=>'Confirmados',     'value'=>$stats['statusDistribuicao']['confirmado'] ?? 0,                            'icon'=>'<polyline points="20 6 9 17 4 12"/>'],
                    ['label'=>'Pendentes',       'value'=>$stats['statusDistribuicao']['pendente'] ?? 0,                              'icon'=>'<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
                ];
            @endphp
            @foreach($resumoHoje as $i => $item)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:9px 0;{{ $i < count($resumoHoje)-1 ? 'border-bottom:1px solid var(--sa-border)' : '' }}">
                <div style="display:flex;align-items:center;gap:8px">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $item['icon'] !!}</svg>
                    <span style="font-size:13px;color:var(--sa-text2)">{{ $item['label'] }}</span>
                </div>
                <span style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:800;color:var(--sa-text1)">{{ $item['value'] }}</span>
            </div>
            @endforeach
        </div>

        {{-- Status do Dia --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <h3 style="font-family:'Poppins',sans-serif;font-size:14px;font-weight:600;color:var(--sa-text1);margin:0 0 14px">Status de Hoje</h3>
            @php
                $statusLabels = [
                    'confirmado'=>['label'=>'Confirmado','color'=>'#059669','bg'=>'rgba(16,185,129,.12)'],
                    'pendente'  =>['label'=>'Pendente',  'color'=>'#d97706','bg'=>'rgba(245,158,11,.12)'],
                    'finalizado'=>['label'=>'Finalizado','color'=>'#6b7280','bg'=>'rgba(107,114,128,.12)'],
                    'cancelado' =>['label'=>'Cancelado', 'color'=>'#dc2626','bg'=>'rgba(239,68,68,.1)'],
                ];
            @endphp
            @if($totalHoje > 0)
            <div style="display:flex;flex-direction:column;gap:8px">
                @foreach($statusLabels as $status => $cfg)
                @php $cnt = $stats['statusDistribuicao'][$status] ?? 0; @endphp
                @if($cnt > 0)
                <div style="display:flex;align-items:center;justify-content:space-between">
                    <div style="display:flex;align-items:center;gap:8px">
                        <span style="width:8px;height:8px;border-radius:50%;background:{{ $cfg['color'] }};flex-shrink:0;display:inline-block"></span>
                        <span style="font-size:13px;color:var(--sa-text2)">{{ $cfg['label'] }}</span>
                    </div>
                    <span style="font-size:13px;font-weight:700;padding:2px 10px;border-radius:20px;background:{{ $cfg['bg'] }};color:{{ $cfg['color'] }}">{{ $cnt }}</span>
                </div>
                @endif
                @endforeach
            </div>
            @php $pct = $totalHoje > 0 ? round(($stats['statusDistribuicao']['finalizado'] ?? 0) / $totalHoje * 100) : 0; @endphp
            <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--sa-border)">
                <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                    <span style="font-size:12px;color:var(--sa-text3)">Total hoje</span>
                    <span style="font-size:12px;font-weight:700;color:var(--sa-text1)">{{ $totalHoje }}</span>
                </div>
                <div style="height:5px;background:var(--sa-surface2);border-radius:3px;overflow:hidden">
                    <div style="height:100%;width:{{ $pct }}%;background:var(--sa-secondary);border-radius:3px;transition:width 600ms ease"></div>
                </div>
                <div style="font-size:11px;color:var(--sa-text3);margin-top:4px">{{ $pct }}% concluídos</div>
            </div>
            @else
            <p style="font-size:14px;color:var(--sa-text3);margin:0;text-align:center;padding:16px 0">Nenhum agendamento hoje.</p>
            @endif
        </div>

        {{-- Atalhos --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <h3 style="font-family:'Poppins',sans-serif;font-size:14px;font-weight:600;color:var(--sa-text1);margin:0 0 12px">Atalhos</h3>
            <div style="display:flex;flex-direction:column;gap:8px">
                @can('create', \App\Models\Agendamento::class)
                <a href="{{ route('agendamentos.create') }}"
                   style="display:flex;align-items:center;gap:8px;padding:9px 12px;border-radius:8px;background:var(--sa-primary);color:#fff;text-decoration:none;font-size:13px;font-weight:600;transition:filter 200ms"
                   onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Novo Agendamento
                </a>
                @endcan
                <a href="{{ route('clientes.index') }}"
                   style="display:flex;align-items:center;gap:8px;padding:9px 12px;border-radius:8px;border:1.5px solid var(--sa-border);color:var(--sa-text2);text-decoration:none;font-size:13px;font-weight:500;transition:border-color 180ms,color 180ms"
                   onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                   onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                    {{ $stats['totalClientes'] }} Clientes
                </a>
                <a href="{{ route('profissionais.index') }}"
                   style="display:flex;align-items:center;gap:8px;padding:9px 12px;border-radius:8px;border:1.5px solid var(--sa-border);color:var(--sa-text2);text-decoration:none;font-size:13px;font-weight:500;transition:border-color 180ms,color 180ms"
                   onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                   onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    {{ $stats['totalProfissionais'] }} Profissionais
                </a>
            </div>
        </div>
    </div>
</div>

@endif
@endsection
