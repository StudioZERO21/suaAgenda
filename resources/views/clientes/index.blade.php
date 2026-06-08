@extends('layouts.app')
@section('title', 'Clientes')

@section('content')
<x-sa.page>
    <x-sa.app-header
        title="Clientes"
        :subtitle="$clientes->total() . ' cliente' . ($clientes->total() !== 1 ? 's' : '') . ' encontrado' . ($clientes->total() !== 1 ? 's' : '')">
        @can('create', \App\Models\Cliente::class)
        <x-slot:actions>
            <x-sa.btn href="{{ route('clientes.create') }}" :icon="view('components.sa.icons.plus')->render()">
                Novo Cliente
            </x-sa.btn>
        </x-slot:actions>
        @endcan
    </x-sa.app-header>

    <x-sa.body>
        <x-sa.card padding="14px 20px" style="margin-bottom:16px">
            <form method="GET">
                <div style="display:flex;gap:12px;align-items:center">
                    <div style="position:relative;flex:1">
                        <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--sa-text3);pointer-events:none;display:flex">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nome, e-mail ou telefone..." class="sa-search-input">
                    </div>
                    @if(request('search'))
                    <x-sa.btn href="{{ route('clientes.index') }}" variant="muted" size="sm">Limpar</x-sa.btn>
                    @endif
                </div>
            </form>
        </x-sa.card>

        <x-sa.card :flush="true">
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse">
                    <thead>
                        <tr style="background:var(--sa-surface2);border-bottom:1px solid var(--sa-border)">
                            <th class="sa-th">Cliente</th>
                            <th class="sa-th hide-mobile">E-mail</th>
                            <th class="sa-th hide-mobile">Telefone</th>
                            <th class="sa-th hide-mobile">Cadastro</th>
                            <th class="sa-th" style="text-align:right;width:80px">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clientes as $cliente)
                        <tr class="sa-tr">
                            <td class="sa-td">
                                <div style="display:flex;align-items:center;gap:10px">
                                    <x-sa.avatar :name="$cliente->name" />
                                    <div>
                                        <a href="{{ route('clientes.show', $cliente) }}" style="font-size:14px;font-weight:600;color:var(--sa-text1);text-decoration:none">{{ $cliente->name }}</a>
                                        @if($cliente->lgpd_consent)
                                        <x-sa.badge status="active" label="LGPD" style="margin-left:6px;font-size:10px;padding:1px 6px" />
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="sa-td hide-mobile" style="color:var(--sa-text2)">{{ $cliente->email ?? '—' }}</td>
                            <td class="sa-td hide-mobile" style="color:var(--sa-text2)">{{ $cliente->phone ?? '—' }}</td>
                            <td class="sa-td hide-mobile" style="font-size:13px;color:var(--sa-text3)">{{ $cliente->created_at->format('d/m/Y') }}</td>
                            <td class="sa-td" style="text-align:right">
                                <div style="display:inline-flex;gap:4px">
                                    <x-sa.icon-btn href="{{ route('clientes.show', $cliente) }}" title="Ver">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </x-sa.icon-btn>
                                    @can('update', $cliente)
                                    <x-sa.icon-btn href="{{ route('clientes.edit', $cliente) }}" title="Editar">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </x-sa.icon-btn>
                                    @endcan
                                    @can('delete', $cliente)
                                    <form method="POST" action="{{ route('clientes.destroy', $cliente) }}" onsubmit="return confirmDelete(event, '{{ $cliente->name }}')">
                                        @csrf @method('DELETE')
                                        <x-sa.icon-btn type="submit" title="Excluir" :danger="true">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                                        </x-sa.icon-btn>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="padding:48px 0;text-align:center;color:var(--sa-text3);font-size:14px">
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
            </div>
            @if($clientes->hasPages())
            <div style="padding:12px 16px;border-top:1px solid var(--sa-border);background:var(--sa-surface2)">
                {{ $clientes->links() }}
            </div>
            @endif
        </x-sa.card>
    </x-sa.body>
</x-sa.page>

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
