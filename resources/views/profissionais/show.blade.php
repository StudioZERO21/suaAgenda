@extends('layouts.app')
@section('title', $profissional->name)
@section('page-title', 'Profissional')

@section('content')
<div style="max-width:900px">

    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:14px">
            <a href="{{ route('profissionais.index') }}" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--sa-border);border-radius:8px;text-decoration:none;color:var(--sa-text3);flex-shrink:0;transition:all 150ms" onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
            @php $ini = strtoupper(substr($profissional->name, 0, 1)); @endphp
            <div style="width:48px;height:48px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;flex-shrink:0">{{ $ini }}</div>
            <div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <h1 style="font-family:'Poppins',sans-serif;font-size:20px;font-weight:700;color:var(--sa-text1);margin:0">{{ $profissional->name }}</h1>
                    @if($profissional->ativo)
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;background:rgba(16,185,129,.12);color:#059669"><span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>Ativo</span>
                    @else
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;background:rgba(107,114,128,.12);color:#6b7280"><span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>Inativo</span>
                    @endif
                </div>
                <p style="font-size:13px;color:var(--sa-text3);margin:2px 0 0">{{ $profissional->especialidade ?? 'Profissional' }}</p>
            </div>
        </div>
        <div style="display:flex;gap:8px">
            @can('update', $profissional)
            <a href="{{ route('profissionais.edit', $profissional) }}"
               style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:9px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:13px;font-weight:600;text-decoration:none;transition:all 150ms"
               onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Editar
            </a>
            @endcan
            @can('delete', $profissional)
            <form method="POST" action="{{ route('profissionais.destroy', $profissional) }}" onsubmit="return confirmDelete(event, '{{ $profissional->name }}')">
                @csrf @method('DELETE')
                <button type="submit"
                        style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:9px;border:1.5px solid var(--sa-border);background:transparent;cursor:pointer;color:var(--sa-text3);font-size:13px;font-weight:600;transition:all 150ms"
                        onmouseover="this.style.borderColor='#e53e3e';this.style.color='#e53e3e'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                    Excluir
                </button>
            </form>
            @endcan
        </div>
    </div>

    <div style="display:grid;grid-template-columns:300px 1fr;gap:20px;align-items:start">

        {{-- Serviços vinculados --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:20px">
            <h2 style="font-size:13px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.06em;margin:0 0 14px">Serviços</h2>
            @forelse($profissional->servicos as $servico)
            <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--sa-border)">
                <div style="width:10px;height:10px;border-radius:50%;background:{{ $servico->cor }};flex-shrink:0"></div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:13px;font-weight:600;color:var(--sa-text1)">{{ $servico->nome }}</div>
                    <div style="font-size:11px;color:var(--sa-text3)">{{ $servico->duracaoFormatada() }} • {{ $servico->precoFormatado() }}</div>
                </div>
            </div>
            @empty
            <p style="font-size:13px;color:var(--sa-text3);margin:0;text-align:center;padding:16px 0">Nenhum serviço vinculado.</p>
            @endforelse
            @can('update', $profissional)
            <a href="{{ route('profissionais.edit', $profissional) }}" style="display:block;text-align:center;margin-top:14px;font-size:12px;color:var(--sa-secondary);text-decoration:none;font-weight:600" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Gerenciar serviços</a>
            @endcan
        </div>

        {{-- Agendamentos recentes --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <div style="padding:16px 20px;border-bottom:1px solid var(--sa-border);display:flex;align-items:center;justify-content:space-between">
                <h2 style="font-size:13px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.06em;margin:0">Histórico de Agendamentos</h2>
                <span style="font-size:12px;color:var(--sa-text3)">Últimos {{ $profissional->agendamentos->count() }}</span>
            </div>

            @forelse($profissional->agendamentos as $ag)
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
                    </div>
                    <div style="font-size:12px;color:var(--sa-text3)">
                        {{ $ag->cliente?->name ?? '—' }} • {{ $ag->servico?->nome ?? '—' }}
                        @if($ag->valor) • R$ {{ number_format((float)$ag->valor, 2, ',', '.') }} @endif
                    </div>
                </div>
                <a href="{{ route('agendamentos.show', $ag) }}" style="flex-shrink:0;width:28px;height:28px;border-radius:7px;border:1px solid var(--sa-border);display:flex;align-items:center;justify-content:center;color:var(--sa-text3);text-decoration:none;transition:all 150ms" onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </a>
            </div>
            @empty
            <div style="padding:40px 20px;text-align:center;color:var(--sa-text3);font-size:14px">
                Nenhum agendamento registrado ainda.
            </div>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmDelete(e, nome) {
    e.preventDefault();
    const form = e.target;
    Swal.fire({
        title: 'Excluir profissional?',
        text: `"${nome}" será removido permanentemente.`,
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
