@extends('layouts.app')
@section('title', 'Serviços')

@php
    use App\Support\SaServiceIcons;
    use Illuminate\Support\Facades\Storage;
    $iconPaths = SaServiceIcons::paths();
    $iconUrls = SaServiceIcons::urls();
@endphp

@push('styles')
<style>
    .sa-svc-modal {
        background: var(--sa-surface); border-radius: 16px; width: 100%; max-width: 560px;
        max-height: 92vh; display: flex; flex-direction: column;
        box-shadow: 0 24px 64px rgba(0,0,0,.2); animation: sa-modal-in 250ms ease;
    }
    .sa-svc-colors { display: flex; gap: 8px; flex-wrap: wrap; }
    .sa-svc-icon-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(76px, 1fr)); gap: 8px; }
    .sa-svc-icon-btn {
        width: 100%; border-radius: 8px; border: 1.5px solid var(--sa-border);
        background: var(--sa-surface); cursor: pointer; display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: 5px; padding: 8px 4px;
        transition: border-color 150ms, background 150ms; min-height: 68px;
    }
    .sa-svc-icon-label {
        font-size: 9px; font-weight: 600; color: var(--sa-text3); text-align: center;
        line-height: 1.25; max-width: 100%; word-break: break-word;
    }
    .sa-svc-icon-btn.is-selected { border-color: var(--sa-primary); background: var(--sa-surface2); }
    .sa-svc-icon-btn:hover { border-color: var(--sa-secondary); }
    .sa-svc-prof-chips > template { display: contents; }
    .sa-svc-toggle {
        position: relative; width: 42px; height: 24px; border-radius: 12px; border: none; cursor: pointer;
        transition: background 200ms; padding: 0; flex-shrink: 0;
    }
    .sa-svc-toggle-knob {
        position: absolute; top: 3px; width: 18px; height: 18px; border-radius: 50%; background: #fff;
        transition: left 200ms; box-shadow: 0 1px 4px rgba(0,0,0,.2);
    }
    .sa-svc-prof-photo {
        width: 24px; height: 24px; border-radius: 50%; overflow: hidden; flex-shrink: 0;
        border: 1.5px solid var(--sa-border); position: relative; background: var(--sa-surface2);
    }
    .sa-svc-prof-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .sa-svc-prof-photo--chip { width: 22px; height: 22px; border-width: 1px; }
</style>
@endpush

@section('content')
<x-sa.page x-data="servicesApp()" x-init="init()">
    <x-sa.app-header title="Serviços" subtitle="Gerencie os serviços oferecidos">
        @can('create', \App\Models\Servico::class)
        <x-slot:actions>
            <x-sa.btn type="button" @click="openCreateModal()" :icon="view('components.sa.icons.plus')->render()">
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
                            <th class="sa-th">Preço</th>
                            <th class="sa-th hide-mobile">Duração</th>
                            <th class="sa-th hide-mobile">Profissionais</th>
                            <th class="sa-th hide-mobile">Status</th>
                            <th class="sa-th" style="text-align:right;width:140px">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($servicos as $servico)
                        @php $icone = SaServiceIcons::normalize($servico->icone ?? SaServiceIcons::DEFAULT); @endphp
                        <tr class="sa-tr">
                            <td class="sa-td">
                                <div style="display:flex;align-items:center;gap:12px">
                                    <div style="width:36px;height:36px;border-radius:9px;background:color-mix(in srgb,{{ $servico->cor }} 10%,transparent);border:1px solid color-mix(in srgb,{{ $servico->cor }} 19%,transparent);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                        <x-sa.service-icon :name="$icone" :size="16" :color="$servico->cor" />
                                    </div>
                                    <div style="min-width:0">
                                        <div style="font-size:14px;font-weight:700;color:var(--sa-text1)">{{ $servico->nome }}</div>
                                        @if($servico->descricao)
                                        <div style="font-size:11px;color:var(--sa-text3);max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $servico->descricao }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="sa-td" style="font-weight:700;color:var(--sa-text1)">{{ $servico->precoFormatado() }}</td>
                            <td class="sa-td hide-mobile" style="color:var(--sa-text2)">{{ $servico->duracaoFormatada() }}</td>
                            <td class="sa-td hide-mobile">
                                <div style="display:flex;gap:4px;flex-wrap:wrap">
                                    @forelse($servico->profissionais as $prof)
                                    @if($prof->foto_path)
                                    <div class="sa-svc-prof-photo" title="{{ $prof->name }}">
                                        <img src="{{ Storage::url($prof->foto_path) }}" alt="{{ $prof->name }}">
                                    </div>
                                    @else
                                    <x-sa.avatar :name="$prof->name" :size="24" :color="$prof->cor ?? '#1a1a1a'" />
                                    @endif
                                    @empty
                                    <span style="font-size:12px;color:var(--sa-text3)">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="sa-td hide-mobile">
                                @can('update', $servico)
                                <button type="button" class="sa-svc-toggle" @click="toggleAtivo('{{ $servico->id }}', {{ $servico->ativo ? 'true' : 'false' }})"
                                        :style="'background:' + (rowAtivo('{{ $servico->id }}', {{ $servico->ativo ? 'true' : 'false' }}) ? 'var(--sa-primary)' : 'var(--sa-border)')">
                                    <div class="sa-svc-toggle-knob" :style="'left:' + (rowAtivo('{{ $servico->id }}', {{ $servico->ativo ? 'true' : 'false' }}) ? '20px' : '3px')"></div>
                                </button>
                                @else
                                <x-sa.badge :status="$servico->ativo ? 'ativo' : 'inativo'" :label="$servico->ativo ? 'Ativo' : 'Inativo'" />
                                @endcan
                            </td>
                            <td class="sa-td" style="text-align:right">
                                <div style="display:inline-flex;gap:6px;align-items:center">
                                    @can('update', $servico)
                                    <button type="button" @click="openModal('{{ $servico->id }}')"
                                            style="display:inline-flex;align-items:center;gap:6px;padding:7px 12px;border-radius:8px;border:1px solid var(--sa-border);background:var(--sa-surface2);color:var(--sa-text2);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--sa-font-body);transition:border-color 180ms,color 180ms"
                                            onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                                            onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        Editar
                                    </button>
                                    @endcan
                                    @can('delete', $servico)
                                    <form method="POST" action="{{ route('servicos.destroy', $servico) }}" onsubmit="return confirmDelete(event, '{{ addslashes($servico->nome) }}')" style="margin:0">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Excluir"
                                                style="width:34px;height:34px;border-radius:8px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--sa-text3);transition:all 150ms"
                                                onmouseover="this.style.borderColor='#ef4444';this.style.color='#ef4444'"
                                                onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                                        </button>
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
                                <button type="button" @click="openCreateModal()" style="color:var(--sa-secondary);font-weight:600;background:none;border:none;cursor:pointer;text-decoration:underline;font-size:14px"> Cadastrar o primeiro</button>
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

    {{-- Modal create/edit --}}
    <div x-show="modalOpen" x-cloak @keydown.escape.window="closeModal()" class="sa-modal-overlay" @click.self="closeModal()">
        <div class="sa-svc-modal" @click.stop x-show="modalOpen">
            <div style="padding:24px 28px 0;display:flex;justify-content:space-between;align-items:flex-start;flex-shrink:0">
                <div>
                    <h3 style="font-family:var(--sa-font-heading);font-size:18px;font-weight:600;color:var(--sa-text1);margin:0"
                        x-text="form.id ? 'Editar Serviço' : 'Novo Serviço'"></h3>
                    <p style="font-size:13px;color:var(--sa-text3);margin:4px 0 0">Configure o serviço oferecido</p>
                </div>
                <button type="button" @click="closeModal()" style="background:none;border:none;cursor:pointer;color:var(--sa-text3);padding:4px;display:flex;border-radius:6px">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div style="padding:20px 28px;overflow-y:auto;flex:1;display:flex;flex-direction:column;gap:14px">
                {{-- Preview --}}
                <div style="display:flex;align-items:center;gap:14px;padding:14px;background:var(--sa-surface2);border-radius:12px;border:1px solid var(--sa-border)">
                    <div style="width:52px;height:52px;border-radius:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0"
                         :style="'background:color-mix(in srgb,' + form.cor + ' 12%,transparent);border:2px solid color-mix(in srgb,' + form.cor + ' 25%,transparent)'">
                        <span x-html="iconSvg(form.icone, form.cor, 22)"></span>
                    </div>
                    <div>
                        <div style="font-family:var(--sa-font-heading);font-size:17px;font-weight:700;color:var(--sa-text1)" x-text="form.nome || 'Nome do serviço'"></div>
                        <div style="font-size:13px;color:var(--sa-text3)" x-text="duracaoLabel(form.duracao_minutos) + ' · ' + precoLabel(form.preco)"></div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Nome do serviço <span style="color:var(--sa-secondary)">*</span></label>
                        <input type="text" x-model="form.nome" placeholder="Ex: Corte degradê"
                               style="width:100%;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:var(--sa-font-body);color:var(--sa-text1);background:var(--sa-surface);outline:none;box-sizing:border-box"
                               onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'"
                               onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Preço <span style="color:var(--sa-secondary)">*</span></label>
                        <div style="position:relative">
                            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:13px;color:var(--sa-text3);pointer-events:none">R$</span>
                            <input type="number" x-model.number="form.preco" min="0" step="5"
                                   style="width:100%;padding:10px 12px 10px 36px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:var(--sa-font-body);color:var(--sa-text1);background:var(--sa-surface);outline:none;box-sizing:border-box"
                                   onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'"
                                   onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                        </div>
                    </div>
                </div>

                {{-- Duração slider --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);margin-bottom:6px">
                        Duração: <span style="color:var(--sa-secondary);font-weight:800" x-text="duracaoLabel(form.duracao_minutos)"></span>
                    </label>
                    <input type="range" min="15" max="240" step="15" x-model.number="form.duracao_minutos"
                           style="width:100%;accent-color:var(--sa-primary);cursor:pointer">
                    <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--sa-text3);margin-top:2px">
                        <span>15min</span><span>1h</span><span>2h</span><span>4h</span>
                    </div>
                </div>

                {{-- Cor --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);margin-bottom:8px">Cor do serviço</label>
                    <div class="sa-svc-colors">
                        @foreach($palette as $corOpt)
                        <button type="button" @click="form.cor = '{{ $corOpt }}'"
                                :style="'width:28px;height:28px;border-radius:50%;background:{{ $corOpt }};border:' + (form.cor === '{{ $corOpt }}' ? '3px solid var(--sa-text1)' : '2px solid transparent') + ';cursor:pointer'"></button>
                        @endforeach
                    </div>
                </div>

                {{-- Ícone (somente no cadastro/edição) --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);margin-bottom:4px">Ícone do serviço</label>
                    <p style="font-size:12px;color:var(--sa-text3);margin:0 0 10px;line-height:1.5">
                        Selecione o segmento da sua profissão e escolha o ícone abaixo.
                    </p>

                    <label style="display:block;font-size:12px;font-weight:600;color:var(--sa-text2);margin-bottom:5px">Segmento profissional</label>
                    <select x-model="iconSegment"
                            style="width:100%;padding:10px 32px 10px 13px;font-size:14px;border:1.5px solid var(--sa-border);border-radius:8px;background:var(--sa-surface);color:var(--sa-text1);cursor:pointer;font-family:'Inter',sans-serif;appearance:none;background-image:url(&quot;data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'/%3e%3c/svg%3e&quot;);background-repeat:no-repeat;background-position:right 10px center;background-size:14px;outline:none;box-sizing:border-box"
                            onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'"
                            onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                        <option value="">Selecione o segmento...</option>
                        <template x-for="cat in iconCatalog" :key="cat.id">
                            <option :value="cat.id" x-text="cat.label"></option>
                        </template>
                    </select>

                    <div x-show="iconSegment" x-cloak style="margin-top:14px;padding:14px;border-radius:10px;border:1px solid var(--sa-border);background:var(--sa-surface2)">
                        <p style="font-size:12px;color:var(--sa-text3);margin:0 0 10px;line-height:1.5" x-text="selectedSegmentDescription"></p>
                        <div class="sa-svc-icon-grid">
                            <template x-for="ico in selectedSegmentIcons" :key="ico.key">
                                <button type="button" class="sa-svc-icon-btn"
                                        @click="form.icone = ico.key"
                                        :class="form.icone === ico.key ? 'is-selected' : ''"
                                        :title="ico.label">
                                    <span x-html="iconSvg(ico.key, 'var(--sa-text1)', 18)"></span>
                                    <span class="sa-svc-icon-label" x-text="ico.label"></span>
                                </button>
                            </template>
                        </div>
                        <p x-show="form.icone" style="font-size:12px;color:var(--sa-text2);margin:10px 0 0;display:flex;align-items:center;gap:6px">
                            <span style="color:var(--sa-text3)">Selecionado:</span>
                            <span x-html="iconSvg(form.icone, form.cor, 14)"></span>
                            <span style="font-weight:600" x-text="selectedIconLabel"></span>
                        </p>
                    </div>
                </div>

                {{-- Profissionais --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);margin-bottom:8px">Profissionais que realizam</label>
                    <div class="sa-svc-prof-chips" style="display:flex;gap:8px;flex-wrap:wrap">
                        <template x-for="prof in profissionais" :key="prof.id">
                            <button type="button" @click="toggleProf(prof.id)"
                                    :style="'display:flex;align-items:center;gap:8px;padding:8px 14px;border-radius:10px;border:1.5px solid ' + (form.profissionais.includes(prof.id) ? prof.cor : 'var(--sa-border)') + ';background:' + (form.profissionais.includes(prof.id) ? 'color-mix(in srgb,' + prof.cor + ' 7%,transparent)' : 'var(--sa-surface)') + ';cursor:pointer;font-family:var(--sa-font-body)'">
                                <template x-if="prof.foto_url">
                                    <div class="sa-svc-prof-photo sa-svc-prof-photo--chip">
                                        <img :src="prof.foto_url" :alt="prof.name">
                                    </div>
                                </template>
                                <template x-if="!prof.foto_url">
                                    <span style="width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0"
                                          :style="'background:' + prof.cor" x-text="profInitials(prof.name)"></span>
                                </template>
                                <span style="font-size:12px;font-weight:600" :style="'color:' + (form.profissionais.includes(prof.id) ? prof.cor : 'var(--sa-text2)')" x-text="prof.name.split(' ')[0]"></span>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Descrição --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Descrição</label>
                    <textarea x-model="form.descricao" rows="2" placeholder="Descreva o serviço..."
                              style="width:100%;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:var(--sa-font-body);color:var(--sa-text1);background:var(--sa-surface);outline:none;resize:vertical;box-sizing:border-box"
                              onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'"
                              onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'"></textarea>
                </div>

                {{-- Ativo toggle --}}
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:var(--sa-surface2);border-radius:10px;border:1px solid var(--sa-border)">
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--sa-text1)">Serviço ativo</div>
                        <div style="font-size:12px;color:var(--sa-text3)">Aparece para agendamento online e no sistema</div>
                    </div>
                    <button type="button" class="sa-svc-toggle" @click="form.ativo = !form.ativo"
                            :style="'background:' + (form.ativo ? 'var(--sa-primary)' : 'var(--sa-border)')">
                        <div class="sa-svc-toggle-knob" :style="'left:' + (form.ativo ? '20px' : '3px')"></div>
                    </button>
                </div>
            </div>

            <div style="padding:16px 28px 24px;border-top:1px solid var(--sa-border);display:flex;gap:10px;justify-content:flex-end;flex-shrink:0">
                <button type="button" @click="closeModal()"
                        style="padding:9px 18px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;cursor:pointer;font-family:var(--sa-font-body)"
                        onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                        onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                    Cancelar
                </button>
                <button type="button" @click="saveModal()" :disabled="saving"
                        style="padding:9px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:var(--sa-font-body);background:var(--sa-primary);color:#fff;display:inline-flex;align-items:center;gap:7px;transition:filter 200ms"
                        onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                    <svg x-show="!saving" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    <span x-text="saving ? 'Salvando...' : (form.id ? 'Salvar' : 'Criar Serviço')"></span>
                </button>
            </div>
        </div>
    </div>
</x-sa.page>

@push('scripts')
<script>
const ICON_PATHS = @json($iconPaths);
const ICON_URLS = @json($iconUrls);
const ICON_CATALOG = @json(SaServiceIcons::catalogForJs());

function servicesApp() {
    const servicos = @json($servicosJson);
    const profissionais = @json($profissionaisJson);
    const openNovo = @json(request()->boolean('novo'));
    const editarId = @json(request('editar'));

    const blank = () => ({
        id: null, nome: '', descricao: '', duracao_minutos: 30, preco: 0,
        cor: '#1a1a1a', icone: 'servico_generico', ativo: true, profissionais: [],
    });

    const resolveIconSegment = (iconeKey) => {
        for (const cat of ICON_CATALOG) {
            if (cat.icons.some(i => i.key === iconeKey)) return cat.id;
        }
        return '';
    };

    return {
        modalOpen: false,
        saving: false,
        form: blank(),
        servicos,
        profissionais,
        ativoMap: {},
        iconCatalog: ICON_CATALOG,
        iconSegment: '',

        get selectedSegment() {
            return this.iconCatalog.find(c => c.id === this.iconSegment);
        },

        get selectedSegmentIcons() {
            return this.selectedSegment?.icons ?? [];
        },

        get selectedSegmentDescription() {
            return this.selectedSegment?.description ?? '';
        },

        get selectedIconLabel() {
            for (const cat of this.iconCatalog) {
                const found = cat.icons.find(i => i.key === this.form.icone);
                if (found) return found.label;
            }
            return '';
        },

        init() {
            servicos.forEach(s => { this.ativoMap[s.id] = s.ativo; });
            if (openNovo) this.openCreateModal();
            if (editarId) this.openModal(editarId);
        },

        rowAtivo(id, fallback) {
            return this.ativoMap[id] !== undefined ? this.ativoMap[id] : fallback;
        },

        iconSvg(name, color, size) {
            if (ICON_URLS[name]) {
                return `<img src="${ICON_URLS[name]}" width="${size}" height="${size}" alt="" style="display:block;object-fit:contain">`;
            }
            const path = ICON_PATHS[name] || ICON_PATHS.servico_generico;
            return `<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="${color}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${path}</svg>`;
        },

        duracaoLabel(min) {
            min = Number(min) || 0;
            if (min >= 60) {
                const h = Math.floor(min / 60);
                const m = min % 60;
                return m ? `${h}h ${m}min` : `${h}h`;
            }
            return `${min}min`;
        },

        precoLabel(val) {
            const n = Number(val) || 0;
            return 'R$ ' + n.toFixed(2).replace('.', ',');
        },

        profInitials(name) {
            return (name || '?').substring(0, 2).toUpperCase();
        },

        toggleProf(id) {
            if (this.form.profissionais.includes(id)) {
                this.form.profissionais = this.form.profissionais.filter(x => x !== id);
            } else {
                this.form.profissionais = [...this.form.profissionais, id];
            }
        },

        openCreateModal() {
            this.form = blank();
            this.iconSegment = '';
            this.modalOpen = true;
        },

        openModal(id) {
            const svc = this.servicos.find(s => s.id === id);
            if (!svc) return;
            this.form = { ...blank(), ...svc, profissionais: [...(svc.profissionais || [])] };
            this.iconSegment = resolveIconSegment(this.form.icone);
            this.modalOpen = true;
        },

        closeModal() {
            this.modalOpen = false;
        },

        async toggleAtivo(id, current) {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            try {
                const r = await fetch(`{{ url('servicos') }}/${id}/toggle`, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                if (!r.ok) throw new Error('Erro ao alterar status');
                const data = await r.json();
                this.ativoMap[id] = data.ativo;
                const idx = this.servicos.findIndex(s => s.id === id);
                if (idx >= 0) this.servicos[idx].ativo = data.ativo;
            } catch {
                this.ativoMap[id] = current;
                Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Erro ao alterar status', showConfirmButton: false, timer: 2000 });
            }
        },

        payload() {
            return {
                nome: this.form.nome,
                descricao: this.form.descricao || null,
                duracao_minutos: this.form.duracao_minutos,
                preco: this.form.preco,
                cor: this.form.cor,
                icone: this.form.icone,
                ativo: this.form.ativo,
                profissionais: this.form.profissionais,
            };
        },

        async saveModal() {
            if (!this.form.nome.trim() || !(Number(this.form.preco) > 0)) {
                return Swal.fire({ title: 'Atenção', text: 'Preencha nome e preço.', icon: 'warning', confirmButtonColor: '#1a1a1a' });
            }
            this.saving = true;
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            try {
                let res;
                if (this.form.id) {
                    res = await fetch(`{{ url('servicos') }}/${this.form.id}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify(this.payload()),
                    });
                } else {
                    res = await fetch(`{{ url('servicos') }}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify(this.payload()),
                    });
                }
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
    Swal.fire({ title: 'Excluir serviço?', text: `"${nome}" será removido.`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim, excluir', cancelButtonText: 'Cancelar', confirmButtonColor: '#e53e3e' })
        .then(r => { if (r.isConfirmed) form.submit(); });
    return false;
}
</script>
@endpush
@endsection
