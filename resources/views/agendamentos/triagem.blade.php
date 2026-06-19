@extends('layouts.app')
@section('title', 'Triagem de Agendamentos')
@section('page-title', 'Triagem de Agendamentos')

@section('content')
<x-sa.page>
    <x-sa.app-header title="Triagem de Agendamentos" subtitle="Aprovações, sinais e acompanhamento de pagamento">
        <x-slot:actions>
            <x-sa.btn href="{{ route('agendamentos.index') }}" variant="secondary" size="sm">
                Ver todos os agendamentos
            </x-sa.btn>
        </x-slot:actions>
    </x-sa.app-header>

    <x-sa.body>
        {{-- Filtros rápidos --}}
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px">
            @php
                $filtros = [
                    'todos'            => ['label' => 'Todos pendentes', 'count' => array_sum($contagens)],
                    'aguardando_sinal' => ['label' => 'Aguardando sinal', 'count' => $contagens['aguardando_sinal']],
                    'aprovacao'        => ['label' => 'Aprovação manual', 'count' => $contagens['aprovacao']],
                    'sinal_pago'       => ['label' => 'Sinal pago (rastrear)', 'count' => $contagens['sinal_pago']],
                ];
            @endphp
            @foreach($filtros as $key => $f)
            @php $ativo = $filtro === $key; @endphp
            <a href="{{ route('agendamentos.triagem', ['filtro' => $key]) }}"
               style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;transition:all 150ms;
                      {{ $ativo ? 'background:var(--sa-primary);color:#fff;border:1.5px solid var(--sa-primary)' : 'background:var(--sa-surface);color:var(--sa-text2);border:1.5px solid var(--sa-border)' }}"
               @if(!$ativo) onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
               onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'" @endif>
                {{ $f['label'] }}
                @if($f['count'] > 0)
                <span style="display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;border-radius:10px;font-size:11px;font-weight:700;padding:0 4px;
                             {{ $ativo ? 'background:rgba(255,255,255,.25);color:#fff' : 'background:rgba(239,68,68,.12);color:#dc2626' }}">
                    {{ $f['count'] }}
                </span>
                @endif
            </a>
            @endforeach
        </div>

        @if($agendamentos->isEmpty())
        <x-sa.card>
            <div style="padding:64px 0;text-align:center;color:var(--sa-text3)">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 16px;display:block;opacity:.3"><polyline points="20 6 9 17 4 12"/></svg>
                <div style="font-size:16px;font-weight:600;margin-bottom:4px">Nenhum agendamento aqui</div>
                <div style="font-size:14px">Tudo em dia — sem pendências para este filtro.</div>
            </div>
        </x-sa.card>
        @else
        <x-sa.card :flush="true">
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse">
                    <thead>
                        <tr style="background:var(--sa-surface2);border-bottom:1px solid var(--sa-border)">
                            <th class="sa-th">Cliente</th>
                            <th class="sa-th hide-mobile">Data / Hora</th>
                            <th class="sa-th hide-mobile">Serviço</th>
                            <th class="sa-th">Status</th>
                            <th class="sa-th">Sinal / Pagamento</th>
                            <th class="sa-th" style="text-align:right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($agendamentos as $ag)
                        <tr class="sa-tr" id="row-{{ $ag->id }}">
                            <td class="sa-td">
                                <div style="display:flex;align-items:center;gap:10px">
                                    <x-sa.avatar :name="$ag->cliente?->name ?? '?'" :size="32" />
                                    <div>
                                        <div style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $ag->cliente?->name ?? '—' }}</div>
                                        <div style="font-size:12px;color:var(--sa-text3)">{{ $ag->cliente?->phone }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="sa-td hide-mobile">
                                <div style="font-weight:600;color:var(--sa-text1)">{{ $ag->data_hora->format('d/m/Y') }}</div>
                                <div style="font-size:12px;color:var(--sa-text3)">{{ $ag->data_hora->format('H:i') }} • {{ $ag->duracao }}min</div>
                            </td>
                            <td class="sa-td hide-mobile" style="font-size:13px;color:var(--sa-text2)">{{ $ag->servico?->nome ?? '—' }}</td>
                            <td class="sa-td">
                                @if($ag->status === 'aguardando_sinal')
                                <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(245,158,11,.12);color:#d97706">
                                    <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                                    Ag. Sinal
                                </span>
                                @elseif($ag->status === 'pendente' && $ag->aprovacao_manual)
                                <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(99,102,241,.12);color:#6366f1">
                                    <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                                    Ap. Manual
                                </span>
                                @else
                                <x-sa.badge :status="$ag->status" :label="ucfirst($ag->status)" />
                                @endif
                            </td>
                            <td class="sa-td">
                                @if($ag->sinal_pct > 0)
                                <div style="display:flex;flex-direction:column;gap:3px">
                                    @if($ag->sinal_status === 'pago')
                                    <div style="font-size:12px;font-weight:600;color:#059669">
                                        ✓ Sinal R$ {{ number_format((float)$ag->sinal_valor, 2, ',', '.') }}
                                    </div>
                                    @if($ag->saldoDevido() > 0)
                                    <div style="font-size:11px;color:var(--sa-text3)">
                                        Saldo: R$ {{ number_format($ag->saldoDevido(), 2, ',', '.') }}
                                    </div>
                                    @endif
                                    @elseif($ag->sinal_status === 'pendente')
                                    <div style="font-size:12px;color:#d97706;font-weight:600">
                                        ⏳ R$ {{ number_format((float)$ag->sinal_valor, 2, ',', '.') }} pendente
                                    </div>
                                    @if($ag->sinal_payment_url)
                                    <a href="{{ $ag->sinal_payment_url }}" target="_blank" style="font-size:11px;color:var(--sa-secondary);text-decoration:none">
                                        Ver link →
                                    </a>
                                    @endif
                                    @elseif($ag->aprovacao_manual)
                                    <div style="font-size:12px;color:var(--sa-text3)">
                                        Paga no dia (R$ {{ number_format((float)$ag->valor, 2, ',', '.') }})
                                    </div>
                                    @endif
                                </div>
                                @else
                                <span style="font-size:12px;color:var(--sa-text3)">—</span>
                                @endif
                            </td>
                            <td class="sa-td" style="text-align:right">
                                <div style="display:inline-flex;gap:4px;align-items:center">
                                    {{-- Aprovar manualmente (aguardando_sinal ou pendente manual) --}}
                                    @can('update', $ag)
                                    @if(in_array($ag->status, ['aguardando_sinal', 'pendente']))
                                    <button type="button" title="Aprovar manualmente"
                                            onclick="aprovarManual('{{ $ag->id }}')"
                                            style="display:inline-flex;align-items:center;gap:5px;padding:6px 10px;border-radius:7px;border:1px solid rgba(5,150,105,.3);background:rgba(5,150,105,.06);color:#059669;font-size:12px;font-weight:600;cursor:pointer;transition:all 150ms;white-space:nowrap"
                                            onmouseover="this.style.background='rgba(5,150,105,.15)'"
                                            onmouseout="this.style.background='rgba(5,150,105,.06)'">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                        Aprovar
                                    </button>
                                    @endif
                                    {{-- Gerar link de saldo (sinal já pago) --}}
                                    @if($ag->sinalPago() && $ag->saldoDevido() > 0)
                                    <button type="button" title="Gerar link do saldo"
                                            onclick="linkSaldo('{{ $ag->id }}')"
                                            style="display:inline-flex;align-items:center;gap:5px;padding:6px 10px;border-radius:7px;border:1px solid rgba(212,165,116,.4);background:rgba(212,165,116,.08);color:var(--sa-secondary);font-size:12px;font-weight:600;cursor:pointer;transition:all 150ms;white-space:nowrap"
                                            onmouseover="this.style.background='rgba(212,165,116,.18)'"
                                            onmouseout="this.style.background='rgba(212,165,116,.08)'">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                        Link saldo
                                    </button>
                                    @endif
                                    @endcan
                                    <x-sa.icon-btn href="{{ route('agendamentos.show', $ag) }}" title="Ver">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </x-sa.icon-btn>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($agendamentos->hasPages())
            <div style="padding:12px 16px;border-top:1px solid var(--sa-border);background:var(--sa-surface2)">
                {{ $agendamentos->links() }}
            </div>
            @endif
        </x-sa.card>
        @endif
    </x-sa.body>
</x-sa.page>

@push('scripts')
<script>
function aprovarManual(id) {
    Swal.fire({
        title: 'Aprovar manualmente?',
        text: 'O agendamento será confirmado. O cliente pagará o valor integral no dia do procedimento.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sim, aprovar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#059669',
    }).then(r => {
        if (!r.isConfirmed) return;
        fetch(`/agendamentos/${id}/aprovar-manual`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
        }).then(res => res.json()).then(data => {
            if (data.ok) {
                Swal.fire({ icon: 'success', title: 'Aprovado!', timer: 1500, showConfirmButton: false })
                    .then(() => location.reload());
            } else {
                Swal.fire({ icon: 'error', title: 'Erro', text: data.message ?? 'Não foi possível aprovar.' });
            }
        }).catch(() => Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha na conexão.' }));
    });
}

function linkSaldo(id) {
    const btn = event.target.closest('button');
    if (btn) { btn.disabled = true; btn.textContent = '...'; }
    fetch(`/agendamentos/${id}/link-saldo`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
    }).then(res => res.json()).then(data => {
        if (btn) { btn.disabled = false; btn.textContent = 'Link saldo'; }
        if (data.ok && data.payment_url) {
            Swal.fire({
                title: 'Link gerado!',
                html: `<p style="font-size:14px;margin-bottom:12px">Saldo: <strong>R$ ${data.saldo}</strong></p>
                       <a href="${data.payment_url}" target="_blank" style="display:inline-flex;align-items:center;gap:6px;padding:10px 20px;border-radius:8px;background:#d4a574;color:#fff;font-weight:600;text-decoration:none;font-size:14px">Abrir link</a>`,
                icon: 'success',
                confirmButtonText: 'Fechar',
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Erro', text: data.message ?? data.erro ?? 'Não foi possível gerar o link.' });
        }
    }).catch(() => {
        if (btn) { btn.disabled = false; }
        Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha na conexão.' });
    });
}
</script>
@endpush
@endsection
