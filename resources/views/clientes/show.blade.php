@extends('layouts.app')
@section('title', $cliente->name)
@section('page-title', 'Cliente')

@section('content')

    {{-- Cabe�alho --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:14px">
            <a href="{{ route('clientes.index') }}" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--sa-border);border-radius:8px;text-decoration:none;color:var(--sa-text3);flex-shrink:0;transition:all 150ms" onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
            @php $ini = strtoupper(substr($cliente->name, 0, 1)); @endphp
            <div style="width:48px;height:48px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;flex-shrink:0">{{ $ini }}</div>
            <div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <h1 style="font-family:var(--sa-font-heading);font-size:20px;font-weight:700;color:var(--sa-text1);margin:0">{{ $cliente->name }}</h1>
                    @if($cliente->lgpd_consent)
                    <span style="display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;background:rgba(16,185,129,.12);color:#059669"><span style="width:4px;height:4px;border-radius:50%;background:currentColor;flex-shrink:0"></span>LGPD</span>
                    @endif
                </div>
                <p style="font-size:13px;color:var(--sa-text3);margin:2px 0 0">Cliente desde {{ $cliente->created_at->format('d/m/Y') }}</p>
            </div>
        </div>
        <div style="display:flex;gap:8px">
            @can('update', $cliente)
            <a href="{{ route('clientes.edit', $cliente) }}"
               style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:13px;font-weight:600;text-decoration:none;transition:all 150ms"
               onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Editar
            </a>
            @endcan
            @can('delete', $cliente)
            <form method="POST" action="{{ route('clientes.destroy', $cliente) }}" onsubmit="return confirmDelete(event, '{{ $cliente->name }}')">
                @csrf @method('DELETE')
                <button type="submit"
                        style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;cursor:pointer;color:var(--sa-text3);font-size:13px;font-weight:600;transition:all 150ms"
                        onmouseover="this.style.borderColor='#e53e3e';this.style.color='#e53e3e'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                    Excluir
                </button>
            </form>
            @endcan
        </div>
    </div>

    {{-- Stats row --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px">
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:16px 18px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <div style="font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px">Agendamentos</div>
            <div style="font-family:var(--sa-font-heading);font-size:26px;font-weight:800;color:var(--sa-text1);line-height:1">{{ $totalAgendamentos }}</div>
        </div>
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:16px 18px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <div style="font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px">Receita Total</div>
            <div style="font-family:var(--sa-font-heading);font-size:26px;font-weight:800;color:var(--sa-secondary);line-height:1">R$&nbsp;{{ number_format($receitaTotal, 0, ',', '.') }}</div>
        </div>
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:16px 18px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <div style="font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px">Ticket Médio</div>
            <div style="font-family:var(--sa-font-heading);font-size:26px;font-weight:800;color:var(--sa-text1);line-height:1">
                R$&nbsp;{{ $totalAgendamentos > 0 ? number_format($receitaTotal / $totalAgendamentos, 0, ',', '.') : '0' }}
            </div>
        </div>
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:16px 18px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <div style="font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px">Nota Média</div>
            <div style="font-family:var(--sa-font-heading);font-size:26px;font-weight:800;color:var(--sa-secondary);line-height:1;display:flex;align-items:center;gap:5px">
                @if($notaMedia !== null)
                {{ number_format((float) $notaMedia, 1, ',', '') }}
                <svg width="16" height="16" viewBox="0 0 24 24" fill="var(--sa-secondary)" stroke="none" style="margin-bottom:2px"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                @else
                <span style="font-size:16px;color:var(--sa-text3);font-weight:400">—</span>
                @endif
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:300px 1fr;gap:20px;align-items:start">

        {{-- Informa��es --}}
        <div style="display:flex;flex-direction:column;gap:16px">
            <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:20px">
                <h2 style="font-size:13px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.06em;margin:0 0 16px">Informa��es</h2>
                <div style="display:flex;flex-direction:column;gap:12px">
                    @if($cliente->phone)
                    <div style="display:flex;gap:10px;align-items:flex-start">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.5a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.09a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0121.15 15z"/></svg>
                        <div>
                            <div style="font-size:11px;color:var(--sa-text3);margin-bottom:2px">Telefone</div>
                            <a href="https://wa.me/55{{ preg_replace('/\D/', '', $cliente->phone) }}" target="_blank" style="font-size:14px;color:var(--sa-text1);text-decoration:none;font-weight:500" onmouseover="this.style.color='var(--sa-secondary)'" onmouseout="this.style.color='var(--sa-text1)'">{{ $cliente->phone }}</a>
                        </div>
                    </div>
                    @endif

                    @if($cliente->email)
                    <div style="display:flex;gap:10px;align-items:flex-start">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <div>
                            <div style="font-size:11px;color:var(--sa-text3);margin-bottom:2px">E-mail</div>
                            <a href="mailto:{{ $cliente->email }}" style="font-size:14px;color:var(--sa-text1);text-decoration:none;font-weight:500" onmouseover="this.style.color='var(--sa-secondary)'" onmouseout="this.style.color='var(--sa-text1)'">{{ $cliente->email }}</a>
                        </div>
                    </div>
                    @endif

                    @if($cliente->data_nasc)
                    <div style="display:flex;gap:10px;align-items:flex-start">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <div>
                            <div style="font-size:11px;color:var(--sa-text3);margin-bottom:2px">Anivers�rio</div>
                            <div style="font-size:14px;color:var(--sa-text1);font-weight:500">{{ $cliente->data_nasc->format('d/m/Y') }}</div>
                        </div>
                    </div>
                    @endif

                    <div style="display:flex;gap:10px;align-items:flex-start">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <div>
                            <div style="font-size:11px;color:var(--sa-text3);margin-bottom:2px">Cadastrado em</div>
                            <div style="font-size:14px;color:var(--sa-text1);font-weight:500">{{ $cliente->created_at->format('d/m/Y \�\s H:i') }}</div>
                        </div>
                    </div>

                    <div style="padding-top:8px;border-top:1px solid var(--sa-border)">
                        @if($cliente->lgpd_consent)
                        <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;padding:4px 10px;border-radius:20px;background:rgba(16,185,129,.12);color:#059669">
                            <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                            Consentimento LGPD
                        </span>
                        @else
                        <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;padding:4px 10px;border-radius:20px;background:rgba(239,68,68,.1);color:#dc2626">
                            <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                            Sem consentimento LGPD
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            @if($cliente->observacao)
            <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:20px">
                <h2 style="font-size:13px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.06em;margin:0 0 10px">Observa��es</h2>
                <p style="font-size:14px;color:var(--sa-text2);line-height:1.6;margin:0;white-space:pre-wrap">{{ $cliente->observacao }}</p>
            </div>
            @endif
        </div>

        {{-- Agendamentos recentes --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <div style="padding:16px 20px;border-bottom:1px solid var(--sa-border);display:flex;align-items:center;justify-content:space-between">
                <h2 style="font-size:13px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.06em;margin:0">Hist�rico de Agendamentos</h2>
                <span style="font-size:12px;color:var(--sa-text3)">�ltimos {{ $cliente->agendamentos->count() }}</span>
            </div>

            @forelse($cliente->agendamentos as $ag)
            @php
                $badgeStyle = match($ag->status) {
                    'confirmado' => 'background:rgba(16,185,129,.12);color:#059669',
                    'finalizado' => 'background:rgba(107,114,128,.12);color:#6b7280',
                    'cancelado'  => 'background:rgba(239,68,68,.1);color:#dc2626',
                    default      => 'background:rgba(245,158,11,.12);color:#d97706',
                };
            @endphp
            <div style="padding:14px 20px;border-bottom:1px solid var(--sa-border);display:flex;align-items:center;justify-content:space-between;gap:12px" onmouseover="this.style.background='var(--sa-surface2)'" onmouseout="this.style.background='transparent'">
                <div style="min-width:0">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:3px">
                        <span style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $ag->data_hora->format('d/m/Y H:i') }}</span>
                        <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;{{ $badgeStyle }}"><span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>{{ ucfirst($ag->status) }}</span>
                        @if($ag->avaliacao)
                        <span style="display:inline-flex;align-items:center;gap:2px;font-size:11px;font-weight:700;color:var(--sa-secondary)" title="Avaliação dada: {{ $ag->avaliacao->nota }}/5">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="var(--sa-secondary)" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            {{ $ag->avaliacao->nota }}/5
                        </span>
                        @endif
                    </div>
                    <div style="font-size:12px;color:var(--sa-text3)">
                        {{ $ag->servico?->nome ?? '�' }} � {{ $ag->profissional?->name ?? '�' }}
                        @if($ag->valor) � R$ {{ number_format((float)$ag->valor, 2, ',', '.') }} @endif
                    </div>
                </div>
                <a href="{{ route('agendamentos.show', $ag) }}" style="flex-shrink:0;width:28px;height:28px;border-radius:7px;border:1px solid var(--sa-border);display:flex;align-items:center;justify-content:center;color:var(--sa-text3);text-decoration:none;transition:all 150ms" onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </a>
            </div>
            @empty
            <div style="padding:40px 20px;text-align:center;color:var(--sa-text3);font-size:14px">
                Nenhum agendamento registrado ainda.
                @can('create', \App\Models\Agendamento::class)
                <br><a href="{{ route('agendamentos.create') }}" style="color:var(--sa-secondary);font-weight:600;text-decoration:none;margin-top:6px;display:inline-block">Criar agendamento</a>
                @endcan
            </div>
            @endforelse
        </div>
    </div>

@push('scripts')
<script>
function confirmDelete(e, nome) {
    e.preventDefault();
    const form = e.target;
    Swal.fire({
        title: 'Excluir cliente?',
        text: `"${nome}" ser� removido permanentemente.`,
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
