@extends('layouts.app')
@section('title', 'Clientes')
@section('page-title', 'Clientes')

@section('content')
<div style="max-width:1100px">

    {{-- Cabeçalho --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
        <div>
            <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">Clientes</h1>
            <p style="font-size:14px;color:var(--sa-text3);margin:0">{{ $clientes->total() }} cliente{{ $clientes->total() !== 1 ? 's' : '' }} cadastrado{{ $clientes->total() !== 1 ? 's' : '' }}</p>
        </div>
        @can('create', \App\Models\Cliente::class)
        <a href="{{ route('clientes.create') }}"
           style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:9px;background:var(--sa-primary);color:#fff;text-decoration:none;font-size:14px;font-weight:600;transition:filter 200ms"
           onmouseover="this.style.filter='brightness(1.15)'" onmouseout="this.style.filter='none'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Novo Cliente
        </a>
        @endcan
    </div>

    {{-- Busca --}}
    <form method="GET" style="margin-bottom:16px">
        <div style="position:relative;max-width:360px">
            <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--sa-text3);pointer-events:none">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nome, e-mail ou telefone..."
                   style="width:100%;padding:9px 12px 9px 34px;border:1.5px solid var(--sa-border);border-radius:9px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms"
                   onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
        </div>
    </form>

    {{-- Tabela --}}
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:var(--sa-surface2);border-bottom:1px solid var(--sa-border)">
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Nome</th>
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em" class="hide-mobile">Telefone</th>
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em" class="hide-mobile">E-mail</th>
                    <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em" class="hide-mobile">Cadastro</th>
                    <th style="padding:11px 16px;text-align:right;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clientes as $cliente)
                @php $ini = strtoupper(substr($cliente->name, 0, 1)); @endphp
                <tr style="border-bottom:1px solid var(--sa-border);transition:background 120ms" onmouseover="this.style.background='var(--sa-surface2)'" onmouseout="this.style.background='transparent'">
                    <td style="padding:14px 16px">
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:34px;height:34px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0">{{ $ini }}</div>
                            <div>
                                <a href="{{ route('clientes.show', $cliente) }}" style="font-size:14px;font-weight:600;color:var(--sa-text1);text-decoration:none;transition:color 150ms" onmouseover="this.style.color='var(--sa-secondary)'" onmouseout="this.style.color='var(--sa-text1)'">{{ $cliente->name }}</a>
                                @if($cliente->lgpd_consent)
                                <span style="display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:600;padding:1px 6px;border-radius:20px;background:rgba(16,185,129,.12);color:#059669;margin-left:6px"><span style="width:4px;height:4px;border-radius:50%;background:currentColor;flex-shrink:0"></span>LGPD</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="padding:14px 16px;font-size:14px;color:var(--sa-text2)" class="hide-mobile">{{ $cliente->phone ?? '—' }}</td>
                    <td style="padding:14px 16px;font-size:14px;color:var(--sa-text2)" class="hide-mobile">{{ $cliente->email ?? '—' }}</td>
                    <td style="padding:14px 16px;font-size:13px;color:var(--sa-text3)" class="hide-mobile">{{ $cliente->created_at->format('d/m/Y') }}</td>
                    <td style="padding:14px 16px;text-align:right">
                        <div style="display:inline-flex;gap:4px">
                            <a href="{{ route('clientes.show', $cliente) }}" title="Ver" style="width:30px;height:30px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;display:flex;align-items:center;justify-content:center;color:var(--sa-text3);text-decoration:none;transition:all 150ms" onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            @can('update', $cliente)
                            <a href="{{ route('clientes.edit', $cliente) }}" title="Editar" style="width:30px;height:30px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;display:flex;align-items:center;justify-content:center;color:var(--sa-text3);text-decoration:none;transition:all 150ms" onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            @endcan
                            @can('delete', $cliente)
                            <form method="POST" action="{{ route('clientes.destroy', $cliente) }}" onsubmit="return confirmDelete(event, '{{ $cliente->name }}')">
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
                    <td colspan="5" style="padding:48px 16px;text-align:center;color:var(--sa-text3);font-size:14px">
                        @if(request('search'))
                            Nenhum cliente encontrado para "<strong>{{ request('search') }}</strong>"
                        @else
                            Nenhum cliente cadastrado ainda.
                            @can('create', \App\Models\Cliente::class)
                            <a href="{{ route('clientes.create') }}" style="color:var(--sa-secondary);font-weight:600;text-decoration:none"> Cadastrar o primeiro</a>
                            @endcan
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($clientes->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--sa-border);background:var(--sa-surface2)">
            {{ $clientes->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function confirmDelete(e, nome) {
    e.preventDefault();
    const form = e.target;
    Swal.fire({
        title: 'Excluir cliente?',
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
