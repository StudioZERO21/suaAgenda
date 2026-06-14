@extends('layouts.app')
@section('title', 'Assinatura — ' . ($subscription->company->name ?? ''))
@section('page-title', 'Assinatura')

@section('content')
@php
    $statusColor = match($subscription->status) {
        'trial'     => ['bg'=>'rgba(99,102,241,.12)','color'=>'#4f46e5'],
        'active'    => ['bg'=>'rgba(16,185,129,.12)','color'=>'#059669'],
        'grace'     => ['bg'=>'rgba(245,158,11,.12)','color'=>'#d97706'],
        'suspended' => ['bg'=>'rgba(239,68,68,.1)','color'=>'#dc2626'],
        'cancelled' => ['bg'=>'rgba(107,114,128,.12)','color'=>'#6b7280'],
        'past_due'  => ['bg'=>'rgba(239,68,68,.1)','color'=>'#dc2626'],
        default     => ['bg'=>'rgba(107,114,128,.12)','color'=>'#6b7280'],
    };
    $statusLabel = match($subscription->status) {
        'trial'     => 'Trial',
        'active'    => 'Ativa',
        'grace'     => 'Carência',
        'suspended' => 'Suspensa',
        'cancelled' => 'Cancelada',
        'past_due'  => 'Atrasada',
        default     => $subscription->status,
    };
@endphp

{{-- Header --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="{{ route('admin.billing.index') }}"
           style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text3);text-decoration:none;transition:all 150ms"
           onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
           onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div>
            <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">{{ $subscription->company->name ?? '—' }}</h1>
            <p style="font-size:14px;color:var(--sa-text3);margin:0">{{ $subscription->company->email ?? '' }}</p>
        </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        {{-- Gerar fatura --}}
        @if(in_array($subscription->status, ['active','trial','grace']))
        <form method="POST" action="{{ route('admin.billing.fatura', $subscription) }}" id="form-fatura">
            @csrf
            <button type="button"
                    onclick="Swal.fire({title:'Gerar fatura?',text:'Uma nova cobrança será criada e enviada ao gateway.',icon:'question',showCancelButton:true,confirmButtonText:'Sim, gerar',cancelButtonText:'Cancelar',confirmButtonColor:'var(--sa-primary)',cancelButtonColor:'transparent',customClass:{cancelButton:'swal-cancel-muted'}}).then(r=>{if(r.isConfirmed)document.getElementById('form-fatura').submit()})"
                    style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:var(--sa-secondary);color:var(--sa-primary);transition:filter 200ms"
                    onmouseover="this.style.filter='brightness(1.08)'" onmouseout="this.style.filter='none'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                Gerar Fatura
            </button>
        </form>
        @endif

        {{-- Reativar --}}
        @if(in_array($subscription->status, ['suspended','cancelled','grace']))
        <form method="POST" action="{{ route('admin.billing.reativar', $subscription) }}" id="form-reativar">
            @csrf @method('PATCH')
            <button type="button"
                    onclick="Swal.fire({title:'Reativar assinatura?',icon:'question',showCancelButton:true,confirmButtonText:'Sim, reativar',cancelButtonText:'Cancelar',confirmButtonColor:'#059669',cancelButtonColor:'transparent',customClass:{cancelButton:'swal-cancel-muted'}}).then(r=>{if(r.isConfirmed)document.getElementById('form-reativar').submit()})"
                    style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:rgba(16,185,129,.12);color:#059669;transition:filter 200ms"
                    onmouseover="this.style.filter='brightness(.92)'" onmouseout="this.style.filter='none'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
                Reativar
            </button>
        </form>
        @endif

        {{-- Suspender --}}
        @if(in_array($subscription->status, ['active','trial','grace']))
        <form method="POST" action="{{ route('admin.billing.suspender', $subscription) }}" id="form-suspender">
            @csrf @method('PATCH')
            <button type="button"
                    onclick="Swal.fire({title:'Suspender assinatura?',text:'A empresa perderá acesso ao painel.',icon:'warning',showCancelButton:true,confirmButtonText:'Sim, suspender',cancelButtonText:'Cancelar',confirmButtonColor:'#f59e0b',cancelButtonColor:'transparent',customClass:{cancelButton:'swal-cancel-muted'}}).then(r=>{if(r.isConfirmed)document.getElementById('form-suspender').submit()})"
                    style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:rgba(245,158,11,.12);color:#d97706;transition:filter 200ms"
                    onmouseover="this.style.filter='brightness(.92)'" onmouseout="this.style.filter='none'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                Suspender
            </button>
        </form>
        @endif

        {{-- Cancelar --}}
        @if($subscription->status !== 'cancelled')
        <form method="POST" action="{{ route('admin.billing.cancelar', $subscription) }}" id="form-cancelar">
            @csrf @method('PATCH')
            <button type="button"
                    onclick="Swal.fire({title:'Cancelar assinatura?',text:'Esta ação encerrará a assinatura permanentemente.',icon:'warning',showCancelButton:true,confirmButtonText:'Sim, cancelar',cancelButtonText:'Voltar',confirmButtonColor:'#ef4444',cancelButtonColor:'transparent',customClass:{cancelButton:'swal-cancel-muted'}}).then(r=>{if(r.isConfirmed)document.getElementById('form-cancelar').submit()})"
                    style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:rgba(239,68,68,.1);color:#dc2626;transition:filter 200ms"
                    onmouseover="this.style.filter='brightness(.92)'" onmouseout="this.style.filter='none'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                Cancelar
            </button>
        </form>
        @endif
    </div>
</div>

@if(session('success'))
<div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:10px;padding:12px 18px;margin-bottom:20px;font-size:14px;color:#059669;display:flex;align-items:center;gap:8px">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    {{ session('success') }}
</div>
@endif

{{-- Detalhes da assinatura --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <h2 style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin:0 0 18px">Assinatura</h2>
        <dl style="display:flex;flex-direction:column;gap:12px">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <dt style="font-size:13px;color:var(--sa-text3)">Status</dt>
                <dd style="margin:0">
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $statusColor['bg'] }};color:{{ $statusColor['color'] }}">
                        <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                        {{ $statusLabel }}
                    </span>
                </dd>
            </div>
            <div style="display:flex;justify-content:space-between">
                <dt style="font-size:13px;color:var(--sa-text3)">Plano</dt>
                <dd style="margin:0;font-size:13px;font-weight:600;color:var(--sa-text1)">{{ $subscription->plan_slug ?? '—' }}</dd>
            </div>
            <div style="display:flex;justify-content:space-between">
                <dt style="font-size:13px;color:var(--sa-text3)">Valor/mês</dt>
                <dd style="margin:0;font-size:14px;font-weight:700;color:var(--sa-secondary)">R$ {{ number_format($subscription->monthly_amount ?? 0, 2, ',', '.') }}</dd>
            </div>
            <div style="display:flex;justify-content:space-between">
                <dt style="font-size:13px;color:var(--sa-text3)">Dia de cobrança</dt>
                <dd style="margin:0;font-size:13px;color:var(--sa-text1)">dia {{ $subscription->anniversary_day ?? '—' }}</dd>
            </div>
            @if($subscription->trial_ends_at)
            <div style="display:flex;justify-content:space-between">
                <dt style="font-size:13px;color:var(--sa-text3)">Trial expira</dt>
                <dd style="margin:0;font-size:13px;color:var(--sa-text1)">{{ \Carbon\Carbon::parse($subscription->trial_ends_at)->format('d/m/Y') }}</dd>
            </div>
            @endif
            @if($subscription->current_period_start)
            <div style="display:flex;justify-content:space-between">
                <dt style="font-size:13px;color:var(--sa-text3)">Período atual</dt>
                <dd style="margin:0;font-size:12px;color:var(--sa-text2)">
                    {{ \Carbon\Carbon::parse($subscription->current_period_start)->format('d/m/Y') }} →
                    {{ \Carbon\Carbon::parse($subscription->current_period_end)->format('d/m/Y') }}
                </dd>
            </div>
            @endif
            @if($subscription->suspended_at)
            <div style="display:flex;justify-content:space-between">
                <dt style="font-size:13px;color:#dc2626">Suspensa em</dt>
                <dd style="margin:0;font-size:13px;color:#dc2626">{{ \Carbon\Carbon::parse($subscription->suspended_at)->format('d/m/Y H:i') }}</dd>
            </div>
            @endif
            @if($subscription->cancelled_at)
            <div style="display:flex;justify-content:space-between">
                <dt style="font-size:13px;color:var(--sa-text3)">Cancelada em</dt>
                <dd style="margin:0;font-size:13px;color:var(--sa-text2)">{{ \Carbon\Carbon::parse($subscription->cancelled_at)->format('d/m/Y H:i') }}</dd>
            </div>
            @endif
        </dl>
    </div>

    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <h2 style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin:0 0 18px">Empresa</h2>
        <dl style="display:flex;flex-direction:column;gap:12px">
            <div style="display:flex;justify-content:space-between">
                <dt style="font-size:13px;color:var(--sa-text3)">Nome</dt>
                <dd style="margin:0;font-size:13px;font-weight:600;color:var(--sa-text1)">{{ $subscription->company->name ?? '—' }}</dd>
            </div>
            <div style="display:flex;justify-content:space-between">
                <dt style="font-size:13px;color:var(--sa-text3)">E-mail</dt>
                <dd style="margin:0;font-size:13px;color:var(--sa-text2)">{{ $subscription->company->email ?? '—' }}</dd>
            </div>
            <div style="display:flex;justify-content:space-between">
                <dt style="font-size:13px;color:var(--sa-text3)">Cadastro</dt>
                <dd style="margin:0;font-size:13px;color:var(--sa-text2)">{{ optional($subscription->company->created_at)->format('d/m/Y') ?? '—' }}</dd>
            </div>
            @if($subscription->gateway_customer_id)
            <div style="display:flex;justify-content:space-between">
                <dt style="font-size:13px;color:var(--sa-text3)">Gateway ID</dt>
                <dd style="margin:0;font-size:12px;font-family:monospace;color:var(--sa-text3)">{{ $subscription->gateway_customer_id }}</dd>
            </div>
            @endif
        </dl>
        <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--sa-border)">
            <a href="{{ route('admin.empresas.show', $subscription->company) }}"
               style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--sa-secondary);font-weight:600;text-decoration:none"
               onmouseover="this.style.opacity='.75'" onmouseout="this.style.opacity='1'">
                Ver empresa completa
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        </div>
    </div>
</div>

{{-- Histórico de faturas --}}
<div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)">
    <div style="padding:18px 20px;border-bottom:1px solid var(--sa-border);display:flex;align-items:center;justify-content:space-between">
        <h2 style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin:0">Faturas</h2>
        <span style="font-size:13px;color:var(--sa-text3)">{{ $invoices->count() }} registros</span>
    </div>
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;min-width:700px">
            <thead>
                <tr style="background:var(--sa-surface2);border-bottom:1px solid var(--sa-border)">
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Número</th>
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Status</th>
                    <th style="padding:11px 16px;text-align:right;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Valor</th>
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Vencimento</th>
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Pago em</th>
                    <th style="padding:11px 16px;text-align:right;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                @php
                    $invColor = match($invoice->status) {
                        'paid'      => ['bg'=>'rgba(16,185,129,.12)','color'=>'#059669','label'=>'Paga'],
                        'pending'   => ['bg'=>'rgba(245,158,11,.12)','color'=>'#d97706','label'=>'Pendente'],
                        'overdue'   => ['bg'=>'rgba(239,68,68,.1)','color'=>'#dc2626','label'=>'Vencida'],
                        'cancelled' => ['bg'=>'rgba(107,114,128,.12)','color'=>'#6b7280','label'=>'Cancelada'],
                        default     => ['bg'=>'rgba(107,114,128,.12)','color'=>'#6b7280','label'=>$invoice->status],
                    };
                @endphp
                <tr style="border-bottom:1px solid var(--sa-border);transition:background 120ms"
                    onmouseover="this.style.background='var(--sa-surface2)'"
                    onmouseout="this.style.background='transparent'">
                    <td style="padding:13px 16px;font-size:13px;font-family:monospace;color:var(--sa-text1);font-weight:600">{{ $invoice->number }}</td>
                    <td style="padding:13px 16px">
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $invColor['bg'] }};color:{{ $invColor['color'] }}">
                            <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                            {{ $invColor['label'] }}
                        </span>
                    </td>
                    <td style="padding:13px 16px;text-align:right;font-size:14px;font-weight:700;color:var(--sa-secondary)">
                        R$ {{ number_format($invoice->amount, 2, ',', '.') }}
                    </td>
                    <td style="padding:13px 16px;font-size:13px;color:var(--sa-text2)">
                        {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}
                    </td>
                    <td style="padding:13px 16px;font-size:13px;color:var(--sa-text2)">
                        {{ $invoice->paid_at ? \Carbon\Carbon::parse($invoice->paid_at)->format('d/m/Y H:i') : '—' }}
                    </td>
                    <td style="padding:13px 16px;text-align:right">
                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:6px">
                            @if($invoice->gateway_payment_url)
                            <a href="{{ $invoice->gateway_payment_url }}" target="_blank"
                               style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;color:var(--sa-text3);text-decoration:none;transition:all 150ms"
                               onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'"
                               onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'"
                               title="Ver link de pagamento">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                            </a>
                            @endif
                            @if(in_array($invoice->status, ['pending','overdue']))
                            <form method="POST" action="{{ route('admin.billing.invoice.paga', $invoice) }}" id="form-pagar-{{ $invoice->id }}" style="display:inline">
                                @csrf @method('PATCH')
                                <button type="button"
                                        onclick="Swal.fire({title:'Marcar {{ $invoice->number }} como paga?',text:'Isso reativará a assinatura se estiver suspensa.',icon:'question',showCancelButton:true,confirmButtonText:'Sim, marcar paga',cancelButtonText:'Cancelar',confirmButtonColor:'#059669',cancelButtonColor:'transparent',customClass:{cancelButton:'swal-cancel-muted'}}).then(r=>{if(r.isConfirmed)document.getElementById('form-pagar-{{ $invoice->id }}').submit()})"
                                        style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;color:var(--sa-text3);transition:all 150ms"
                                        onmouseover="this.style.borderColor='#059669';this.style.color='#059669'"
                                        onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'"
                                        title="Marcar como paga">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding:40px 16px;text-align:center;color:var(--sa-text3);font-size:14px">Nenhuma fatura encontrada.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
