@extends('layouts.app')
@section('title', 'Funcionários')

@php
    $palette = ['#1a1a1a', '#d4a574', '#6366f1', '#10b981', '#f59e0b', '#ec4899', '#ef4444', '#0ea5e9', '#8b5cf6', '#14b8a6'];
    $colorFor = fn (string $key) => $palette[crc32($key) % count($palette)];
    $servicosJson = $servicos->map(fn ($s) => ['id' => $s->id, 'nome' => $s->nome, 'cor' => $s->cor])->values();
@endphp

@section('content')
<x-sa.page x-data="staffApp()">
    <x-sa.app-header
        title="Funcionários"
        :subtitle="'Gerencie sua equipe e comissões · ' . $stats['total'] . ' cadastrado' . ($stats['total'] !== 1 ? 's' : '')">
        <x-slot:actions>
            <x-sa.btn href="{{ route('profissionais.exportar') }}" variant="secondary"
                      :icon="'<svg width=\'13\' height=\'13\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><path d=\'M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4\'/><polyline points=\'7 10 12 15 17 10\'/><line x1=\'12\' y1=\'15\' x2=\'12\' y2=\'3\'/></svg>'">
                Exportar CSV
            </x-sa.btn>
            @can('create', \App\Models\Profissional::class)
            <x-sa.btn href="{{ route('profissionais.create') }}" :icon="view('components.sa.icons.plus')->render()">
                Novo Funcionário
            </x-sa.btn>
            @endcan
        </x-slot:actions>
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
                                    @if($prof->foto_path)
                                    <div style="width:52px;height:52px;border-radius:50%;overflow:hidden;flex-shrink:0;border:2px solid var(--sa-border)">
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($prof->foto_path) }}" alt="{{ $prof->name }}" style="width:100%;height:100%;object-fit:cover">
                                    </div>
                                    @else
                                    <x-sa.avatar :name="$prof->name" :size="52" :color="$cor" />
                                    @endif
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

                        @php
                        $profData = ['id'=>$prof->id,'name'=>$prof->name,'especialidade'=>$prof->especialidade,'comissao_pct'=>(float)($prof->comissao_pct??0),'ativo'=>(bool)$prof->ativo,'cor'=>$prof->cor??'#1a1a1a','phone'=>$prof->phone??'','admissao'=>$prof->admissao?$prof->admissao->format('Y-m-d'):'','instagram'=>$prof->instagram??'','tiktok'=>$prof->tiktok??'','facebook'=>$prof->facebook??'','agendamentos_count'=>$prof->agendamentos_count,'servicos'=>$prof->servicos->pluck('id')->values()->all(),'foto_url'=>$prof->foto_path?\Illuminate\Support\Facades\Storage::url($prof->foto_path):null];
                        @endphp
                        <div style="padding:10px 14px;border-top:1px solid var(--sa-border);display:flex;gap:8px">
                            @can('update', $prof)
                            <button type="button"
                                    data-prof="{{ htmlspecialchars(json_encode($profData), ENT_QUOTES) }}"
                                    @click="openModal(JSON.parse($el.dataset.prof))"
                                    style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:7px 12px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:13px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;transition:border-color 180ms,color 180ms"
                                    onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                                    onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Editar
                            </button>
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
                                        @if($prof->foto_path)
                                        <div style="width:34px;height:34px;border-radius:50%;overflow:hidden;flex-shrink:0;border:1px solid var(--sa-border)">
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($prof->foto_path) }}" alt="{{ $prof->name }}" style="width:100%;height:100%;object-fit:cover">
                                        </div>
                                        @else
                                        <x-sa.avatar :name="$prof->name" :color="$cor" />
                                        @endif
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
                                        @php $profData2 = ['id'=>$prof->id,'name'=>$prof->name,'especialidade'=>$prof->especialidade,'comissao_pct'=>(float)($prof->comissao_pct??0),'ativo'=>(bool)$prof->ativo,'cor'=>$prof->cor??'#1a1a1a','phone'=>$prof->phone??'','admissao'=>$prof->admissao?$prof->admissao->format('Y-m-d'):'','instagram'=>$prof->instagram??'','tiktok'=>$prof->tiktok??'','facebook'=>$prof->facebook??'','agendamentos_count'=>$prof->agendamentos_count,'servicos'=>$prof->servicos->pluck('id')->values()->all(),'foto_url'=>$prof->foto_path?\Illuminate\Support\Facades\Storage::url($prof->foto_path):null]; @endphp
                                        <button type="button"
                                                data-prof="{{ htmlspecialchars(json_encode($profData2), ENT_QUOTES) }}"
                                                @click="openModal(JSON.parse($el.dataset.prof))"
                                                title="Editar"
                                                style="width:30px;height:30px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--sa-text3);transition:all 150ms"
                                                onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'"
                                                onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </button>
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

    {{-- ── EDIT MODAL ──────────────────────────────────────────── --}}
    <div x-show="modalOpen" x-cloak
         @keydown.escape.window="closeModal()"
         style="position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:1000;padding:20px"
         @click.self="closeModal()">
        <div style="background:var(--sa-surface);border-radius:16px;width:100%;max-width:820px;max-height:92vh;display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.2);animation:sa-modal-in 250ms ease">
            {{-- Header --}}
            <div style="padding:24px 28px 0;display:flex;justify-content:space-between;align-items:flex-start;flex-shrink:0">
                <div>
                    <h3 style="font-family:var(--sa-font-heading);font-size:18px;font-weight:600;color:var(--sa-text1);margin:0" x-text="'Editar — ' + (form.name || '')"></h3>
                    <p style="font-size:13px;color:var(--sa-text3);margin:4px 0 0">Atualize os dados do funcionário</p>
                </div>
                <button type="button" @click="closeModal()" style="background:none;border:none;cursor:pointer;color:var(--sa-text3);padding:4px;display:flex;border-radius:6px">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            {{-- Body --}}
            <div style="padding:20px 28px;overflow-y:auto;flex:1">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                    {{-- Left column --}}
                    <div style="display:flex;flex-direction:column;gap:14px">
                        {{-- Avatar + color picker --}}
                        <div style="padding:16px;background:var(--sa-surface2);border-radius:12px;border:1px solid var(--sa-border)">
                            <div style="display:flex;align-items:center;gap:16px">
                                <div style="position:relative;flex-shrink:0">
                                    <div style="width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;overflow:hidden;font-size:26px;font-weight:800;font-family:'Inter',sans-serif;color:#fff;flex-shrink:0"
                                         :style="'background:' + (form.foto_url ? 'transparent' : form.cor)">
                                        <img x-show="form.foto_url" :src="form.foto_url" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
                                        <span x-show="!form.foto_url" x-text="(form.name || '?').charAt(0).toUpperCase()"></span>
                                    </div>
                                    <button type="button" @click="$refs.fotoInput.click()" :disabled="uploadingFoto"
                                            style="position:absolute;bottom:-2px;right:-2px;width:22px;height:22px;border-radius:50%;background:var(--sa-secondary);border:2px solid var(--sa-surface);display:flex;align-items:center;justify-content:center;cursor:pointer">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                    <input type="file" x-ref="fotoInput" accept="image/*" style="display:none" @change="uploadFoto($event)">
                                </div>
                                <div style="flex:1">
                                    <div style="font-size:13px;font-weight:600;color:var(--sa-text1);margin-bottom:8px">Foto & Cor</div>
                                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:6px">
                                        <template x-for="c in profColors" :key="c">
                                            <button type="button" @click="form.cor = c"
                                                    :style="'width:22px;height:22px;border-radius:50%;background:' + c + ';border:' + (form.cor === c ? '3px solid var(--sa-text1)' : '2px solid transparent') + ';cursor:pointer;transition:border 150ms'"></button>
                                        </template>
                                    </div>
                                    <div style="display:flex;gap:6px;align-items:center">
                                        <span style="font-size:11px;color:var(--sa-text3)" x-text="uploadingFoto ? 'Enviando...' : (form.foto_url ? 'Clique no avatar para trocar' : 'Clique no avatar para enviar foto')"></span>
                                        <button x-show="form.foto_url" type="button" @click="removeFoto()"
                                                style="font-size:10px;color:#ef4444;background:none;border:none;cursor:pointer;padding:0;text-decoration:underline">remover</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Name --}}
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Nome completo <span style="color:var(--sa-secondary)">*</span></label>
                            <input type="text" x-model="form.name" required placeholder="Nome do funcionário"
                                   style="width:100%;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms;box-sizing:border-box"
                                   onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'"
                                   onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                        </div>

                        {{-- Especialidade + Status --}}
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                            <div>
                                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Especialidade</label>
                                <input type="text" x-model="form.especialidade" placeholder="Ex: Barbeiro"
                                       style="width:100%;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;box-sizing:border-box"
                                       onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                            </div>
                            <div>
                                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Status</label>
                                <select x-model="form.ativo"
                                        style="width:100%;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;cursor:pointer;appearance:none;box-sizing:border-box"
                                        onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                                    <option :value="true">Ativo</option>
                                    <option :value="false">Inativo</option>
                                </select>
                            </div>
                        </div>

                        {{-- Email + Phone --}}
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">WhatsApp</label>
                            <input type="text" x-model="form.phone" placeholder="(11) 99999-0000"
                                   style="width:100%;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;box-sizing:border-box"
                                   onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                        </div>
                    </div>

                    {{-- Right column --}}
                    <div style="display:flex;flex-direction:column;gap:14px">
                        {{-- Admission date --}}
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Data de admissão</label>
                            <input type="date" x-model="form.admissao"
                                   style="width:100%;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;box-sizing:border-box"
                                   onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                        </div>

                        {{-- Commission slider --}}
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
                                Comissão:
                                <span style="color:var(--sa-secondary);font-weight:800" x-text="form.comissao_pct + '%'"></span>
                            </label>
                            <input type="range" x-model.number="form.comissao_pct" min="0" max="70" step="1"
                                   style="width:100%;accent-color:var(--sa-primary);cursor:pointer">
                            <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--sa-text3);margin-top:3px">
                                <span>0%</span><span>35%</span><span>70%</span>
                            </div>
                        </div>

                        {{-- Specialties / Serviços --}}
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:8px">Serviços vinculados</label>
                            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:8px;min-height:28px">
                                <template x-if="form.servicos.length === 0">
                                    <span style="font-size:12px;color:var(--sa-text3);font-style:italic">Nenhum serviço vinculado</span>
                                </template>
                                <template x-for="sid in form.servicos" :key="sid">
                                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;padding:4px 10px;border-radius:20px;background:color-mix(in srgb,var(--sa-primary) 10%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 20%,transparent);color:var(--sa-primary)">
                                        <span x-text="servicoName(sid)"></span>
                                        <button type="button" @click="form.servicos = form.servicos.filter(x => x !== sid)" style="background:none;border:none;cursor:pointer;color:var(--sa-text3);line-height:1;padding:0;display:flex;align-items:center">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </button>
                                    </span>
                                </template>
                            </div>
                            <select @change="addServico($event)"
                                    style="width:100%;padding:8px 12px;font-size:13px;border:1.5px solid var(--sa-border);border-radius:8px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;cursor:pointer;appearance:none;box-sizing:border-box"
                                    onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                                <option value="">+ Adicionar serviço...</option>
                                <template x-for="s in availableServicos" :key="s.id">
                                    <option :value="s.id" x-text="s.nome"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Stats (read-only) --}}
                        <div style="background:var(--sa-surface2);border-radius:10px;padding:14px 16px;border:1px solid var(--sa-border)">
                            <div style="font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">Estatísticas</div>
                            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--sa-border)">
                                <span style="font-size:12px;color:var(--sa-text3)">Agendamentos</span>
                                <span style="font-size:13px;font-weight:700;color:var(--sa-text1)" x-text="form.agendamentos_count || 0"></span>
                            </div>
                            <div style="display:flex;justify-content:space-between;padding:6px 0">
                                <span style="font-size:12px;color:var(--sa-text3)">Admissão</span>
                                <span style="font-size:13px;font-weight:700;color:var(--sa-text1)" x-text="form.admissao || '—'"></span>
                            </div>
                        </div>

                        {{-- Social media --}}
                        <div style="background:var(--sa-surface2);border-radius:10px;padding:14px 16px;border:1px solid var(--sa-border)">
                            <div style="font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Redes Sociais</div>
                            <div style="display:flex;flex-direction:column;gap:10px">
                                <div>
                                    <label style="display:block;font-size:12px;font-weight:600;color:var(--sa-text2);margin-bottom:4px">Instagram</label>
                                    <input type="text" x-model="form.instagram" placeholder="@usuario"
                                           style="width:100%;padding:8px 12px;border:1px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;box-sizing:border-box"
                                           onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                                </div>
                                <div>
                                    <label style="display:block;font-size:12px;font-weight:600;color:var(--sa-text2);margin-bottom:4px">TikTok</label>
                                    <input type="text" x-model="form.tiktok" placeholder="@usuario"
                                           style="width:100%;padding:8px 12px;border:1px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;box-sizing:border-box"
                                           onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                                </div>
                                <div>
                                    <label style="display:block;font-size:12px;font-weight:600;color:var(--sa-text2);margin-bottom:4px">Facebook</label>
                                    <input type="text" x-model="form.facebook" placeholder="Nome da página"
                                           style="width:100%;padding:8px 12px;border:1px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;box-sizing:border-box"
                                           onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div style="padding:16px 28px 24px;border-top:1px solid var(--sa-border);display:flex;gap:10px;justify-content:flex-end;flex-shrink:0">
                <button type="button" @click="closeModal()"
                        style="padding:9px 18px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;transition:border-color 180ms,color 180ms"
                        onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                        onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                    Cancelar
                </button>
                <button type="button" @click="saveModal()" :disabled="saving"
                        style="padding:9px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:var(--sa-primary);color:#fff;display:inline-flex;align-items:center;gap:7px;transition:filter 200ms"
                        onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                    <template x-if="saving">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
                    </template>
                    <template x-if="!saving">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    </template>
                    Salvar alterações
                </button>
            </div>
        </div>
    </div>
</x-sa.page>

@push('scripts')
<script>
function staffApp() {
    const PROF_COLORS = ['#1a1a1a','#d4a574','#6366f1','#10b981','#f59e0b','#ec4899','#ef4444','#0ea5e9','#8b5cf6','#14b8a6'];
    const servicos = @json($servicosJson);

    const blank = () => ({
        id: null, name: '', especialidade: '', comissao_pct: 0, ativo: true,
        cor: '#1a1a1a', phone: '', admissao: '', instagram: '', tiktok: '', facebook: '',
        agendamentos_count: 0, servicos: [], foto_url: null,
    });

    return {
        view: localStorage.getItem('sa_staff_view') || 'cards',
        modalOpen: false,
        saving: false,
        uploadingFoto: false,
        form: blank(),
        profColors: PROF_COLORS,
        servicos,

        get availableServicos() {
            return this.servicos.filter(s => !this.form.servicos.includes(s.id));
        },

        servicoName(sid) {
            return this.servicos.find(s => s.id === sid)?.nome || sid;
        },

        addServico(event) {
            const val = event.target.value;
            if (val && !this.form.servicos.includes(val)) {
                this.form.servicos = [...this.form.servicos, val];
            }
            event.target.value = '';
        },

        openModal(prof) {
            this.form = { ...blank(), ...prof, servicos: Array.isArray(prof.servicos) ? [...prof.servicos] : [] };
            this.uploadingFoto = false;
            this.modalOpen = true;
        },

        async uploadFoto(event) {
            const file = event.target.files[0];
            if (!file || !this.form.id) return;
            this.uploadingFoto = true;
            const fd = new FormData;
            fd.append('foto', file);
            fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
            try {
                const r = await fetch(`/profissionais/${this.form.id}/foto`, { method: 'POST', body: fd });
                if (!r.ok) throw new Error('Upload falhou');
                const data = await r.json();
                this.form.foto_url = data.foto_url;
            } catch {
                Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Erro ao enviar foto', showConfirmButton: false, timer: 2000 });
            } finally {
                this.uploadingFoto = false;
                event.target.value = '';
            }
        },

        async removeFoto() {
            if (!this.form.id) return;
            const csrf = document.querySelector('meta[name=csrf-token]').content;
            await fetch(`/profissionais/${this.form.id}/foto`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf } });
            this.form.foto_url = null;
        },

        closeModal() {
            this.modalOpen = false;
        },

        async saveModal() {
            if (!this.form.name.trim()) {
                return Swal.fire({ title: 'Atenção', text: 'Nome obrigatório.', icon: 'warning', confirmButtonColor: '#1a1a1a' });
            }
            this.saving = true;
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                const res = await fetch('/profissionais/' + this.form.id, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-HTTP-Method-Override': 'PUT' },
                    body: JSON.stringify({
                        _method: 'PUT',
                        name: this.form.name,
                        especialidade: this.form.especialidade || null,
                        comissao_pct: this.form.comissao_pct,
                        ativo: this.form.ativo,
                        cor: this.form.cor || null,
                        phone: this.form.phone || null,
                        admissao: this.form.admissao || null,
                        instagram: this.form.instagram || null,
                        tiktok: this.form.tiktok || null,
                        facebook: this.form.facebook || null,
                        servicos: this.form.servicos,
                    }),
                });
                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    throw new Error(err.message || 'Erro ao salvar.');
                }
                this.closeModal();
                Swal.fire({ title: 'Salvo!', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                setTimeout(() => window.location.reload(), 1200);
            } catch (e) {
                Swal.fire({ title: 'Erro', text: e.message, icon: 'error', confirmButtonColor: '#1a1a1a' });
            } finally {
                this.saving = false;
            }
        },
    };
}

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
