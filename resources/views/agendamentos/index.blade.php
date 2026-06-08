@extends('layouts.app')
@section('title', 'Agendamentos')
@section('page-title', 'Agendamentos')

@section('content')

    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
        <div>
            <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">Agendamentos</h1>
            <p style="font-size:14px;color:var(--sa-text3);margin:0">{{ $agendamentos->total() }} agendamento{{ $agendamentos->total() !== 1 ? 's' : '' }}</p>
        </div>
        @can('create', \App\Models\Agendamento::class)
        <a href="{{ route('agendamentos.create') }}"
           style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;background:var(--sa-primary);color:#fff;text-decoration:none;font-size:14px;font-weight:600;transition:filter 200ms"
           onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Novo Agendamento
        </a>
        @endcan
    </div>

    {{-- Filtros --}}
    <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px;align-items:flex-end">
        <div>
            <label style="display:block;font-size:11px;font-weight:600;color:var(--sa-text3);margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em">Data</label>
            <input type="date" name="data" value="{{ request('data') }}"
                   style="padding:8px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                   onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
        </div>
        <div>
            <label style="display:block;font-size:11px;font-weight:600;color:var(--sa-text3);margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em">Status</label>
            <select name="status"
                    style="padding:8px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;color:var(--sa-text1);background:var(--sa-surface);outline:none;cursor:pointer;transition:border-color 180ms,outline 180ms"
                    onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                <option value="">Todos (exceto cancelados)</option>
                @foreach(['pendente','confirmado','finalizado','cancelado'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display:block;font-size:11px;font-weight:600;color:var(--sa-text3);margin-bottom:4px;text-transform:uppercase;letter-spacing:.05em">Profissional</label>
            <select name="profissional_id"
                    style="padding:8px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;color:var(--sa-text1);background:var(--sa-surface);outline:none;cursor:pointer;transition:border-color 180ms,outline 180ms"
                    onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                <option value="">Todos</option>
                @foreach($profissionais as $prof)
                <option value="{{ $prof->id }}" {{ request('profissional_id') === $prof->id ? 'selected' : '' }}>{{ $prof->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;gap:8px">
            <button type="submit"
                    style="padding:9px 16px;border-radius:8px;border:none;cursor:pointer;font-size:13px;font-weight:600;background:var(--sa-primary);color:#fff;transition:filter 200ms"
                    onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                Filtrar
            </button>
            @if(request()->hasAny(['data','status','profissional_id']))
            <a href="{{ route('agendamentos.index') }}"
               style="padding:9px 14px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:13px;font-weight:600;text-decoration:none;transition:all 180ms"
               onmouseover="this.style.borderColor='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)'">
                Limpar
            </a>
            @endif
        </div>
    </form>

    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:var(--sa-surface2);border-bottom:1px solid var(--sa-border)">
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Cliente</th>
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em" class="hide-mobile">Data / Hora</th>
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em" class="hide-mobile">Profissional</th>
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em" class="hide-mobile">Serviço</th>
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Status</th>
                    <th style="padding:11px 16px;text-align:right;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($agendamentos as $ag)
                @php
                    $ini = strtoupper(substr($ag->cliente->name ?? '?', 0, 1));
                    $badgeStyle = match($ag->status) {
                        'confirmado' => 'background:rgba(16,185,129,.12);color:#059669',
                        'finalizado' => 'background:rgba(107,114,128,.12);color:#6b7280',
                        'cancelado'  => 'background:rgba(239,68,68,.1);color:#dc2626',
                        default      => 'background:rgba(245,158,11,.12);color:#d97706',
                    };
                @endphp
                <tr style="border-bottom:1px solid var(--sa-border);transition:background 120ms" onmouseover="this.style.background='var(--sa-surface2)'" onmouseout="this.style.background='transparent'">
                    <td style="padding:14px 16px">
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:32px;height:32px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">{{ $ini }}</div>
                            <div>
                                <div style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $ag->cliente->name ?? '—' }}</div>
                                <div style="font-size:12px;color:var(--sa-text3)">{{ $ag->data_hora->format('d/m H:i') }}</div>
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
                        <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;{{ $badgeStyle }}"><span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>{{ ucfirst($ag->status) }}</span>
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
                                <button type="submit" title="Cancelar" style="width:30px;height:30px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--sa-text3);transition:all 150ms" onmouseover="this.style.borderColor='#e53e3e';this.style.color='#e53e3e'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
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
                        <a href="{{ route('agendamentos.create') }}" style="color:var(--sa-secondary);font-weight:600;text-decoration:none"> Criar agendamento</a>
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

@push('scripts')
<script>
function confirmDelete(e) {
    e.preventDefault();
    const form = e.target;
    Swal.fire({
        title: 'Cancelar agendamento?',
        text: 'O agendamento será marcado como cancelado.',
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
