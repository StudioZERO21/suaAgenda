@extends('layouts.app')
@section('title', 'Serviços')

@section('content')
<x-sa.page>
    <x-sa.app-header title="Serviços" subtitle="Gerencie os serviços oferecidos">
        @can('create', \App\Models\Servico::class)
        <x-slot:actions>
            <x-sa.btn href="{{ route('servicos.create') }}" :icon="view('components.sa.icons.plus')->render()">
                Novo Serviço
            </x-sa.btn>
        </x-slot:actions>
        @endcan
    </x-sa.app-header>

    <x-sa.body>
        <div class="sa-grid-4" style="margin-bottom:20px">
            <x-sa.tint-card label="Total de serviços" :value="$stats['total']" :icon="'<svg width=\'130\' height=\'130\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'var(--sa-primary)\' stroke-width=\'1.5\'><circle cx=\'6\' cy=\'6\' r=\'3\'/><circle cx=\'6\' cy=\'18\' r=\'3\'/><line x1=\'20\' y1=\'4\' x2=\'8.12\' y2=\'15.88\'/></svg>'" />
            <x-sa.tint-card label="Ativos" :value="$stats['ativos']" accent="#10b981" :icon="'<svg width=\'130\' height=\'130\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'#10b981\' stroke-width=\'1.5\'><polyline points=\'20 6 9 17 4 12\'/></svg>'" />
            <x-sa.tint-card label="Ticket médio" :value="'R$ ' . number_format($stats['ticket_medio'], 2, ',', '.')" accent="var(--sa-secondary)" :icon="'<svg width=\'130\' height=\'130\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'var(--sa-secondary)\' stroke-width=\'1.5\'><line x1=\'12\' y1=\'1\' x2=\'12\' y2=\'23\'/><path d=\'M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6\'/></svg>'" />
            <x-sa.tint-card label="Duração média" :value="$stats['duracao_media'] . 'min'" :icon="'<svg width=\'130\' height=\'130\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'var(--sa-primary)\' stroke-width=\'1.5\'><circle cx=\'12\' cy=\'12\' r=\'10\'/><polyline points=\'12 6 12 12 16 14\'/></svg>'" />
        </div>

        <form method="GET" style="margin-bottom:16px">
            <div style="position:relative;max-width:320px">
                <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--sa-text3);pointer-events:none;display:flex">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar serviço..." class="sa-search-input">
            </div>
        </form>

        <x-sa.card :flush="true">
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse">
                    <thead>
                        <tr style="background:var(--sa-surface2);border-bottom:1px solid var(--sa-border)">
                            <th class="sa-th">Serviço</th>
                            <th class="sa-th hide-mobile">Categoria</th>
                            <th class="sa-th hide-mobile">Duração</th>
                            <th class="sa-th">Preço</th>
                            <th class="sa-th hide-mobile">Status</th>
                            <th class="sa-th" style="text-align:right;width:80px">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($servicos as $servico)
                        <tr class="sa-tr">
                            <td class="sa-td">
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div style="width:36px;height:36px;border-radius:10px;background:{{ $servico->cor }}20;border:1.5px solid {{ $servico->cor }}40;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="{{ $servico->cor }}" stroke-width="2"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/></svg>
                                    </div>
                                    <div>
                                        <div style="font-size:14px;font-weight:600">{{ $servico->nome }}</div>
                                        @if($servico->descricao)
                                        <div style="font-size:12px;color:var(--sa-text3);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $servico->descricao }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="sa-td hide-mobile" style="color:var(--sa-text2)">{{ $servico->categoria ?? '—' }}</td>
                            <td class="sa-td hide-mobile" style="color:var(--sa-text2)">{{ $servico->duracaoFormatada() }}</td>
                            <td class="sa-td" style="font-weight:600">{{ $servico->precoFormatado() }}</td>
                            <td class="sa-td hide-mobile">
                                <x-sa.badge :status="$servico->ativo ? 'ativo' : 'inativo'" :label="$servico->ativo ? 'Ativo' : 'Inativo'" />
                            </td>
                            <td class="sa-td" style="text-align:right">
                                <div style="display:inline-flex;gap:4px">
                                    @can('update', $servico)
                                    <x-sa.icon-btn href="{{ route('servicos.edit', $servico) }}" title="Editar">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </x-sa.icon-btn>
                                    @endcan
                                    @can('delete', $servico)
                                    <form method="POST" action="{{ route('servicos.destroy', $servico) }}" onsubmit="return confirmDelete(event, '{{ $servico->nome }}')">
                                        @csrf @method('DELETE')
                                        <x-sa.icon-btn type="submit" title="Excluir" :danger="true">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                                        </x-sa.icon-btn>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" style="padding:48px 0;text-align:center;color:var(--sa-text3);font-size:14px">
                                Nenhum serviço cadastrado.
                                @can('create', \App\Models\Servico::class)
                                <a href="{{ route('servicos.create') }}" style="color:var(--sa-secondary);font-weight:600;text-decoration:none"> Cadastrar o primeiro</a>
                                @endcan
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($servicos->hasPages())
            <div style="padding:12px 16px;border-top:1px solid var(--sa-border);background:var(--sa-surface2)">
                {{ $servicos->links() }}
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
    Swal.fire({ title: 'Excluir serviço?', text: `"${nome}" será removido.`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim, excluir', cancelButtonText: 'Cancelar', confirmButtonColor: '#e53e3e' })
        .then(r => { if (r.isConfirmed) form.submit(); });
    return false;
}
</script>
@endpush
@endsection
