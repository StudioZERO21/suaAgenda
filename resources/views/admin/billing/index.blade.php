@extends('layouts.app')
@section('title', 'Billing')
@section('page-title', 'Billing')

@section('content')
{{-- Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div>
        <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">Billing</h1>
        <p style="font-size:14px;color:var(--sa-text3);margin:0">Assinaturas e cobranças das empresas</p>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
        <a href="{{ route('admin.billing.gateway') }}"
           style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;text-decoration:none;transition:border-color 180ms,color 180ms"
           onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
           onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51a1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
            Config. Gateway
        </a>
    </div>
</div>

{{-- Stat cards --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:28px">
    {{-- MRR --}}
    <div style="background:color-mix(in srgb,var(--sa-secondary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-secondary) 14%,transparent);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:140px;display:flex;flex-direction:column">
        <div style="font-size:11px;font-weight:700;color:var(--sa-secondary);letter-spacing:1px;text-transform:uppercase;margin-bottom:10px;opacity:.85">MRR</div>
        <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">
            R$ {{ number_format($stats['mrr'], 2, ',', '.') }}
        </div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:6px">receita mensal recorrente</div>
        <div style="position:absolute;bottom:-24px;right:-20px;opacity:.08;pointer-events:none">
            <svg width="110" height="110" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="1.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        </div>
    </div>

    {{-- Receita do mês --}}
    <div style="background:color-mix(in srgb,var(--sa-secondary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-secondary) 14%,transparent);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:140px;display:flex;flex-direction:column">
        <div style="font-size:11px;font-weight:700;color:var(--sa-secondary);letter-spacing:1px;text-transform:uppercase;margin-bottom:10px;opacity:.85">Receita {{ now()->translatedFormat('M') }}</div>
        <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">
            R$ {{ number_format($stats['receita_mes'], 2, ',', '.') }}
        </div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:6px">faturas pagas este mês</div>
        <div style="position:absolute;bottom:-24px;right:-20px;opacity:.08;pointer-events:none">
            <svg width="110" height="110" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="1.5"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
    </div>

    {{-- Ativas --}}
    <div style="background:color-mix(in srgb,var(--sa-primary) 6%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 12%,transparent);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:140px;display:flex;flex-direction:column">
        <div style="font-size:11px;font-weight:700;color:var(--sa-primary);letter-spacing:1px;text-transform:uppercase;margin-bottom:10px;opacity:.65">Ativas</div>
        <div style="font-family:'Poppins',sans-serif;font-size:36px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">{{ $stats['active'] }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:6px">trial + ativas</div>
        <div style="position:absolute;bottom:-24px;right:-20px;opacity:.07;pointer-events:none">
            <svg width="110" height="110" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
    </div>

    {{-- Em carência --}}
    <div style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:140px;display:flex;flex-direction:column">
        <div style="font-size:11px;font-weight:700;color:#d97706;letter-spacing:1px;text-transform:uppercase;margin-bottom:10px;opacity:.85">Carência</div>
        <div style="font-family:'Poppins',sans-serif;font-size:36px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">{{ $stats['grace'] }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:6px">no período de graça</div>
        <div style="position:absolute;bottom:-24px;right:-20px;opacity:.08;pointer-events:none">
            <svg width="110" height="110" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="1.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
    </div>

    {{-- Suspensas --}}
    <div style="background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.18);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:140px;display:flex;flex-direction:column">
        <div style="font-size:11px;font-weight:700;color:#dc2626;letter-spacing:1px;text-transform:uppercase;margin-bottom:10px;opacity:.85">Suspensas</div>
        <div style="font-family:'Poppins',sans-serif;font-size:36px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">{{ $stats['suspended'] }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:6px">{{ $stats['overdue_invoices'] }} fat. vencidas</div>
        <div style="position:absolute;bottom:-24px;right:-20px;opacity:.08;pointer-events:none">
            <svg width="110" height="110" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
        </div>
    </div>

    {{-- Canceladas --}}
    <div style="background:rgba(107,114,128,.07);border:1px solid rgba(107,114,128,.18);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:140px;display:flex;flex-direction:column">
        <div style="font-size:11px;font-weight:700;color:#6b7280;letter-spacing:1px;text-transform:uppercase;margin-bottom:10px;opacity:.85">Canceladas</div>
        <div style="font-family:'Poppins',sans-serif;font-size:36px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">{{ $stats['cancelled'] }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:6px">assinaturas canceladas</div>
        <div style="position:absolute;bottom:-24px;right:-20px;opacity:.07;pointer-events:none">
            <svg width="110" height="110" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="1.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
        </div>
    </div>
</div>

{{-- Alerta gateway não configurado --}}
@if(!$billingConfig->active)
<div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:12px">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" style="flex-shrink:0"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    <span style="font-size:14px;color:#92400e;font-weight:500">Gateway de pagamento não configurado. <a href="{{ route('admin.billing.gateway') }}" style="color:#d97706;font-weight:600">Configurar agora</a></span>
</div>
@endif

{{-- Filtros + tabela --}}
<div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)">
    {{-- filtro header --}}
    <div style="padding:16px 20px;border-bottom:1px solid var(--sa-border);display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <form method="GET" action="{{ route('admin.billing.index') }}" style="display:flex;align-items:center;gap:8px;flex:1;flex-wrap:wrap">
            <input type="text" name="q" value="{{ $busca }}" placeholder="Buscar empresa..."
                   style="flex:1;min-width:180px;padding:9px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms"
                   onfocus="this.style.borderColor='var(--sa-primary)'"
                   onblur="this.style.borderColor='var(--sa-border)'">
            <select name="status"
                    style="padding:9px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;color:var(--sa-text2);background:var(--sa-surface);outline:none;cursor:pointer"
                    onchange="this.form.submit()">
                <option value="" @selected($status==='')>Todos os status</option>
                <option value="trial" @selected($status==='trial')>Trial</option>
                <option value="active" @selected($status==='active')>Ativa</option>
                <option value="grace" @selected($status==='grace')>Carência</option>
                <option value="suspended" @selected($status==='suspended')>Suspensa</option>
                <option value="cancelled" @selected($status==='cancelled')>Cancelada</option>
                <option value="past_due" @selected($status==='past_due')>Atrasada</option>
            </select>
            <button type="submit"
                    style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:8px;border:none;cursor:pointer;font-size:13px;font-weight:600;font-family:'Inter',sans-serif;background:var(--sa-primary);color:#fff;transition:filter 200ms"
                    onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">Filtrar</button>
            @if($busca || $status)
            <a href="{{ route('admin.billing.index') }}"
               style="display:inline-flex;align-items:center;padding:9px 14px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text3);font-size:13px;text-decoration:none"
               onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
               onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">Limpar</a>
            @endif
        </form>
        <span style="font-size:13px;color:var(--sa-text3)">{{ $subscriptions->total() }} registros</span>
    </div>

    {{-- tabela --}}
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;min-width:820px">
            <thead>
                <tr style="background:var(--sa-surface2);border-bottom:1px solid var(--sa-border)">
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">Empresa</th>
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">Plano</th>
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">Status</th>
                    <th style="padding:11px 16px;text-align:right;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">Valor/mês</th>
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">Aniversário</th>
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">Período atual</th>
                    <th style="padding:11px 16px;text-align:right;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscriptions as $sub)
                @php
                    $statusColor = match($sub->status) {
                        'trial'     => ['bg'=>'rgba(99,102,241,.12)','color'=>'#4f46e5'],
                        'active'    => ['bg'=>'rgba(16,185,129,.12)','color'=>'#059669'],
                        'grace'     => ['bg'=>'rgba(245,158,11,.12)','color'=>'#d97706'],
                        'suspended' => ['bg'=>'rgba(239,68,68,.1)','color'=>'#dc2626'],
                        'cancelled' => ['bg'=>'rgba(107,114,128,.12)','color'=>'#6b7280'],
                        'past_due'  => ['bg'=>'rgba(239,68,68,.1)','color'=>'#dc2626'],
                        default     => ['bg'=>'rgba(107,114,128,.12)','color'=>'#6b7280'],
                    };
                    $statusLabel = match($sub->status) {
                        'trial'     => 'Trial',
                        'active'    => 'Ativa',
                        'grace'     => 'Carência',
                        'suspended' => 'Suspensa',
                        'cancelled' => 'Cancelada',
                        'past_due'  => 'Atrasada',
                        default     => $sub->status,
                    };
                @endphp
                <tr style="border-bottom:1px solid var(--sa-border);transition:background 120ms"
                    onmouseover="this.style.background='var(--sa-surface2)'"
                    onmouseout="this.style.background='transparent'">
                    <td style="padding:14px 16px">
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:32px;height:32px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">
                                {{ strtoupper(substr($sub->company->name ?? '?', 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $sub->company->name ?? '—' }}</div>
                                <div style="font-size:12px;color:var(--sa-text3)">{{ $sub->company->email ?? '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:14px 16px;font-size:13px;color:var(--sa-text2)">{{ $sub->plan_slug ?? '—' }}</td>
                    <td style="padding:14px 16px">
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $statusColor['bg'] }};color:{{ $statusColor['color'] }}">
                            <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                            {{ $statusLabel }}
                        </span>
                    </td>
                    <td style="padding:14px 16px;text-align:right;font-size:14px;font-weight:600;color:var(--sa-secondary)">
                        R$ {{ number_format($sub->monthly_amount ?? 0, 2, ',', '.') }}
                    </td>
                    <td style="padding:14px 16px;font-size:13px;color:var(--sa-text2)">
                        dia {{ $sub->anniversary_day ?? '—' }}
                    </td>
                    <td style="padding:14px 16px;font-size:12px;color:var(--sa-text3)">
                        @if($sub->current_period_start)
                            {{ \Carbon\Carbon::parse($sub->current_period_start)->format('d/m') }} –
                            {{ \Carbon\Carbon::parse($sub->current_period_end)->format('d/m/Y') }}
                        @elseif($sub->status === 'trial' && $sub->trial_ends_at)
                            trial até {{ \Carbon\Carbon::parse($sub->trial_ends_at)->format('d/m/Y') }}
                        @else
                            —
                        @endif
                    </td>
                    <td style="padding:14px 16px;text-align:right">
                        <a href="{{ route('admin.billing.show', $sub) }}"
                           style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;color:var(--sa-text3);text-decoration:none;transition:all 150ms"
                           onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'"
                           onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'"
                           title="Ver detalhes">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="padding:40px 16px;text-align:center;color:var(--sa-text3);font-size:14px">Nenhuma assinatura encontrada.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($subscriptions->hasPages())
    <div style="padding:14px 20px;border-top:1px solid var(--sa-border)">
        {{ $subscriptions->links() }}
    </div>
    @endif
</div>
@endsection
