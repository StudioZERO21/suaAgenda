@extends('layouts.app')
@section('title', 'Agenda')

@section('content')
<x-sa.page>
    <x-sa.app-header
        title="Agenda"
        :subtitle="$agendamentos->total() . ' agendamento' . ($agendamentos->total() !== 1 ? 's' : '')">
        @can('create', \App\Models\Agendamento::class)
        <x-slot:actions>
            <x-sa.btn href="{{ route('agendamentos.create') }}" :icon="view('components.sa.icons.plus')->render()">
                Novo Agendamento
            </x-sa.btn>
        </x-slot:actions>
        @endcan
    </x-sa.app-header>

    <x-sa.body>
        <x-sa.card padding="14px 20px" style="margin-bottom:16px">
            <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:var(--sa-text3);margin-bottom:4px;text-transform:uppercase;letter-spacing:.5px">Cliente</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nome..."
                           class="sa-search-input" style="padding:8px 12px;min-width:180px"
                           onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:var(--sa-text3);margin-bottom:4px;text-transform:uppercase;letter-spacing:.5px">Data</label>
                    <input type="date" name="data" value="{{ request('data') }}" class="sa-search-input" style="padding:8px 12px;width:auto">
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:var(--sa-text3);margin-bottom:4px;text-transform:uppercase;letter-spacing:.5px">Status</label>
                    <select name="status" class="sa-search-input" style="padding:8px 12px;width:auto;cursor:pointer">
                        <option value="">Todos (exceto cancelados)</option>
                        @foreach(['pendente','confirmado','finalizado','cancelado'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:var(--sa-text3);margin-bottom:4px;text-transform:uppercase;letter-spacing:.5px">Profissional</label>
                    <select name="profissional_id" class="sa-search-input" style="padding:8px 12px;width:auto;cursor:pointer">
                        <option value="">Todos</option>
                        @foreach($profissionais as $prof)
                        <option value="{{ $prof->id }}" {{ request('profissional_id') == $prof->id ? 'selected' : '' }}>{{ $prof->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:var(--sa-text3);margin-bottom:4px;text-transform:uppercase;letter-spacing:.5px">Serviço</label>
                    <select name="servico_id" class="sa-search-input" style="padding:8px 12px;width:auto;cursor:pointer">
                        <option value="">Todos</option>
                        @foreach($servicos as $svc)
                        <option value="{{ $svc->id }}" {{ request('servico_id') == $svc->id ? 'selected' : '' }}>{{ $svc->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;gap:8px">
                    <x-sa.btn type="submit" size="sm">Filtrar</x-sa.btn>
                    @if(request()->hasAny(['data','status','profissional_id','servico_id','q']))
                    <x-sa.btn href="{{ route('agendamentos.index') }}" variant="secondary" size="sm">Limpar</x-sa.btn>
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
                            <th class="sa-th hide-mobile">Data / Hora</th>
                            <th class="sa-th hide-mobile">Profissional</th>
                            <th class="sa-th hide-mobile">Serviço</th>
                            <th class="sa-th">Status</th>
                            <th class="sa-th" style="text-align:right;width:80px">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($agendamentos as $ag)
                        <tr class="sa-tr">
                            <td class="sa-td">
                                <div style="display:flex;align-items:center;gap:10px">
                                    <x-sa.avatar :name="$ag->cliente->name ?? '?'" :size="32" />
                                    <div>
                                        <div style="font-size:14px;font-weight:600">{{ $ag->cliente->name ?? '—' }}</div>
                                        <div style="font-size:12px;color:var(--sa-text3)">{{ $ag->data_hora->format('d/m H:i') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="sa-td hide-mobile">
                                <div style="font-weight:600">{{ $ag->data_hora->format('d/m/Y') }}</div>
                                <div style="font-size:12px;color:var(--sa-text3)">{{ $ag->data_hora->format('H:i') }} • {{ $ag->duracao }}min</div>
                            </td>
                            <td class="sa-td hide-mobile" style="color:var(--sa-text2)">{{ $ag->profissional->name ?? '—' }}</td>
                            <td class="sa-td hide-mobile">
                                @if($ag->servico)
                                <div style="display:flex;align-items:center;gap:6px">
                                    <div style="width:8px;height:8px;border-radius:50%;background:{{ $ag->servico->cor }}"></div>
                                    <span style="font-size:13px;color:var(--sa-text2)">{{ $ag->servico->nome }}</span>
                                </div>
                                @else — @endif
                            </td>
                            <td class="sa-td">
                                <x-sa.badge :status="$ag->status" :label="ucfirst($ag->status)" />
                            </td>
                            <td class="sa-td" style="text-align:right">
                                <div style="display:inline-flex;gap:4px">
                                    <x-sa.icon-btn href="{{ route('agendamentos.show', $ag) }}" title="Ver">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </x-sa.icon-btn>
                                    @can('update', $ag)
                                    <x-sa.icon-btn href="{{ route('agendamentos.edit', $ag) }}" title="Editar">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </x-sa.icon-btn>
                                    @endcan
                                    @can('delete', $ag)
                                    <form method="POST" action="{{ route('agendamentos.destroy', $ag) }}" onsubmit="return confirmDelete(event)">
                                        @csrf @method('DELETE')
                                        <x-sa.icon-btn type="submit" title="Cancelar" :danger="true">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </x-sa.icon-btn>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" style="padding:48px 0;text-align:center;color:var(--sa-text3);font-size:14px">
                                Nenhum agendamento encontrado.
                                @can('create', \App\Models\Agendamento::class)
                                <a href="{{ route('agendamentos.create') }}" style="color:var(--sa-secondary);font-weight:600;text-decoration:none"> Criar agendamento</a>
                                @endcan
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($agendamentos->hasPages())
            <div style="padding:12px 16px;border-top:1px solid var(--sa-border);background:var(--sa-surface2)">
                {{ $agendamentos->links() }}
            </div>
            @endif
        </x-sa.card>
    </x-sa.body>
</x-sa.page>

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
