@extends('layouts.public')
@section('title', 'Minhas Reservas — ' . $company->name)

@section('header-right')
<div style="margin-left:auto;font-size:13px;color:rgba(255,255,255,.6)">{{ $company->name }}</div>
@endsection

@section('content')

@php
    $statusConfig = [
        'pendente'       => ['label' => 'Aguardando',    'color' => '#d97706', 'bg' => 'rgba(245,158,11,.1)',  'border' => 'rgba(245,158,11,.2)'],
        'confirmado'     => ['label' => 'Confirmado',    'color' => '#059669', 'bg' => 'rgba(16,185,129,.1)', 'border' => 'rgba(16,185,129,.2)'],
        'em_atendimento' => ['label' => 'Em atendimento','color' => '#6366f1', 'bg' => 'rgba(99,102,241,.1)', 'border' => 'rgba(99,102,241,.2)'],
        'finalizado'     => ['label' => 'Finalizado',    'color' => '#6b7280', 'bg' => 'rgba(107,114,128,.1)','border' => 'rgba(107,114,128,.2)'],
        'cancelado'      => ['label' => 'Cancelado',     'color' => '#dc2626', 'bg' => 'rgba(239,68,68,.1)',  'border' => 'rgba(239,68,68,.2)'],
    ];
@endphp

{{-- Header --}}
<div style="text-align:center;margin-bottom:28px">
    <h1 style="font-family:var(--sa-font-heading);font-size:22px;font-weight:700;color:var(--sa-text1);margin-bottom:4px">Minhas Reservas</h1>
    <p style="font-size:14px;color:var(--sa-text3)">{{ $company->name }}</p>
</div>

{{-- Formulário de busca --}}
<div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;margin-bottom:24px">
    <form method="GET" action="{{ route('vitrine.minhas-reservas', $company->slug) }}"
          style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
        <div style="flex:1;min-width:220px">
            <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
                Seu WhatsApp / Telefone
            </label>
            <input type="tel" name="phone" value="{{ $phone }}"
                   placeholder="(11) 99999-9999"
                   style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:var(--sa-font-body);color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms"
                   onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
        </div>
        <button type="submit"
                style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:var(--sa-font-body);background:var(--sa-primary);color:#fff;transition:filter 200ms;white-space:nowrap"
                onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            Buscar
        </button>
    </form>
</div>

{{-- Resultados --}}
@if($phone !== '')
    @if(!$cliente)
    <div style="text-align:center;padding:32px 0">
        <div style="width:64px;height:64px;border-radius:50%;background:rgba(107,114,128,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <p style="font-size:15px;font-weight:600;color:var(--sa-text1);margin-bottom:6px">Nenhum cadastro encontrado</p>
        <p style="font-size:13px;color:var(--sa-text3)">Não encontramos agendamentos com o telefone <strong>{{ $phone }}</strong>.</p>
        <a href="{{ route('agendar.show', $company->slug) }}"
           style="display:inline-flex;align-items:center;gap:7px;margin-top:20px;padding:10px 20px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:var(--sa-font-body);background:var(--sa-primary);color:#fff;text-decoration:none;transition:filter 200ms"
           onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
            Fazer um agendamento
        </a>
    </div>
    @else
    {{-- Cliente encontrado --}}
    <div style="background:color-mix(in srgb,var(--sa-primary) 5%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 12%,transparent);border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:center;gap:14px">
        <div style="width:42px;height:42px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;flex-shrink:0">
            {{ strtoupper(mb_substr($cliente->name, 0, 1)) }}
        </div>
        <div>
            <div style="font-size:14px;font-weight:700;color:var(--sa-text1)">{{ $cliente->name }}</div>
            <div style="font-size:12px;color:var(--sa-text3)">{{ $agendamentos->count() }} agendamento{{ $agendamentos->count() === 1 ? '' : 's' }} encontrado{{ $agendamentos->count() === 1 ? '' : 's' }}</div>
        </div>
    </div>

    @if($agendamentos->isEmpty())
    <p style="text-align:center;font-size:14px;color:var(--sa-text3);padding:20px 0">Nenhum agendamento registrado.</p>
    @else
    <div style="display:flex;flex-direction:column;gap:12px">
        @foreach($agendamentos as $ag)
        @php
            $sc = $statusConfig[$ag->status] ?? $statusConfig['pendente'];
            $isFuturo = $ag->data_hora->isFuture();
            $cancelavel = in_array($ag->status, ['pendente', 'confirmado']) && $isFuturo && $ag->cancel_token;
        @endphp
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:18px 20px;display:flex;flex-direction:column;gap:10px">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap">
                <div>
                    <div style="font-size:15px;font-weight:700;color:var(--sa-text1);margin-bottom:2px">
                        {{ $ag->servico?->nome ?? '—' }}
                    </div>
                    <div style="font-size:13px;color:var(--sa-text3)">
                        {{ $ag->profissional?->name ?? 'Qualquer profissional' }}
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;border:1px solid {{ $sc['border'] }};background:{{ $sc['bg'] }};flex-shrink:0">
                    <span style="width:6px;height:6px;border-radius:50%;background:{{ $sc['color'] }};flex-shrink:0"></span>
                    <span style="font-size:12px;font-weight:600;color:{{ $sc['color'] }}">{{ $sc['label'] }}</span>
                </div>
            </div>

            <div style="display:flex;gap:20px;flex-wrap:wrap">
                <div style="display:flex;align-items:center;gap:6px">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span style="font-size:13px;color:var(--sa-text2)">{{ $ag->data_hora->format('d/m/Y') }}</span>
                </div>
                <div style="display:flex;align-items:center;gap:6px">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span style="font-size:13px;color:var(--sa-text2)">{{ $ag->data_hora->format('H:i') }}</span>
                </div>
                @if($ag->valor)
                <div style="display:flex;align-items:center;gap:6px">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                    <span style="font-size:13px;font-weight:600;color:var(--sa-secondary)">R$ {{ number_format((float) $ag->valor, 2, ',', '.') }}</span>
                </div>
                @endif
            </div>

            <div style="display:flex;gap:8px;flex-wrap:wrap">
                @if($ag->cancel_token)
                <a href="{{ route('agendamento.meu', $ag->cancel_token) }}"
                   style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:7px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:12px;font-weight:600;text-decoration:none;transition:border-color 150ms,color 150ms"
                   onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                   onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    Ver detalhes
                </a>
                @endif
                @if($cancelavel)
                <a href="{{ route('agendamento.meu', $ag->cancel_token) }}"
                   style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:7px;border:1.5px solid rgba(239,68,68,.4);background:transparent;color:#dc2626;font-size:12px;font-weight:600;text-decoration:none;transition:border-color 150ms,background 150ms"
                   onmouseover="this.style.background='rgba(239,68,68,.06)'" onmouseout="this.style.background='transparent'">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    Cancelar
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif
    @endif
@endif

<div style="text-align:center;margin-top:28px">
    <a href="{{ route('agendar.show', $company->slug) }}"
       style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;text-decoration:none;transition:border-color 180ms,color 180ms"
       onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
       onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
        Novo agendamento
    </a>
</div>
@endsection
