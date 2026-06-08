@extends('layouts.app')
@section('title', 'Funcionários')

@php
    // Paleta determinística para a faixa de cor / avatar de cada profissional.
    // Mantém consistência visual entre recarregamentos sem depender de coluna no banco.
    $palette = ['#1a1a1a', '#d4a574', '#6366f1', '#10b981', '#f59e0b', '#ec4899', '#ef4444', '#0ea5e9', '#8b5cf6', '#14b8a6'];
    $colorFor = fn (string $key) => $palette[crc32($key) % count($palette)];
@endphp

@section('content')
<x-sa.page x-data="{ view: localStorage.getItem('sa_staff_view') || 'cards' }">
    <x-sa.app-header
        title="Funcionários"
        :subtitle="'Gerencie sua equipe e comissões · ' . $stats['total'] . ' cadastrado' . ($stats['total'] !== 1 ? 's' : '')">
        @can('create', \App\Models\Profissional::class)
        <x-slot:actions>
            <x-sa.btn href="{{ route('profissionais.create') }}" :icon="view('components.sa.icons.plus')->render()">
                Novo Funcionário
            </x-sa.btn>
        </x-slot:actions>
        @endcan
    </x-sa.app-header>

    <x-sa.body>
        {{-- Stat cards --}}
        <div class="sa-grid-4" style="margin-bottom:20px">
            <div class="sa-tint-card" style="--tint:var(--sa-primary)">
                <div class="sa-tint-card__label">Total de funcionários</div>
                <div class="sa-tint-card__value">{{ $stats['total'] }}</div>
                <div class="sa-tint-card__icon"><svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg></div>
            </div>
            <div class="sa-tint-card" style="--tint:#10b981">
                <div class="sa-tint-card__label">Ativos</div>
                <div class="sa-tint-card__value">{{ $stats['ativos'] }}</div>
                <div class="sa-tint-card__icon"><svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="1.5"><polyline points="20 6 9 17 4 12"/></svg></div>
            </div>
            <div class="sa-tint-card" style="--tint:#f59e0b">
                <div class="sa-tint-card__label">Inativos</div>
                <div class="sa-tint-card__value">{{ $stats['inativos'] }}</div>
                <div class="sa-tint-card__icon"><svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
            </div>
            <div class="sa-tint-card" style="--tint:var(--sa-secondary)">
                <div class="sa-tint-card__label">Comissão média</div>
                <div class="sa-tint-card__value">{{ number_format($stats['comissao_media'], 0) }}%</div>
                <div class="sa-tint-card__icon"><svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="1.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
            </div>
        </div>

        {{-- Filter bar --}}
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:20px;flex-wrap:wrap">
            <form method="GET" style="position:relative;flex:1;max-width:300px;margin:0">
                <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--sa-text3);pointer-events:none;display:flex">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nome ou cargo..." class="sa-search-input">
            </form>

            {{-- View toggle (cards | tabela) --}}
            <div style="margin-left:auto;display:flex;border:1px solid var(--sa-border);border-radius:8px;overflow:hidden">
                <button type="button" @click="view='cards';localStorage.setItem('sa_staff_view','cards')"
                        :style="view==='cards' ? 'background:var(--sa-primary);color:#fff' : 'background:var(--sa-surface);color:var(--sa-text2)'"
                        style="padding:8px 12px;border:none;border-right:1px solid var(--sa-border);cursor:pointer;display:flex;align-items:center" title="Cards">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                </button>
                <button type="button" @click="view='table';localStorage.setItem('sa_staff_view','table')"
                        :style="view==='table' ? 'background:var(--sa-primary);color:#fff' : 'background:var(--sa-surface);color:var(--sa-text2)'"
                        style="padding:8px 12px;border:none;cursor:pointer;display:flex;align-items:center" title="Tabela">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
            </div>
        </div>

        @if($profissionais->isEmpty())
            <div style="text-align:center;padding:60px;color:var(--sa-text3);font-size:14px">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 16px;display:block;opacity:.3"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                Nenhum funcionário encontrado.
                @can('create', \App\Models\Profissional::class)
                <a href="{{ route('profissionais.create') }}" style="color:var(--sa-secondary);font-weight:600;text-decoration:none"> Cadastrar o primeiro</a>
                @endcan
            </div>
        @else
            {{-- ── CARDS VIEW ─────────────────────────────────────────── --}}
            <div x-show="view==='cards'" class="sa-grid-3">
                @foreach($profissionais as $prof)
                    @php $cor = $colorFor($prof->name); @endphp
                    <div class="sa-staff-card">
                        <div style="height:5px;background:{{ $cor }}"></div>
                        <div style="padding:20px 20px 0">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px">
                                <div style="display:flex;gap:12px;align-items:center">
                                    <x-sa.avatar :name="$prof->name" :size="52" :color="$cor" />
                                    <div>
                                        <a href="{{ route('profissionais.show', $prof) }}" style="font-family:var(--sa-font-heading,'Poppins',sans-serif);font-size:16px;font-weight:700;color:var(--sa-text1);text-decoration:none">{{ $prof->name }}</a>
                                        <div style="font-size:12px;color:var(--sa-text3);margin-top:2px">{{ $prof->especialidade ?? 'Funcionário' }}</div>
                                    </div>
                                </div>
                                <x-sa.badge :status="$prof->ativo ? 'ativo' : 'inativo'" :label="$prof->ativo ? 'Ativo' : 'Inativo'" />
                            </div>

                            {{-- Stats row --}}
                            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:16px;padding:10px;background:var(--sa-surface);border-radius:10px;border:1px solid var(--sa-border)">
                                <div style="text-align:center">
                                    <div style="font-family:var(--sa-font-heading,'Poppins',sans-serif);font-size:18px;font-weight:800;color:var(--sa-text1)">{{ $prof->agendamentos_count }}</div>
                                    <div style="font-size:10px;color:var(--sa-text3);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:2px">Agendamentos</div>
                                </div>
                                <div style="text-align:center">
                                    <div style="font-family:var(--sa-font-heading,'Poppins',sans-serif);font-size:18px;font-weight:800;color:var(--sa-text1)">{{ $prof->comissao_pct !== null ? number_format((float) $prof->comissao_pct, 0) . '%' : '—' }}</div>
                                    <div style="font-size:10px;color:var(--sa-text3);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:2px">Comissão</div>
                                </div>
                            </div>

                            @if($prof->especialidade)
                            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:16px">
                                <span style="font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;background:{{ $cor }}15;color:{{ $cor }};border:1px solid {{ $cor }}30">{{ $prof->especialidade }}</span>
                            </div>
                            @endif
                        </div>

                        <div style="padding:10px 14px;border-top:1px solid var(--sa-border);display:flex;gap:8px">
                            @can('update', $prof)
                            <x-sa.btn href="{{ route('profissionais.edit', $prof) }}" variant="muted" size="sm" style="flex:1"
                                      :icon="'<svg width=\'13\' height=\'13\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><path d=\'M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7\'/><path d=\'M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z\'/></svg>'">
                                Editar
                            </x-sa.btn>
                            @endcan
                            <x-sa.btn href="{{ route('profissionais.show', $prof) }}" variant="ghost" size="sm"
                                      :icon="'<svg width=\'13\' height=\'13\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><path d=\'M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z\'/><circle cx=\'12\' cy=\'12\' r=\'3\'/></svg>'" />
                            @can('delete', $prof)
                            <form method="POST" action="{{ route('profissionais.destroy', $prof) }}" onsubmit="return confirmDelete(event, '{{ addslashes($prof->name) }}')" style="margin:0">
                                @csrf @method('DELETE')
                                <x-sa.btn type="submit" variant="ghost" size="sm"
                                          :icon="'<svg width=\'13\' height=\'13\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'#ef4444\' stroke-width=\'2\'><polyline points=\'3 6 5 6 21 6\'/><path d=\'M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6\'/></svg>'" />
                            </form>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ── TABLE VIEW ─────────────────────────────────────────── --}}
            <x-sa.card :flush="true" x-show="view==='table'" x-cloak>
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse">
                        <thead>
                            <tr style="background:var(--sa-surface2);border-bottom:1px solid var(--sa-border)">
                                <th class="sa-th">Funcionário</th>
                                <th class="sa-th hide-mobile">Cargo</th>
                                <th class="sa-th hide-mobile">Status</th>
                                <th class="sa-th hide-mobile">Comissão</th>
                                <th class="sa-th hide-mobile">Agendamentos</th>
                                <th class="sa-th" style="text-align:right;width:90px">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($profissionais as $prof)
                            @php $cor = $colorFor($prof->name); @endphp
                            <tr class="sa-tr">
                                <td class="sa-td">
                                    <div style="display:flex;align-items:center;gap:10px">
                                        <x-sa.avatar :name="$prof->name" :color="$cor" />
                                        <a href="{{ route('profissionais.show', $prof) }}" style="font-size:14px;font-weight:600;color:var(--sa-text1);text-decoration:none">{{ $prof->name }}</a>
                                    </div>
                                </td>
                                <td class="sa-td hide-mobile" style="color:var(--sa-text2)">{{ $prof->especialidade ?? '—' }}</td>
                                <td class="sa-td hide-mobile">
                                    <x-sa.badge :status="$prof->ativo ? 'ativo' : 'inativo'" :label="$prof->ativo ? 'Ativo' : 'Inativo'" />
                                </td>
                                <td class="sa-td hide-mobile" style="color:var(--sa-secondary);font-weight:600">{{ $prof->comissao_pct !== null ? number_format((float) $prof->comissao_pct, 0) . '%' : '—' }}</td>
                                <td class="sa-td hide-mobile">
                                    <span style="font-size:13px;font-weight:600;padding:2px 10px;border-radius:20px;background:rgba(26,26,26,.06);color:var(--sa-text2)">{{ $prof->agendamentos_count }}</span>
                                </td>
                                <td class="sa-td" style="text-align:right">
                                    <div style="display:inline-flex;gap:4px">
                                        <x-sa.icon-btn href="{{ route('profissionais.show', $prof) }}" title="Ver">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </x-sa.icon-btn>
                                        @can('update', $prof)
                                        <x-sa.icon-btn href="{{ route('profissionais.edit', $prof) }}" title="Editar">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </x-sa.icon-btn>
                                        @endcan
                                        @can('delete', $prof)
                                        <form method="POST" action="{{ route('profissionais.destroy', $prof) }}" onsubmit="return confirmDelete(event, '{{ addslashes($prof->name) }}')" style="margin:0">
                                            @csrf @method('DELETE')
                                            <x-sa.icon-btn type="submit" title="Excluir" :danger="true">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                                            </x-sa.icon-btn>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-sa.card>

            @if($profissionais->hasPages())
            <div style="margin-top:16px">
                {{ $profissionais->links() }}
            </div>
            @endif
        @endif
    </x-sa.body>
</x-sa.page>

@push('scripts')
<script>
function confirmDelete(e, nome) {
    e.preventDefault();
    const form = e.target;
    Swal.fire({ title: 'Excluir funcionário?', text: `"${nome}" será removido.`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim, excluir', cancelButtonText: 'Cancelar', confirmButtonColor: '#e53e3e' })
        .then(r => { if (r.isConfirmed) form.submit(); });
    return false;
}
</script>
@endpush
@endsection
