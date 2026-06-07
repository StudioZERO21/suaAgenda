@extends('layouts.app')
@section('title', 'Agendamento')
@section('page-title', 'Agendamento')

@section('content')
<div style="max-width:800px">

    {{-- Cabeçalho --}}
    @php
        $badgeStyle = match($agendamento->status) {
            'confirmado' => 'background:rgba(5,150,105,.1);color:#065f46',
            'finalizado' => 'background:rgba(107,114,128,.1);color:#374151',
            'cancelado'  => 'background:rgba(239,68,68,.1);color:#991b1b',
            default      => 'background:rgba(245,158,11,.1);color:#92400e',
        };
    @endphp

    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:14px">
            <a href="{{ route('agendamentos.index') }}" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--sa-border);border-radius:8px;text-decoration:none;color:var(--sa-text3);flex-shrink:0;transition:all 150ms" onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
            <div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                    <h1 style="font-family:'Poppins',sans-serif;font-size:20px;font-weight:700;color:var(--sa-text1);margin:0">{{ $agendamento->data_hora->format('d/m/Y \à\s H:i') }}</h1>
                    <span style="font-size:12px;font-weight:700;padding:3px 10px;border-radius:20px;{{ $badgeStyle }}">{{ ucfirst($agendamento->status) }}</span>
                </div>
                <p style="font-size:13px;color:var(--sa-text3);margin:3px 0 0">{{ $agendamento->cliente?->name ?? '—' }} • {{ $agendamento->profissional?->name ?? '—' }}</p>
            </div>
        </div>
        @can('update', $agendamento)
        <a href="{{ route('agendamentos.edit', $agendamento) }}"
           style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:9px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:13px;font-weight:600;text-decoration:none;transition:all 150ms"
           onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Editar
        </a>
        @endcan
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

        {{-- Card Cliente --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:20px">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--sa-text3);margin-bottom:12px">Cliente</div>
            @if($agendamento->cliente)
            <div style="display:flex;align-items:center;gap:10px">
                <div style="width:38px;height:38px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;flex-shrink:0">{{ strtoupper(substr($agendamento->cliente->name, 0, 1)) }}</div>
                <div>
                    <a href="{{ route('clientes.show', $agendamento->cliente) }}" style="font-size:15px;font-weight:600;color:var(--sa-text1);text-decoration:none" onmouseover="this.style.color='var(--sa-secondary)'" onmouseout="this.style.color='var(--sa-text1)'">{{ $agendamento->cliente->name }}</a>
                    @if($agendamento->cliente->phone)
                    <div style="font-size:12px;color:var(--sa-text3);margin-top:2px">{{ $agendamento->cliente->phone }}</div>
                    @endif
                </div>
            </div>
            @else
            <p style="font-size:14px;color:var(--sa-text3);margin:0">—</p>
            @endif
        </div>

        {{-- Card Profissional --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:20px">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--sa-text3);margin-bottom:12px">Profissional</div>
            @if($agendamento->profissional)
            <div style="display:flex;align-items:center;gap:10px">
                <div style="width:38px;height:38px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;flex-shrink:0">{{ strtoupper(substr($agendamento->profissional->name, 0, 1)) }}</div>
                <div>
                    <a href="{{ route('profissionais.show', $agendamento->profissional) }}" style="font-size:15px;font-weight:600;color:var(--sa-text1);text-decoration:none" onmouseover="this.style.color='var(--sa-secondary)'" onmouseout="this.style.color='var(--sa-text1)'">{{ $agendamento->profissional->name }}</a>
                    @if($agendamento->profissional->especialidade)
                    <div style="font-size:12px;color:var(--sa-text3);margin-top:2px">{{ $agendamento->profissional->especialidade }}</div>
                    @endif
                </div>
            </div>
            @else
            <p style="font-size:14px;color:var(--sa-text3);margin:0">—</p>
            @endif
        </div>
    </div>

    {{-- Detalhes do serviço e horário --}}
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:20px;margin-bottom:16px">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:20px">
            <div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--sa-text3);margin-bottom:6px">Data e Hora</div>
                <div style="font-size:15px;font-weight:600;color:var(--sa-text1)">{{ $agendamento->data_hora->format('d/m/Y') }}</div>
                <div style="font-size:13px;color:var(--sa-text3)">{{ $agendamento->data_hora->format('H:i') }}</div>
            </div>
            <div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--sa-text3);margin-bottom:6px">Duração</div>
                <div style="font-size:15px;font-weight:600;color:var(--sa-text1)">{{ $agendamento->duracao }} min</div>
                <div style="font-size:13px;color:var(--sa-text3)">Término: {{ $agendamento->data_hora->copy()->addMinutes($agendamento->duracao)->format('H:i') }}</div>
            </div>
            @if($agendamento->servico)
            <div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--sa-text3);margin-bottom:6px">Serviço</div>
                <div style="display:flex;align-items:center;gap:6px">
                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $agendamento->servico->cor }}"></span>
                    <span style="font-size:15px;font-weight:600;color:var(--sa-text1)">{{ $agendamento->servico->nome }}</span>
                </div>
                <div style="font-size:13px;color:var(--sa-text3)">{{ $agendamento->servico->categoria ?? '' }}</div>
            </div>
            @endif
            @if($agendamento->valor)
            <div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--sa-text3);margin-bottom:6px">Valor</div>
                <div style="font-size:18px;font-weight:700;color:var(--sa-secondary)">R$ {{ number_format((float)$agendamento->valor, 2, ',', '.') }}</div>
            </div>
            @endif
        </div>

        @if($agendamento->observacao)
        <div style="padding-top:16px;margin-top:16px;border-top:1px solid var(--sa-border)">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--sa-text3);margin-bottom:6px">Observação</div>
            <p style="font-size:14px;color:var(--sa-text2);line-height:1.6;margin:0;white-space:pre-wrap">{{ $agendamento->observacao }}</p>
        </div>
        @endif
    </div>

    {{-- Ações de status --}}
    @can('update', $agendamento)
    @if($agendamento->status !== 'cancelado')
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:20px">
        <div style="font-size:13px;font-weight:700;color:var(--sa-text2);margin-bottom:14px">Alterar Status</div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">

            @if($agendamento->status === 'pendente')
            <form method="POST" action="{{ route('agendamentos.updateStatus', $agendamento) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="confirmado">
                <button type="submit"
                        style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:9px;border:none;cursor:pointer;font-size:13px;font-weight:600;background:rgba(5,150,105,.1);color:#065f46;transition:background 150ms"
                        onmouseover="this.style.background='rgba(5,150,105,.2)'" onmouseout="this.style.background='rgba(5,150,105,.1)'">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Confirmar
                </button>
            </form>
            @endif

            @if(in_array($agendamento->status, ['pendente', 'confirmado']))
            <form method="POST" action="{{ route('agendamentos.updateStatus', $agendamento) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="finalizado">
                <button type="submit"
                        style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:9px;border:none;cursor:pointer;font-size:13px;font-weight:600;background:rgba(107,114,128,.1);color:#374151;transition:background 150ms"
                        onmouseover="this.style.background='rgba(107,114,128,.2)'" onmouseout="this.style.background='rgba(107,114,128,.1)'">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Finalizar
                </button>
            </form>
            @endif

            <form method="POST" action="{{ route('agendamentos.updateStatus', $agendamento) }}" onsubmit="return confirmCancelamento(event)">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="cancelado">
                <button type="submit"
                        style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:9px;border:1.5px solid var(--sa-border);cursor:pointer;font-size:13px;font-weight:600;background:transparent;color:var(--sa-text3);transition:all 150ms"
                        onmouseover="this.style.borderColor='#e53e3e';this.style.color='#e53e3e'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Cancelar agendamento
                </button>
            </form>
        </div>
    </div>
    @endif
    @endcan
</div>

@push('scripts')
<script>
function confirmCancelamento(e) {
    e.preventDefault();
    const form = e.target;
    Swal.fire({
        title: 'Cancelar agendamento?',
        text: 'Esta ação alterará o status para "Cancelado".',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, cancelar',
        cancelButtonText: 'Não',
        confirmButtonColor: '#e53e3e',
    }).then(r => { if (r.isConfirmed) form.submit(); });
    return false;
}
</script>
@endpush
@endsection
