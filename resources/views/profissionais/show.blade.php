@extends('layouts.app')
@section('title', $profissional->name)
@section('page-title', 'Profissional')

@section('content')

    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:14px">
            <a href="{{ route('profissionais.index') }}" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--sa-border);border-radius:8px;text-decoration:none;color:var(--sa-text3);flex-shrink:0;transition:all 150ms" onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
            @php
                $ini = strtoupper(substr($profissional->name, 0, 1));
                $cor = $profissional->cor ?? '#1a1a1a';
            @endphp
            @if($profissional->foto_path)
            <div style="width:48px;height:48px;border-radius:50%;overflow:hidden;flex-shrink:0;border:2px solid var(--sa-border)">
                <img src="{{ \Illuminate\Support\Facades\Storage::url($profissional->foto_path) }}" alt="{{ $profissional->name }}" style="width:100%;height:100%;object-fit:cover">
            </div>
            @else
            <div style="width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#fff;flex-shrink:0;background:{{ $cor }}">{{ $ini }}</div>
            @endif
            <div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <h1 style="font-family:var(--sa-font-heading);font-size:20px;font-weight:700;color:var(--sa-text1);margin:0">{{ $profissional->name }}</h1>
                    @if($profissional->ativo)
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;background:rgba(16,185,129,.12);color:#059669"><span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>Ativo</span>
                    @else
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;background:rgba(107,114,128,.12);color:#6b7280"><span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>Inativo</span>
                    @endif
                </div>
                <p style="font-size:13px;color:var(--sa-text3);margin:2px 0 0">{{ $profissional->especialidade ?? 'Profissional' }}</p>
            </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            @can('update', $profissional)
            <a href="{{ route('profissionais.horarios', $profissional) }}"
               style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:13px;font-weight:600;text-decoration:none;transition:all 150ms"
               onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Horários
            </a>
            @endcan
            @can('update', $profissional)
            <a href="{{ route('profissionais.edit', $profissional) }}"
               style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:13px;font-weight:600;text-decoration:none;transition:all 150ms"
               onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Editar
            </a>
            @endcan
            @can('delete', $profissional)
            <form method="POST" action="{{ route('profissionais.destroy', $profissional) }}" onsubmit="return confirmDelete(event, '{{ addslashes($profissional->name) }}')">
                @csrf @method('DELETE')
                <button type="submit"
                        style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;cursor:pointer;color:var(--sa-text3);font-size:13px;font-weight:600;transition:all 150ms"
                        onmouseover="this.style.borderColor='#e53e3e';this.style.color='#e53e3e'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                    Excluir
                </button>
            </form>
            @endcan
        </div>
    </div>

    {{-- Stats do mês --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px">
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:16px 18px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <div style="font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px">Agend. este mês</div>
            <div style="font-family:var(--sa-font-heading);font-size:26px;font-weight:800;color:var(--sa-text1);line-height:1">{{ $totalMes }}</div>
        </div>
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:16px 18px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <div style="font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px">Receita este mês</div>
            <div style="font-family:var(--sa-font-heading);font-size:26px;font-weight:800;color:var(--sa-secondary);line-height:1">R$&nbsp;{{ number_format($receitaMes, 0, ',', '.') }}</div>
        </div>
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:16px 18px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <div style="font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px">Taxa Conclusão</div>
            <div style="font-family:var(--sa-font-heading);font-size:26px;font-weight:800;color:var(--sa-text1);line-height:1">{{ $taxaConclusao }}%</div>
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

        {{-- Sidebar info --}}
        <div style="display:flex;flex-direction:column;gap:14px">
            {{-- Serviços vinculados --}}
            <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:20px">
                <h2 style="font-size:13px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.06em;margin:0 0 14px">Serviços</h2>
                @forelse($profissional->servicos as $servico)
                <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--sa-border)">
                    <div style="width:10px;height:10px;border-radius:50%;background:{{ $servico->cor }};flex-shrink:0"></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:13px;font-weight:600;color:var(--sa-text1)">{{ $servico->nome }}</div>
                        <div style="font-size:11px;color:var(--sa-text3)">{{ $servico->duracaoFormatada() }} · {{ $servico->precoFormatado() }}</div>
                    </div>
                </div>
                @empty
                <p style="font-size:13px;color:var(--sa-text3);margin:0;text-align:center;padding:16px 0">Nenhum serviço vinculado.</p>
                @endforelse
                @can('update', $profissional)
                <a href="{{ route('profissionais.edit', $profissional) }}" style="display:block;text-align:center;margin-top:14px;font-size:12px;color:var(--sa-secondary);text-decoration:none;font-weight:600" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Gerenciar serviços</a>
                @endcan
            </div>

            {{-- Contact & Social --}}
            @if($profissional->phone || $profissional->admissao || $profissional->instagram || $profissional->tiktok || $profissional->facebook)
            <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:20px">
                <h2 style="font-size:13px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.06em;margin:0 0 14px">Contato & Info</h2>
                @if($profissional->phone)
                <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--sa-border)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.09 8.8a19.79 19.79 0 01-3.07-8.63A2 2 0 012 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 7.91a16 16 0 006.08 6.08l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92z"/></svg>
                    <span style="font-size:13px;color:var(--sa-text2)">{{ \App\Support\PhoneFormatter::format($profissional->phone) }}</span>
                </div>
                @endif
                @if($profissional->admissao)
                <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--sa-border)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span style="font-size:13px;color:var(--sa-text2)">Desde {{ $profissional->admissao->translatedFormat('d/m/Y') }}</span>
                </div>
                @endif
                @if($profissional->instagram)
                <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--sa-border)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                    <span style="font-size:13px;color:var(--sa-text2)">{{ $profissional->instagram }}</span>
                </div>
                @endif
                @if($profissional->tiktok)
                <div style="display:flex;align-items:center;gap:10px;padding:8px 0{{ $profissional->facebook ? ';border-bottom:1px solid var(--sa-border)' : '' }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2"><path d="M9 12a4 4 0 104 4V4a5 5 0 005 5"/></svg>
                    <span style="font-size:13px;color:var(--sa-text2)">{{ $profissional->tiktok }}</span>
                </div>
                @endif
                @if($profissional->facebook)
                <div style="display:flex;align-items:center;gap:10px;padding:8px 0">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
                    <span style="font-size:13px;color:var(--sa-text2)">{{ $profissional->facebook }}</span>
                </div>
                @endif
            </div>
            @endif
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
                    'confirmado'   => 'background:rgba(16,185,129,.12);color:#059669',
                    'finalizado'   => 'background:rgba(107,114,128,.12);color:#6b7280',
                    'cancelado'    => 'background:rgba(239,68,68,.1);color:#dc2626',
                    'em_atendimento' => 'background:rgba(99,102,241,.12);color:#6366f1',
                    default        => 'background:rgba(245,158,11,.12);color:#d97706',
                };
            @endphp
            <div style="padding:14px 20px;border-bottom:1px solid var(--sa-border);display:flex;align-items:center;justify-content:space-between;gap:12px" onmouseover="this.style.background='var(--sa-surface2)'" onmouseout="this.style.background='transparent'">
                <div style="min-width:0">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:3px">
                        <span style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $ag->data_hora->format('d/m/Y H:i') }}</span>
                        <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;{{ $badgeStyle }}"><span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>{{ ucfirst(str_replace('_', ' ', $ag->status)) }}</span>
                    </div>
                    <div style="font-size:12px;color:var(--sa-text3)">
                        {{ $ag->cliente?->name ?? '—' }} · {{ $ag->servico?->nome ?? '—' }}
                        @if($ag->valor) · R$ {{ number_format((float)$ag->valor, 2, ',', '.') }} @endif
                    </div>
                </div>
                <a href="{{ route('agendamentos.show', $ag) }}" style="flex-shrink:0;width:28px;height:28px;border-radius:7px;border:1px solid var(--sa-border);display:flex;align-items:center;justify-content:center;color:var(--sa-text3);text-decoration:none;transition:all 150ms" onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </a>
            </div>
            @empty
            <div style="padding:40px 20px;text-align:center;color:var(--sa-text3);font-size:14px">
                Nenhum agendamento registrado ainda.
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
        title: 'Excluir profissional?',
        text: `"${nome}" será removido permanentemente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#e53e3e',
        cancelButtonColor: 'transparent',
        customClass: { cancelButton: 'swal-cancel-muted' },
    }).then(r => { if (r.isConfirmed) form.submit(); });
    return false;
}
</script>
@endpush
@endsection
