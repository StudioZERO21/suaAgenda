@extends('layouts.app')
@section('title', 'Empresas')
@section('page-title', 'Empresas')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div>
        <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">Empresas</h1>
        <p style="font-size:14px;color:var(--sa-text3);margin:0">{{ $empresas->total() }} empresas cadastradas no sistema</p>
    </div>
    <form method="GET" action="{{ route('admin.empresas.index') }}" style="display:flex;gap:8px">
        <input type="text" name="q" value="{{ $busca }}" placeholder="Buscar por nome, slug ou e-mail"
               style="width:260px;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
               onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'"
               onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
        <button type="submit"
                style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:var(--sa-primary);color:#fff;transition:filter 200ms"
                onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">Buscar</button>
    </form>
</div>

<div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)">
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;min-width:760px">
            <thead>
                <tr style="background:var(--sa-surface2);border-bottom:1px solid var(--sa-border)">
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">Empresa</th>
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">Plano</th>
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">Usuários</th>
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">Agendamentos</th>
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">Trial até</th>
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">Status</th>
                    <th style="padding:11px 16px;text-align:right;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($empresas as $empresa)
                <tr style="border-bottom:1px solid var(--sa-border);transition:background 120ms"
                    onmouseover="this.style.background='var(--sa-surface2)'"
                    onmouseout="this.style.background='transparent'">
                    <td style="padding:14px 16px;font-size:14px;color:var(--sa-text1)">
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:34px;height:34px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;font-family:'Inter',sans-serif;flex-shrink:0">{{ strtoupper(substr($empresa->name, 0, 1)) }}</div>
                            <div>
                                <div style="font-weight:600">{{ $empresa->name }}</div>
                                <div style="font-size:12px;color:var(--sa-text3)">{{ $empresa->slug }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:14px 16px;font-size:14px;color:var(--sa-text2);text-transform:capitalize">{{ $empresa->plan_slug ?? '—' }}</td>
                    <td style="padding:14px 16px;font-size:14px;color:var(--sa-text2)">{{ $empresa->users_count }}</td>
                    <td style="padding:14px 16px;font-size:14px;color:var(--sa-text2)">{{ $empresa->agendamentos_count }}</td>
                    <td style="padding:14px 16px;font-size:14px;color:var(--sa-text2)">{{ $empresa->trial_ends_at?->format('d/m/Y') ?? '—' }}</td>
                    <td style="padding:14px 16px">
                        @if($empresa->ativo)
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(16,185,129,.12);color:#059669">
                            <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                            Ativa
                        </span>
                        @else
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(107,114,128,.12);color:#6b7280">
                            <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                            Inativa
                        </span>
                        @endif
                    </td>
                    <td style="padding:14px 16px;text-align:right">
                        <div style="display:inline-flex;gap:6px">
                            <a href="{{ route('admin.empresas.show', $empresa) }}" title="Detalhes"
                               style="width:30px;height:30px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;color:var(--sa-text3);transition:all 150ms;text-decoration:none"
                               onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'"
                               onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            <button type="button" title="{{ $empresa->ativo ? 'Desativar' : 'Ativar' }}"
                                    onclick="toggleEmpresa('{{ $empresa->id }}', '{{ addslashes($empresa->name) }}', {{ $empresa->ativo ? 'true' : 'false' }})"
                                    style="width:30px;height:30px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;color:var(--sa-text3);transition:all 150ms"
                                    onmouseover="this.style.borderColor='{{ $empresa->ativo ? '#ef4444' : 'var(--sa-secondary)' }}';this.style.color='{{ $empresa->ativo ? '#ef4444' : 'var(--sa-secondary)' }}'"
                                    onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 11-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="padding:32px 16px;text-align:center;font-size:14px;color:var(--sa-text3)">Nenhuma empresa encontrada.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px">
    {{ $empresas->links() }}
</div>

@push('scripts')
<script>
function toggleEmpresa(id, nome, ativa) {
    Swal.fire({
        title: (ativa ? 'Desativar ' : 'Ativar ') + nome + '?',
        text: ativa ? 'A vitrine e o acesso da empresa serão suspensos.' : 'A empresa voltará a operar normalmente.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: ativa ? 'Sim, desativar' : 'Sim, ativar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: ativa ? '#ef4444' : '#1a1a1a',
        cancelButtonColor: 'transparent',
        customClass: { cancelButton: 'swal-cancel-muted' },
    }).then(async r => {
        if (!r.isConfirmed) return;
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const resp = await fetch('/admin/empresas/' + id + '/toggle', {
            method: 'PATCH',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        });
        if (resp.ok) { window.location.reload(); }
        else { Swal.fire({ title: 'Erro', text: 'Não foi possível alterar o status.', icon: 'error', confirmButtonColor: '#1a1a1a' }); }
    });
}
</script>
@endpush
@endsection
