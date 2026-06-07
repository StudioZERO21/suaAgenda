@extends('layouts.app')
@section('title', 'Serviços')
@section('page-title', 'Serviços')

@section('content')
<div style="max-width:1100px">

    {{-- Cabeçalho --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
        <div>
            <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">Serviços</h1>
            <p style="font-size:14px;color:var(--sa-text3);margin:0">{{ $servicos->total() }} serviço{{ $servicos->total() !== 1 ? 's' : '' }} cadastrado{{ $servicos->total() !== 1 ? 's' : '' }}</p>
        </div>
        @can('create', \App\Models\Servico::class)
        <a href="{{ route('servicos.create') }}"
           style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:9px;background:var(--sa-primary);color:#fff;text-decoration:none;font-size:14px;font-weight:600;transition:background 180ms"
           onmouseover="this.style.background='var(--sa-secondary)'" onmouseout="this.style.background='var(--sa-primary)'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Novo Serviço
        </a>
        @endcan
    </div>

    {{-- Busca --}}
    <form method="GET" style="margin-bottom:16px">
        <div style="position:relative;max-width:360px">
            <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--sa-text3);pointer-events:none">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </span>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nome..."
                   style="width:100%;padding:9px 12px 9px 34px;border:1.5px solid var(--sa-border);border-radius:9px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms"
                   onfocus="this.style.borderColor='var(--sa-secondary)'" onblur="this.style.borderColor='var(--sa-border)'">
        </div>
    </form>

    {{-- Tabela --}}
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden">
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:var(--sa-surface2);border-bottom:1px solid var(--sa-border)">
                    <th style="padding:11px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Serviço</th>
                    <th style="padding:11px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em" class="hide-mobile">Categoria</th>
                    <th style="padding:11px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em" class="hide-mobile">Duração</th>
                    <th style="padding:11px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Preço</th>
                    <th style="padding:11px 16px;text-align:left;font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em" class="hide-mobile">Status</th>
                    <th style="padding:11px 16px;text-align:right;font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($servicos as $servico)
                <tr style="border-bottom:1px solid var(--sa-border);transition:background 120ms" onmouseover="this.style.background='rgba(0,0,0,.02)'" onmouseout="this.style.background='transparent'">
                    <td style="padding:14px 16px">
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:12px;height:12px;border-radius:50%;background:{{ $servico->cor }};flex-shrink:0"></div>
                            <div>
                                <div style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $servico->nome }}</div>
                                @if($servico->descricao)
                                <div style="font-size:12px;color:var(--sa-text3);margin-top:1px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $servico->descricao }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="padding:14px 16px;font-size:13px;color:var(--sa-text2)" class="hide-mobile">{{ $servico->categoria ?? '—' }}</td>
                    <td style="padding:14px 16px;font-size:13px;color:var(--sa-text2)" class="hide-mobile">{{ $servico->duracaoFormatada() }}</td>
                    <td style="padding:14px 16px;font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $servico->precoFormatado() }}</td>
                    <td style="padding:14px 16px" class="hide-mobile">
                        @if($servico->ativo)
                        <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;background:rgba(5,150,105,.1);color:#065f46">Ativo</span>
                        @else
                        <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;background:rgba(107,114,128,.1);color:#374151">Inativo</span>
                        @endif
                    </td>
                    <td style="padding:14px 16px;text-align:right">
                        <div style="display:inline-flex;gap:4px">
                            @can('update', $servico)
                            <a href="{{ route('servicos.edit', $servico) }}" title="Editar" style="width:30px;height:30px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;display:flex;align-items:center;justify-content:center;color:var(--sa-text3);text-decoration:none;transition:all 150ms" onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            @endcan
                            @can('delete', $servico)
                            <form method="POST" action="{{ route('servicos.destroy', $servico) }}" onsubmit="return confirmDelete(event, '{{ $servico->nome }}')">
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
                        @if(request('search'))
                            Nenhum serviço encontrado para "<strong>{{ request('search') }}</strong>"
                        @else
                            Nenhum serviço cadastrado ainda.
                            @can('create', \App\Models\Servico::class)
                            <a href="{{ route('servicos.create') }}" style="color:var(--sa-secondary);font-weight:600;text-decoration:none"> Cadastrar o primeiro</a>
                            @endcan
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($servicos->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--sa-border);background:var(--sa-surface2)">
            {{ $servicos->links() }}
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
        title: 'Excluir serviço?',
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
