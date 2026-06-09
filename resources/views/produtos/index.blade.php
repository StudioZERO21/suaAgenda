@extends('layouts.app')
@section('title', 'Produtos')

@section('content')
<x-sa.page x-data="productsApp()">
    <x-sa.app-header title="Produtos" subtitle="Gerencie o estoque e preços">
        <x-slot:actions>
            <x-sa.btn @click="openNew()"
                      :icon="view('components.sa.icons.plus')->render()">
                Novo Produto
            </x-sa.btn>
        </x-slot:actions>
    </x-sa.app-header>

    <x-sa.body>
        {{-- Stat cards --}}
        <div class="sa-grid-4" style="margin-bottom:20px">
            <div class="sa-tint-card" style="--tint:var(--sa-primary)">
                <div class="sa-tint-card__label">Produtos ativos</div>
                <div class="sa-tint-card__value" x-text="stats.totalActive"></div>
                <div class="sa-tint-card__icon"><svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="1.5"><polyline points="20 6 9 17 4 12"/></svg></div>
            </div>
            <div class="sa-tint-card" style="--tint:var(--sa-primary)">
                <div class="sa-tint-card__label">Total em estoque</div>
                <div class="sa-tint-card__value" x-text="stats.totalStock"></div>
                <div class="sa-tint-card__icon"><svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="1.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg></div>
            </div>
            <div class="sa-tint-card" style="--tint:var(--sa-secondary)">
                <div class="sa-tint-card__label">Valor em estoque</div>
                <div class="sa-tint-card__value" x-text="stats.stockValue"></div>
                <div class="sa-tint-card__icon"><svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="1.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
            </div>
            <div class="sa-tint-card" style="--tint:#ef4444">
                <div class="sa-tint-card__label">Estoque baixo</div>
                <div class="sa-tint-card__value" :style="stats.lowStock > 0 ? 'color:#ef4444' : ''" x-text="stats.lowStock"></div>
                <div class="sa-tint-card__icon"><svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            </div>
        </div>

        {{-- Low stock alert --}}
        <div x-show="stats.lowStock > 0" x-cloak
             style="display:flex;gap:10px;align-items:center;padding:10px 16px;background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.2);border-radius:10px;margin-bottom:16px">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span style="font-size:13px;color:#dc2626;font-weight:600" x-text="stats.lowStock + ' produto' + (stats.lowStock > 1 ? 's' : '') + ' com estoque baixo (<5 unidades)'"></span>
            <x-sa.btn variant="ghost" size="sm" @click="showLowStock()" style="margin-left:auto;color:#dc2626">Ver</x-sa.btn>
        </div>

        {{-- Filters --}}
        <div style="display:flex;gap:10px;margin-bottom:16px;align-items:center;flex-wrap:wrap">
            <div style="position:relative;flex:1;max-width:300px">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" x-model="search" placeholder="Buscar produto ou SKU..." class="sa-search-input">
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
                <template x-for="cat in categorias" :key="cat">
                    <button type="button" @click="setCatFilter(cat)"
                            :style="'padding:7px 12px;border-radius:8px;border:1px solid ' + (catFilter === cat ? 'var(--sa-primary)' : 'var(--sa-border)') + ';background:' + (catFilter === cat ? 'var(--sa-primary)' : 'var(--sa-surface)') + ';color:' + (catFilter === cat ? '#fff' : 'var(--sa-text2)') + ';font-size:12px;font-weight:' + (catFilter === cat ? '600' : '400') + ';cursor:pointer;font-family:var(--sa-font-body);white-space:nowrap;transition:all 150ms'"
                            x-text="cat"></button>
                </template>
            </div>
        </div>

        <div x-show="lowStockOnly" x-cloak style="margin-bottom:12px">
            <button type="button" @click="lowStockOnly = false"
                    style="font-size:12px;color:var(--sa-text3);background:none;border:none;cursor:pointer;font-weight:600;text-decoration:underline">
                ✕ Limpar filtro de estoque baixo
            </button>
        </div>

        {{-- Table --}}
        <x-sa.card :flush="true">
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse">
                    <thead>
                        <tr style="background:var(--sa-surface2);border-bottom:1px solid var(--sa-border)">
                            <th class="sa-th">Produto</th>
                            <th class="sa-th hide-mobile">SKU</th>
                            <th class="sa-th hide-mobile">Categoria</th>
                            <th class="sa-th">Preço</th>
                            <th class="sa-th hide-mobile">Custo</th>
                            <th class="sa-th hide-mobile">Margem</th>
                            <th class="sa-th">Estoque</th>
                            <th class="sa-th hide-mobile">Status</th>
                            <th class="sa-th">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="p in filtered" :key="p.id">
                            <tr class="sa-tr">
                                <td class="sa-td">
                                    <div style="display:flex;align-items:center;gap:10px">
                                        <div style="width:34px;height:34px;border-radius:9px;background:color-mix(in srgb,var(--sa-secondary) 15%,transparent);border:1px solid color-mix(in srgb,var(--sa-secondary) 25%,transparent);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                        </div>
                                        <div>
                                            <div style="font-size:14px;font-weight:700" x-text="p.nome"></div>
                                            <div style="font-size:11px;color:var(--sa-text3);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" x-text="(p.descricao || '').slice(0, 35) + ((p.descricao || '').length > 35 ? '…' : '')"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="sa-td hide-mobile" style="font-family:monospace;font-size:12px" x-text="p.sku || '—'"></td>
                                <td class="sa-td hide-mobile">
                                    <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;background:var(--sa-surface2);color:var(--sa-text2)" x-text="p.categoria"></span>
                                </td>
                                <td class="sa-td" style="font-weight:700" x-text="formatCurrency(p.preco)"></td>
                                <td class="sa-td hide-mobile" style="color:var(--sa-text3)" x-text="formatCurrency(p.custo)"></td>
                                <td class="sa-td hide-mobile">
                                    <span style="font-size:12px;font-weight:700"
                                          :style="'color:' + marginColor(calcMargin(p))" x-text="calcMargin(p) + '%'"></span>
                                </td>
                                <td class="sa-td">
                                    <span style="font-weight:700" :style="'color:' + (p.estoque < 5 ? '#ef4444' : 'var(--sa-text1)')" x-text="p.estoque + ' ' + p.unidade"></span>
                                    <span x-show="p.estoque < 5" style="font-size:10px;color:#ef4444;display:block;line-height:1.2">⚠ estoque baixo</span>
                                </td>
                                <td class="sa-td hide-mobile">
                                    <button type="button" @click="toggleActive(p.id)" role="switch" :aria-checked="p.ativo ? 'true' : 'false'"
                                            :style="'position:relative;width:38px;height:22px;border-radius:11px;border:none;cursor:pointer;background:' + (p.ativo ? 'var(--sa-primary)' : 'var(--sa-border)') + ';transition:background 200ms;padding:0'">
                                        <div :style="'position:absolute;top:2px;left:' + (p.ativo ? '18px' : '2px') + ';width:18px;height:18px;border-radius:50%;background:#fff;transition:left 200ms'"></div>
                                    </button>
                                </td>
                                <td class="sa-td">
                                    <div style="display:flex;gap:5">
                                        <x-sa.btn size="sm" variant="muted" @click="openEdit(p)">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                            Editar
                                        </x-sa.btn>
                                        <x-sa.btn size="sm" variant="ghost" @click="doDelete(p.id)">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                                        </x-sa.btn>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filtered.length === 0">
                            <td colspan="9" style="padding:48px 0;text-align:center;color:var(--sa-text3);font-size:14px">Nenhum produto encontrado.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-sa.card>
    </x-sa.body>

    {{-- ProductModal --}}
    <div x-show="modalOpen" x-cloak
         @keydown.escape.window="modalOpen = false"
         style="position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:1000;padding:20px"
         @click.self="modalOpen = false">
        <div style="background:var(--sa-surface);border-radius:16px;width:100%;max-width:600px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.2);animation:sa-modal-in 250ms ease">
            <div style="padding:24px 28px 0;display:flex;justify-content:space-between;align-items:flex-start;flex-shrink:0">
                <div>
                    <h3 style="font-family:var(--sa-font-heading);font-size:18px;font-weight:600;color:var(--sa-text1);margin:0" x-text="editing ? 'Editar Produto' : 'Novo Produto'"></h3>
                    <p style="font-size:13px;color:var(--sa-text3);margin:4px 0 0">Preencha as informações do produto</p>
                </div>
                <button type="button" @click="modalOpen = false" style="background:none;border:none;cursor:pointer;color:var(--sa-text3);padding:4px;display:flex;border-radius:6px">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div style="padding:20px 28px;overflow-y:auto;flex:1">
                <div style="display:flex;flex-direction:column;gap:14px">
                    {{-- Preview card --}}
                    <div style="display:flex;gap:14px;padding:14px;background:var(--sa-surface2);border-radius:12px;border:1px solid var(--sa-border);align-items:center">
                        <div style="width:56px;height:56px;border-radius:12px;background:color-mix(in srgb,var(--sa-secondary) 15%,transparent);border:1px dashed var(--sa-border);display:flex;align-items:center;justify-content:center;flex-shrink:0;cursor:pointer;overflow:hidden;position:relative"
                             @click="editing && $refs.fileInput && $refs.fileInput.click()">
                            <template x-if="capaImagem">
                                <img :src="capaImagem.url" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0">
                            </template>
                            <template x-if="!capaImagem">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2" style="opacity:.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            </template>
                        </div>
                        <div style="flex:1">
                            <div style="font-family:var(--sa-font-heading);font-size:16px;font-weight:700;color:var(--sa-text1)" x-text="form.nome || 'Nome do produto'"></div>
                            <div style="display:flex;gap:10px;margin-top:4px">
                                <span style="font-size:13px;font-weight:800;color:var(--sa-secondary)" x-text="formatCurrency(form.preco)"></span>
                                <span x-show="formMargin > 0" style="font-size:12px;color:#10b981;font-weight:600" x-text="'Margem ' + formMargin + '%'"></span>
                            </div>
                        </div>
                        <div style="text-align:right">
                            <div style="font-size:11px;color:var(--sa-text3)">Estoque</div>
                            <div style="font-family:var(--sa-font-heading);font-size:22px;font-weight:800"
                                 :style="'color:' + (form.estoque < 5 ? '#ef4444' : form.estoque < 10 ? '#f59e0b' : '#10b981')"
                                 x-text="form.estoque"></div>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div>
                            <label style="font-size:13px;font-weight:600;color:var(--sa-text1);display:block;margin-bottom:6">Nome do produto</label>
                            <input type="text" x-model="form.nome" placeholder="Ex: Pomada modeladora" required
                                   style="width:100%;padding:9px 12px;font-size:13px;border:1px solid var(--sa-border);border-radius:8px;background:var(--sa-surface);color:var(--sa-text1);font-family:var(--sa-font-body);outline:none;box-sizing:border-box">
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:var(--sa-text1);display:block;margin-bottom:6">SKU / Código</label>
                            <input type="text" x-model="form.sku" placeholder="Ex: POB001"
                                   style="width:100%;padding:9px 12px;font-size:13px;border:1px solid var(--sa-border);border-radius:8px;background:var(--sa-surface);color:var(--sa-text1);font-family:var(--sa-font-body);outline:none;box-sizing:border-box">
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div>
                            <label style="font-size:13px;font-weight:600;color:var(--sa-text1);display:block;margin-bottom:6">Categoria</label>
                            <select x-model="form.categoria"
                                    style="width:100%;padding:9px 12px;font-size:13px;border:1px solid var(--sa-border);border-radius:8px;background:var(--sa-surface);color:var(--sa-text1);font-family:var(--sa-font-body);outline:none">
                                <template x-for="cat in categorias.filter(c => c !== 'Todos')" :key="cat">
                                    <option :value="cat" x-text="cat"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:var(--sa-text1);display:block;margin-bottom:6">Unidade</label>
                            <select x-model="form.unidade"
                                    style="width:100%;padding:9px 12px;font-size:13px;border:1px solid var(--sa-border);border-radius:8px;background:var(--sa-surface);color:var(--sa-text1);font-family:var(--sa-font-body);outline:none">
                                <template x-for="u in unidades" :key="u">
                                    <option :value="u" x-text="u"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                        <div>
                            <label style="font-size:13px;font-weight:600;color:var(--sa-text1);display:block;margin-bottom:6">Preço de venda</label>
                            <div style="position:relative">
                                <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:12px;color:var(--sa-text3)">R$</span>
                                <input type="number" x-model.number="form.preco" min="0" step="0.01"
                                       style="width:100%;padding:9px 10px 9px 32px;font-size:13px;border:1px solid var(--sa-border);border-radius:8px;background:var(--sa-surface);color:var(--sa-text1);font-family:var(--sa-font-body);outline:none;box-sizing:border-box">
                            </div>
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:var(--sa-text1);display:block;margin-bottom:6">Custo</label>
                            <div style="position:relative">
                                <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:12px;color:var(--sa-text3)">R$</span>
                                <input type="number" x-model.number="form.custo" min="0" step="0.01"
                                       style="width:100%;padding:9px 10px 9px 32px;font-size:13px;border:1px solid var(--sa-border);border-radius:8px;background:var(--sa-surface);color:var(--sa-text1);font-family:var(--sa-font-body);outline:none;box-sizing:border-box">
                            </div>
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;color:var(--sa-text1);display:block;margin-bottom:6">Estoque</label>
                            <input type="number" x-model.number="form.estoque" min="0"
                                   style="width:100%;padding:9px 12px;font-size:13px;border:1px solid var(--sa-border);border-radius:8px;background:var(--sa-surface);color:var(--sa-text1);font-family:var(--sa-font-body);outline:none;box-sizing:border-box">
                        </div>
                    </div>
                    {{-- Photo gallery --}}
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                            <label style="font-size:13px;font-weight:600;color:var(--sa-text1)">Fotos do Produto</label>
                            <button type="button" x-show="editing && (form.imagens || []).length < 7"
                                    @click="$refs.fileInput.click()"
                                    style="display:inline-flex;align-items:center;gap:5px;padding:5px 10px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:12px;font-weight:600;cursor:pointer;font-family:var(--sa-font-body)"
                                    onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                                    onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Adicionar Foto
                            </button>
                            <input type="file" x-ref="fileInput" accept="image/*" style="display:none" @change="uploadImagem($event)">
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px">
                            {{-- Cover slot --}}
                            <div @click="editing && $refs.fileInput.click()"
                                 :style="'position:relative;aspect-ratio:1;border-radius:10px;overflow:hidden;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;cursor:' + (editing ? 'pointer' : 'default') + ';background:color-mix(in srgb,var(--sa-secondary) 12%,transparent);border:2px solid ' + ((form.imagens || []).length > 0 ? 'var(--sa-secondary)' : 'var(--sa-border)')">
                                <template x-if="capaImagem">
                                    <img :src="capaImagem.url" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">
                                </template>
                                <template x-if="!capaImagem">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2" style="opacity:.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                </template>
                                <span x-show="!capaImagem" style="font-size:10px;font-weight:700;color:var(--sa-secondary);letter-spacing:.5px">CAPA</span>
                                <div x-show="(form.imagens || []).length > 0" style="position:absolute;top:4px;right:4px;background:var(--sa-secondary);border-radius:20px;padding:2px 6px">
                                    <span style="font-size:9px;font-weight:700;color:#fff">CAPA</span>
                                </div>
                            </div>
                            {{-- Existing images --}}
                            <template x-for="img in (form.imagens || []).filter(i => !i.is_capa)" :key="img.id">
                                <div style="position:relative;aspect-ratio:1;border-radius:10px;overflow:hidden;border:2px solid var(--sa-border);background:var(--sa-surface2)"
                                     @mouseenter="img._hover = true" @mouseleave="img._hover = false">
                                    <img :src="img.url" style="width:100%;height:100%;object-fit:cover">
                                    <div x-show="img._hover" style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.5);padding:4px 6px;display:flex;gap:4px;justify-content:space-between">
                                        <button type="button" @click.stop="setCapaImagem(img)"
                                                style="font-size:9px;background:var(--sa-secondary);color:#fff;border:none;border-radius:4px;padding:2px 5px;cursor:pointer;font-weight:700">Capa</button>
                                        <button type="button" @click.stop="deleteImagem(img)"
                                                style="font-size:9px;background:#ef4444;color:#fff;border:none;border-radius:4px;padding:2px 5px;cursor:pointer">✕</button>
                                    </div>
                                </div>
                            </template>
                            {{-- Add slot (when editing and < 7 additional images) --}}
                            <div x-show="editing && (form.imagens || []).length < 7"
                                 @click="$refs.fileInput.click()"
                                 style="aspect-ratio:1;border-radius:10px;border:2px dashed var(--sa-border);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;cursor:pointer;transition:all 160ms"
                                 onmouseover="this.style.borderColor='var(--sa-primary)';this.style.background='color-mix(in srgb,var(--sa-primary) 4%,transparent)'"
                                 onmouseout="this.style.borderColor='var(--sa-border)';this.style.background='transparent'">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                <span style="font-size:9px;font-family:monospace;color:var(--sa-text3)">Adicionar</span>
                            </div>
                            {{-- Placeholder when creating new product --}}
                            <div x-show="!editing" style="aspect-ratio:1;border-radius:10px;border:2px dashed var(--sa-border);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;cursor:default;opacity:.5">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                <span style="font-size:9px;font-family:monospace;color:var(--sa-text3)">Salve primeiro</span>
                            </div>
                        </div>
                        <p style="font-size:11px;color:var(--sa-text3);margin-top:6px">Passe o mouse nas fotos para definir como capa ou remover. Máx. 8 fotos.</p>
                        <div x-show="uploadingImagem" style="font-size:12px;color:var(--sa-text2);margin-top:4px">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2" style="animation:spin 1s linear infinite;display:inline-block;vertical-align:middle;margin-right:4px"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>
                            Enviando imagem...
                        </div>
                    </div>

                    <div>
                        <label style="font-size:13px;font-weight:600;color:var(--sa-text1);display:block;margin-bottom:6">Descrição</label>
                        <textarea x-model="form.descricao" rows="2" placeholder="Descreva o produto..."
                                  style="width:100%;padding:9px 12px;font-size:13px;border:1px solid var(--sa-border);border-radius:8px;background:var(--sa-surface);color:var(--sa-text1);font-family:var(--sa-font-body);outline:none;box-sizing:border-box;resize:vertical"></textarea>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:var(--sa-surface2);border-radius:9px;border:1px solid var(--sa-border)">
                        <div>
                            <div style="font-size:13px;font-weight:600;color:var(--sa-text1)">Produto ativo</div>
                            <div style="font-size:12px;color:var(--sa-text3)">Aparece no PDV e na loja online</div>
                        </div>
                        <button type="button" @click="form.ativo = !form.ativo" role="switch" :aria-checked="form.ativo ? 'true' : 'false'"
                                :style="'width:42px;height:24px;border-radius:12px;border:none;cursor:pointer;background:' + (form.ativo ? 'var(--sa-primary)' : 'var(--sa-border)') + ';position:relative;padding:0;transition:background 200ms'">
                            <div :style="'position:absolute;top:3px;left:' + (form.ativo ? '20px' : '3px') + ';width:18px;height:18px;border-radius:50%;background:#fff;transition:left 200ms;box-shadow:0 1px 3px rgba(0,0,0,.2)'"></div>
                        </button>
                    </div>
                    <div x-show="form.preco > 0 && form.custo > 0"
                         :style="'background:' + (formMargin >= 40 ? 'rgba(16,185,129,.07)' : formMargin >= 20 ? 'rgba(245,158,11,.07)' : 'rgba(239,68,68,.06)') + ';border:1px solid ' + (formMargin >= 40 ? 'rgba(16,185,129,.2)' : formMargin >= 20 ? 'rgba(245,158,11,.2)' : 'rgba(239,68,68,.15)') + ';border-radius:9px;padding:10px 14px'">
                        <div style="font-size:12px;font-weight:700" :style="'color:' + marginColor(formMargin)"
                             x-text="'Margem de lucro: ' + formMargin + '% · Lucro por unidade: ' + formatCurrency(form.preco - form.custo)"></div>
                    </div>
                </div>
            </div>
            <div style="padding:16px 28px 24px;border-top:1px solid var(--sa-border);display:flex;gap:10px;justify-content:flex-end;flex-shrink:0">
                <x-sa.btn variant="secondary" size="sm" @click="modalOpen = false">Cancelar</x-sa.btn>
                <x-sa.btn size="sm" @click="saveProduct()" x-bind:disabled="saving">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px"><polyline points="20 6 9 17 4 12"/></svg>
                    <span x-text="editing ? 'Salvar' : 'Cadastrar'"></span>
                </x-sa.btn>
            </div>
        </div>
    </div>
</x-sa.page>

@push('scripts')
<script>
function productsApp() {
    const categorias = @json($categorias);
    const unidades = @json($unidades);

    const blankForm = () => ({
        nome: '', categoria: categorias.find(c => c !== 'Todos') || 'Cabelo',
        preco: 0, custo: 0, estoque: 0, unidade: unidades[0] || 'un.',
        ativo: true, sku: '', descricao: '', imagens: [],
    });

    return {
        prods: @json($produtosJson),
        categorias,
        unidades,
        modalOpen: false,
        editing: null,
        search: '',
        catFilter: 'Todos',
        lowStockOnly: false,
        form: blankForm(),
        saving: false,
        uploadingImagem: false,

        get stats() {
            const active = this.prods.filter(p => p.ativo);
            return {
                totalActive: active.length,
                totalStock: this.prods.reduce((s, p) => s + p.estoque, 0),
                stockValue: 'R$ ' + this.prods.reduce((s, p) => s + p.estoque * p.custo, 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 }),
                lowStock: this.prods.filter(p => p.estoque < 5 && p.ativo).length,
            };
        },

        get filtered() {
            const q = this.search.toLowerCase();
            return this.prods.filter(p => {
                if (this.lowStockOnly && (p.estoque >= 5 || !p.ativo)) return false;
                if (this.catFilter !== 'Todos' && p.categoria !== this.catFilter) return false;
                if (q && !p.nome.toLowerCase().includes(q) && !(p.sku || '').toLowerCase().includes(q)) return false;
                return true;
            });
        },

        get formMargin() {
            return this.calcMargin(this.form);
        },

        get capaImagem() {
            return (this.form.imagens || []).find(i => i.is_capa) || null;
        },

        calcMargin(p) {
            return p.preco > 0 && p.custo > 0
                ? Math.round(((p.preco - p.custo) / p.preco) * 100)
                : 0;
        },

        marginColor(mg) {
            return mg >= 40 ? '#059669' : mg >= 20 ? '#d97706' : '#dc2626';
        },

        formatCurrency(v) {
            return 'R$ ' + Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
        },

        setCatFilter(cat) {
            this.catFilter = cat;
            this.lowStockOnly = false;
        },

        showLowStock() {
            this.lowStockOnly = true;
            this.catFilter = 'Todos';
            this.search = '';
        },

        openNew() {
            this.editing = null;
            this.form = blankForm();
            this.modalOpen = true;
        },

        openEdit(p) {
            this.editing = p;
            this.form = { ...p, imagens: [...(p.imagens || [])] };
            this.modalOpen = true;
        },

        toggleActive(id) {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            fetch(`/produtos/${id}/toggle`, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': csrf } })
                .then(r => r.json())
                .then(data => { this.prods = this.prods.map(p => p.id === id ? data : p); });
        },

        doDelete(id) {
            Swal.fire({
                title: 'Remover produto?',
                text: 'Esta ação não pode ser desfeita.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Remover',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef4444',
            }).then(result => {
                if (!result.isConfirmed) return;
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                fetch(`/produtos/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf } })
                    .then(() => {
                        this.prods = this.prods.filter(p => p.id !== id);
                        Swal.fire({ title: 'Produto removido', icon: 'success', confirmButtonText: 'OK', confirmButtonColor: '#1a1a1a' });
                    });
            });
        },

        saveProduct() {
            if (!this.form.nome.trim() || this.form.preco <= 0) {
                return Swal.fire({ title: 'Atenção', text: 'Preencha nome e preço.', icon: 'error', confirmButtonText: 'OK', confirmButtonColor: '#1a1a1a' });
            }
            this.saving = true;
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const isEditing = !!this.editing;
            const url = isEditing ? `/produtos/${this.editing.id}` : '/produtos';
            const method = isEditing ? 'PUT' : 'POST';
            fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(this.form),
            })
            .then(r => r.ok ? r.json() : Promise.reject(r))
            .then(data => {
                if (isEditing) {
                    this.prods = this.prods.map(p => p.id === data.id ? data : p);
                    this.editing = data;
                    this.form = { ...data, imagens: [...(data.imagens || [])] };
                    this.saving = false;
                    this.modalOpen = false;
                    Swal.fire({ title: 'Produto atualizado!', icon: 'success', confirmButtonText: 'OK', confirmButtonColor: '#1a1a1a' });
                } else {
                    this.prods.push(data);
                    this.editing = data;
                    this.form = { ...data, imagens: [...(data.imagens || [])] };
                    this.saving = false;
                    Swal.fire({ title: 'Produto cadastrado!', text: 'Agora você pode adicionar fotos ao produto.', icon: 'success', confirmButtonText: 'OK', confirmButtonColor: '#1a1a1a' });
                }
            })
            .catch(() => {
                this.saving = false;
                Swal.fire({ title: 'Erro', text: 'Não foi possível salvar.', icon: 'error', confirmButtonText: 'OK', confirmButtonColor: '#1a1a1a' });
            });
        },

        uploadImagem(event) {
            if (!this.editing) return;
            const file = event.target.files[0];
            if (!file) return;
            event.target.value = '';

            this.uploadingImagem = true;
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const fd = new FormData();
            fd.append('imagem', file);

            fetch(`/produtos/${this.editing.id}/imagens`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
                body: fd,
            })
            .then(r => r.ok ? r.json() : Promise.reject(r))
            .then(img => {
                this.form.imagens = [...(this.form.imagens || []), img];
                this.prods = this.prods.map(p => p.id === this.editing.id ? { ...p, imagens: this.form.imagens } : p);
                this.uploadingImagem = false;
            })
            .catch(() => {
                this.uploadingImagem = false;
                Swal.fire({ title: 'Erro', text: 'Não foi possível enviar a imagem.', icon: 'error', confirmButtonText: 'OK', confirmButtonColor: '#1a1a1a' });
            });
        },

        deleteImagem(img) {
            Swal.fire({
                title: 'Remover foto?',
                text: 'Esta ação não pode ser desfeita.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Remover',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef4444',
            }).then(result => {
                if (!result.isConfirmed) return;
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                fetch(`/produtos/imagens/${img.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf } })
                    .then(() => {
                        this.form.imagens = this.form.imagens.filter(i => i.id !== img.id);
                        // If deleted was capa, set first as capa
                        if (img.is_capa && this.form.imagens.length > 0) {
                            this.form.imagens[0].is_capa = true;
                        }
                        this.prods = this.prods.map(p => p.id === this.editing.id ? { ...p, imagens: this.form.imagens } : p);
                    })
                    .catch(() => Swal.fire({ title: 'Erro', text: 'Não foi possível remover a imagem.', icon: 'error', confirmButtonText: 'OK', confirmButtonColor: '#1a1a1a' }));
            });
        },

        setCapaImagem(img) {
            if (!this.editing) return;
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            fetch(`/produtos/imagens/${img.id}/capa`, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': csrf } })
                .then(r => r.ok ? r.json() : Promise.reject(r))
                .then(() => {
                    this.form.imagens = this.form.imagens.map(i => ({ ...i, is_capa: i.id === img.id }));
                    this.prods = this.prods.map(p => p.id === this.editing.id ? { ...p, imagens: this.form.imagens } : p);
                })
                .catch(() => Swal.fire({ title: 'Erro', text: 'Não foi possível definir a capa.', icon: 'error', confirmButtonText: 'OK', confirmButtonColor: '#1a1a1a' }));
        },
    };
}
</script>
@endpush
@endsection
