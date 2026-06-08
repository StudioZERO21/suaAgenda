@extends('layouts.app')
@section('title', 'Agendamento')
@section('page-title', 'Agendamento')

@section('content')

    {{-- Cabeçalho --}}
    @php
        $badgeStyle = match($agendamento->status) {
            'confirmado' => 'background:rgba(16,185,129,.12);color:#059669',
            'finalizado' => 'background:rgba(107,114,128,.12);color:#6b7280',
            'cancelado'  => 'background:rgba(239,68,68,.1);color:#dc2626',
            default      => 'background:rgba(245,158,11,.12);color:#d97706',
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
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;padding:3px 10px;border-radius:20px;{{ $badgeStyle }}"><span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>{{ ucfirst($agendamento->status) }}</span>
                </div>
                <p style="font-size:13px;color:var(--sa-text3);margin:3px 0 0">{{ $agendamento->cliente?->name ?? '—' }} • {{ $agendamento->profissional?->name ?? '—' }}</p>
            </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            @if($agendamento->cliente?->phone)
            @php
                $whatsMsg = \App\Services\WhatsAppService::mensagemConfirmacao($agendamento);
                $whatsLink = \App\Services\WhatsAppService::link($agendamento->cliente->phone, $whatsMsg);
            @endphp
            <a href="{{ $whatsLink }}" target="_blank" rel="noopener"
               style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:8px;border:1.5px solid #25d366;background:transparent;color:#25d366;font-size:13px;font-weight:600;text-decoration:none;transition:all 150ms"
               onmouseover="this.style.background='#25d366';this.style.color='#fff'" onmouseout="this.style.background='transparent';this.style.color='#25d366'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                WhatsApp
            </a>
            @endif
            @can('update', $agendamento)
            <a href="{{ route('agendamentos.edit', $agendamento) }}"
               style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:13px;font-weight:600;text-decoration:none;transition:all 150ms"
               onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Editar
            </a>
            @endcan
        </div>
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
                        style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:8px;border:none;cursor:pointer;font-size:13px;font-weight:600;background:rgba(5,150,105,.1);color:#065f46;transition:background 150ms"
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
                        style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:8px;border:none;cursor:pointer;font-size:13px;font-weight:600;background:rgba(107,114,128,.1);color:#374151;transition:background 150ms"
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
                        style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:8px;border:1.5px solid var(--sa-border);cursor:pointer;font-size:13px;font-weight:600;background:transparent;color:var(--sa-text3);transition:all 150ms"
                        onmouseover="this.style.borderColor='#e53e3e';this.style.color='#e53e3e'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Cancelar agendamento
                </button>
            </form>
        </div>
    </div>
    @endif
    @endcan

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
