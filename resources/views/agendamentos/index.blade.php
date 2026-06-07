@extends('layouts.app')
@section('title', 'Agendamentos')
@section('page-title', 'Agendamentos')

@section('content')
<div style="max-width:1100px">

    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
        <div>
            <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">Agendamentos</h1>
            <p style="font-size:14px;color:var(--sa-text3);margin:0">{{ $agendamentos->total() }} agendamento{{ $agendamentos->total() !== 1 ? 's' : '' }}</p>
        </div>
        @can('create', \App\Models\Agendamento::class)
        <a href="{{ route('agendamentos.create') }}"
           style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:9px;background:var(--sa-primary);color:#fff;text-decoration:none;font-size:14px;font-weight:600;transition:background 180ms"
           onmouseover="this.style.background='var(--sa-secondary)'" onmouseout="this.style.background='var(--sa-primary)'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Novo Agendamento
        </a>
        @endcan
    </div>

    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden">
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:var(--sa-surface2);border-bottom:1px solid var(--sa-border)">
                    <th style="padding:11px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Cliente</th>
                    <th style="padding:11px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em" class="hide-mobile">Data / Hora</th>
                    <th style="padding:11px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em" class="hide-mobile">Profissional</th>
                    <th style="padding:11px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em" class="hide-mobile">Serviço</th>
                    <th style="padding:11px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Status</th>
                    <th style="padding:11px 16px;text-align:right;font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($agendamentos as $ag)
                @php
                    $ini = strtoupper(substr($ag->cliente->name ?? '?', 0, 1));
                    $badgeStyle = match($ag->status) {
                        'confirmado' => 'background:rgba(5,150,105,.1);color:#065f46',
                        'finalizado' => 'background:rgba(107,114,128,.1);color:#374151',
                        'cancelado'  => 'background:rgba(239,68,68,.1);color:#991b1b',
                        default      => 'background:rgba(245,158,11,.1);color:#92400e',
                    };
                @endphp
                <tr style="border-bottom:1px solid var(--sa-border);transition:background 120ms" onmouseover="this.style.background='rgba(0,0,0,.02)'" onmouseout="this.style.background='transparent'">
                    <td style="padding:14px 16px">
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:32px;height:32px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">{{ $ini }}</div>
                            <div>
                                <div style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $ag->cliente->name ?? '—' }}</div>
                                <div style="font-size:12px;color:var(--sa-text3)" class="hide-mobile-show">{{ $ag->data_hora->format('d/m/Y H:i') }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:14px 16px" class="hide-mobile">
                        <div style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $ag->data_hora->format('d/m/Y') }}</div>
                        <div style="font-size:12px;color:var(--sa-text3)">{{ $ag->data_hora->format('H:i') }} • {{ $ag->duracao }}min</div>
                    </td>
                    <td style="padding:14px 16px;font-size:14px;color:var(--sa-text2)" class="hide-mobile">{{ $ag->profissional->name ?? '—' }}</td>
                    <td style="padding:14px 16px" class="hide-mobile">
                        @if($ag->servico)
                        <div style="display:flex;align-items:center;gap:6px">
                            <div style="width:8px;height:8px;border-radius:50%;background:{{ $ag->servico->cor }};flex-shrink:0"></div>
                            <span style="font-size:13px;color:var(--sa-text2)">{{ $ag->servico->nome }}</span>
                        </div>
                        @else
                        <span style="font-size:14px;color:var(--sa-text3)">—</span>
                        @endif
                    </td>
                    <td style="padding:14px 16px">
                        <span style="font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;{{ $badgeStyle }}">{{ ucfirst($ag->status) }}</span>
                    </td>
                    <td style="padding:14px 16px;text-align:right">
                        <div style="display:inline-flex;gap:4px">
                            <a href="{{ route('agendamentos.show', $ag) }}" title="Ver" style="width:30px;height:30px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;display:flex;align-items:center;justify-content:center;color:var(--sa-text3);text-decoration:none;transition:all 150ms" onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            @can('update', $ag)
                            <a href="{{ route('agendamentos.edit', $ag) }}" title="Editar" style="width:30px;height:30px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;display:flex;align-items:center;justify-content:center;color:var(--sa-text3);text-decoration:none;transition:all 150ms" onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            @endcan
                            @can('delete', $ag)
                            <form method="POST" action="{{ route('agendamentos.destroy', $ag) }}" onsubmit="return confirmDelete(event)">
                                @csrf @method('DELETE')
                                <button type="submit" title="Excluir" style="width:30px;height:30px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--sa-text3);transition:all 150ms" onmouseover="this.style.borderColor='#e53e3e';this.style.color='#e53e3e'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                                </button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding:48px 16px;text-align:center;color:var(--sa-text3);font-size:14px">
                        Nenhum agendamento encontrado.
                        @can('create', \App\Models\Agendamento::class)
                        <a href="{{ route('agendamentos.create') }}" style="color:var(--sa-secondary);font-weight:600;text-decoration:none"> Criar primeiro agendamento</a>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($agendamentos->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--sa-border);background:var(--sa-surface2)">
            {{ $agendamentos->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function confirmDelete(e) {
    e.preventDefault();
    const form = e.target;
    Swal.fire({
        title: 'Excluir agendamento?',
        text: 'Esta ação não pode ser desfeita.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#e53e3e',
    }).then(r => { if (r.isConfirmed) form.submit(); });
    return false;
}
</script>
@endpush
@endsection
