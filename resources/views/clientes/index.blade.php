@extends('layouts.app')
@section('title', 'Clientes')
@section('page-title', 'Clientes')

@push('styles')
<style>
    /* Alpine x-for em thead quebra colunas — thead usa Blade estático */
    .sa-clients-table thead > template { display: none; }
    .sa-clients-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 20px;
        border-top: 1px solid var(--sa-border);
    }
    .sa-clients-pager { display: flex; gap: 6px; align-items: center; }
    .sa-clients-pager > template { display: contents; }
    .sa-clients-pager-btn {
        width: 30px; height: 30px; border-radius: 7px;
        border: 1px solid var(--sa-border); background: transparent;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        color: var(--sa-text3); font-size: 13px; font-weight: 600;
        transition: border-color 150ms, color 150ms, background 150ms;
    }
    .sa-clients-pager-btn:hover:not(:disabled) {
        border-color: var(--sa-primary); color: var(--sa-text1);
    }
    .sa-clients-pager-btn:disabled { opacity: .4; cursor: default; }
    .sa-clients-pager-btn--active {
        background: var(--sa-primary); color: #fff; border-color: var(--sa-primary);
    }
    .sa-clients-fotos-grid {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;
    }
    .sa-clients-fotos-grid > template { display: contents; }
    .sa-clients-modal {
        position: relative; background: var(--sa-surface); border-radius: 16px;
        border: 1px solid var(--sa-border); width: 100%; max-width: 720px;
        max-height: 88vh; overflow: hidden; display: flex; flex-direction: column;
        box-shadow: 0 20px 60px rgba(0,0,0,.18); animation: sa-modal-in 250ms ease;
    }
    .sa-export-menu {
        position: absolute; top: calc(100% + 6px); right: 0; min-width: 160px; z-index: 50;
        background: var(--sa-surface); border: 1px solid var(--sa-border); border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,.1); padding: 6px; overflow: hidden;
    }
    .sa-export-menu a {
        display: flex; align-items: center; gap: 8px; padding: 9px 12px; border-radius: 7px;
        font-size: 13px; font-weight: 600; color: var(--sa-text2); text-decoration: none;
        transition: background 120ms, color 120ms;
    }
    .sa-export-menu a:hover { background: var(--sa-surface2); color: var(--sa-text1); }
</style>
@endpush

@section('content')
<x-sa.page x-data="clientesApp()">

    <x-sa.app-header title="Clientes">
        <x-slot:subtitle>
            <span x-text="`${filtered.length} cliente${filtered.length !== 1 ? 's' : ''} encontrado${filtered.length !== 1 ? 's' : ''}`"></span>
        </x-slot:subtitle>
        <x-slot:actions>
            @can('viewAny', \App\Models\Cliente::class)
            <div style="position:relative" @click.outside="exportOpen = false">
                <button type="button" @click="exportOpen = !exportOpen"
                        style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;cursor:pointer;font-family:var(--sa-font-body);transition:border-color 180ms,color 180ms"
                        onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                        onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Exportar
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div x-show="exportOpen" x-cloak class="sa-export-menu">
                    <a href="{{ route('clientes.exportar') }}">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        Exportar CSV
                    </a>
                    <a href="{{ route('clientes.exportar.pdf') }}">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        Exportar PDF
                    </a>
                </div>
            </div>
            @endcan
            @can('create', \App\Models\Cliente::class)
            <a href="{{ route('clientes.create') }}"
               style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:var(--sa-font-body);background:var(--sa-primary);color:#fff;text-decoration:none;transition:filter 200ms"
               onmouseover="this.style.filter='brightness(1.1)'"
               onmouseout="this.style.filter='none'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Novo Cliente
            </a>
            @endcan
        </x-slot:actions>
    </x-sa.app-header>

    <x-sa.body>
    {{-- Filtros --}}
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:14px 20px;box-shadow:0 1px 3px rgba(0,0,0,.05);margin-bottom:16px">
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
            {{-- Busca --}}
            <div style="position:relative;flex:1;min-width:220px">
                <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--sa-text3);pointer-events:none;display:flex">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
                <input type="text" x-model="search" @input="page = 0"
                       placeholder="Buscar por nome, e-mail ou telefone..."
                       style="width:100%;padding:10px 13px 10px 34px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms;box-sizing:border-box"
                       onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'"
                       onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
            </div>

            {{-- Status filter --}}
            <div style="position:relative;flex-shrink:0">
                <select x-model="statusFilter" @change="page = 0"
                        style="padding:10px 32px 10px 13px;font-size:13px;border:1.5px solid var(--sa-border);border-radius:8px;background:var(--sa-surface);color:var(--sa-text1);cursor:pointer;font-family:'Inter',sans-serif;appearance:none;background-image:url(&quot;data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'/%3e%3c/svg%3e&quot;);background-repeat:no-repeat;background-position:right 8px center;background-size:14px;white-space:nowrap;outline:none">
                    <option value="all">Todos os status</option>
                    <option value="active">Ativos</option>
                    <option value="inactive">Inativos</option>
                </select>
            </div>

            {{-- Remover selecionados --}}
            <button x-show="selected.length > 0"
                    @click="confirmRemoveSelected()"
                    style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:8px;border:none;cursor:pointer;font-size:13px;font-weight:600;font-family:'Inter',sans-serif;background:#ef4444;color:#fff;transition:filter 200ms"
                    onmouseover="this.style.filter='brightness(1.1)'"
                    onmouseout="this.style.filter='none'">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                <span x-text="`Remover ${selected.length}`"></span>
            </button>
        </div>
    </div>

    {{-- Tabela --}}
    <div class="sa-clients-table" style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:var(--sa-surface2);border-bottom:1px solid var(--sa-border)">
                        {{-- Checkbox --}}
                        <th style="padding:11px 14px;width:40px">
                            <input type="checkbox"
                                   :checked="paged.length > 0 && paged.every(c => selected.includes(c.id))"
                                   @change="toggleAll($event.target.checked)"
                                   style="cursor:pointer;accent-color:var(--sa-primary)">
                        </th>
                        {{-- Colunas ordenáveis (estáticas — x-for em thead quebra layout) --}}
                        @foreach([
                            ['name', 'Cliente'],
                            ['email', 'E-mail'],
                            ['phone', 'Telefone'],
                            ['last_date', 'Último Agend.'],
                            ['total', 'Total'],
                            ['status', 'Status'],
                        ] as [$colKey, $colLabel])
                        <th @click="toggleSort('{{ $colKey }}')"
                            style="padding:11px 14px;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.5px;cursor:pointer;user-select:none;white-space:nowrap;text-align:left">
                            <span style="display:inline-flex;align-items:center;gap:4px">
                                <span>{{ $colLabel }}</span>
                                <span x-show="sortCol !== '{{ $colKey }}'" style="opacity:.3">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                                </span>
                                <span x-show="sortCol === '{{ $colKey }}' && sortDir === 'asc'" x-cloak style="color:var(--sa-secondary)">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>
                                </span>
                                <span x-show="sortCol === '{{ $colKey }}' && sortDir === 'desc'" x-cloak style="color:var(--sa-secondary)">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                                </span>
                            </span>
                        </th>
                        @endforeach
                        <th style="padding:11px 14px;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.5px;width:80px">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr x-show="paged.length === 0">
                        <td colspan="8" style="padding:48px 0;text-align:center;color:var(--sa-text3);font-size:14px">
                            Nenhum cliente encontrado
                        </td>
                    </tr>
                    <template x-for="client in paged" :key="client.id">
                        <tr style="border-bottom:1px solid var(--sa-border);transition:background 120ms"
                            onmouseover="this.style.background='var(--sa-surface2)'"
                            onmouseout="this.style.background='transparent'">
                            {{-- Checkbox --}}
                            <td style="padding:14px;vertical-align:middle;width:40px">
                                <input type="checkbox"
                                       :checked="selected.includes(client.id)"
                                       @change="toggleSel(client.id)"
                                       style="cursor:pointer;accent-color:var(--sa-primary)">
                            </td>
                            {{-- Cliente (avatar + nome) --}}
                            <td style="padding:14px;font-size:14px;color:var(--sa-text1);vertical-align:middle">
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div style="width:32px;height:32px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;font-family:'Inter',sans-serif;flex-shrink:0"
                                         x-text="client.name.charAt(0).toUpperCase()"></div>
                                    <span @click="openDetail(client)"
                                          style="font-weight:600;cursor:pointer;color:var(--sa-primary)"
                                          x-text="client.name"></span>
                                </div>
                            </td>
                            {{-- E-mail --}}
                            <td style="padding:14px;font-size:14px;color:var(--sa-text2);vertical-align:middle" x-text="client.email || '—'"></td>
                            {{-- Telefone --}}
                            <td style="padding:14px;font-size:14px;color:var(--sa-text2);vertical-align:middle" x-text="client.phone || '—'"></td>
                            {{-- Último Agend. --}}
                            <td style="padding:14px;font-size:14px;color:var(--sa-text2);vertical-align:middle" x-text="formatDate(client.last_date)"></td>
                            {{-- Total --}}
                            <td style="padding:14px;font-size:14px;color:var(--sa-text1);vertical-align:middle">
                                <span style="font-weight:600" x-text="client.total"></span>
                            </td>
                            {{-- Status --}}
                            <td style="padding:14px;vertical-align:middle">
                                <span x-show="client.status === 'active'"
                                      style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(16,185,129,.12);color:#059669">
                                    <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                                    Ativo
                                </span>
                                <span x-show="client.status !== 'active'"
                                      style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(107,114,128,.12);color:#6b7280">
                                    <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                                    Inativo
                                </span>
                            </td>
                            {{-- Ações --}}
                            <td style="padding:14px;vertical-align:middle">
                                <div style="display:flex;gap:6px">
                                    {{-- Ver --}}
                                    <button @click="openDetail(client)"
                                            title="Ver detalhes"
                                            style="width:30px;height:30px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--sa-text3);transition:all 150ms"
                                            onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'"
                                            onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                    {{-- Editar --}}
                                    <a :href="`/clientes/${client.id}/edit`"
                                       title="Editar"
                                       style="width:30px;height:30px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--sa-text3);text-decoration:none;transition:all 150ms"
                                       onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'"
                                       onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Paginação --}}
        <div x-show="filtered.length > 0" x-cloak class="sa-clients-pagination">
            <span style="font-size:13px;color:var(--sa-text3)"
                  x-text="`${page * perPage + 1}–${Math.min((page + 1) * perPage, filtered.length)} de ${filtered.length}`"></span>
            <div x-show="totalPages > 1" class="sa-clients-pager">
                <button type="button" class="sa-clients-pager-btn" @click="page--" :disabled="page === 0">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <template x-for="i in totalPages" :key="i">
                    <button type="button"
                            class="sa-clients-pager-btn"
                            :class="(i - 1) === page ? 'sa-clients-pager-btn--active' : ''"
                            @click="page = i - 1"
                            x-text="i"></button>
                </template>
                <button type="button" class="sa-clients-pager-btn" @click="page++" :disabled="page >= totalPages - 1">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            </div>
        </div>
    </div>

    </x-sa.body>

    {{-- Modal de detalhes — sa-modal-overlay centraliza (Alpine remove display inline do x-show) --}}
    <div x-show="detail !== null"
         x-cloak
         @keydown.escape.window="detail = null"
         class="sa-modal-overlay"
         @click.self="detail = null">

        <div class="sa-clients-modal" @click.stop x-show="detail !== null">

            {{-- Header --}}
            <div style="padding:20px 24px;border-bottom:1px solid var(--sa-border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
                <div>
                    <div style="font-family:'Poppins',sans-serif;font-size:17px;font-weight:700;color:var(--sa-text1)" x-text="detail?.name"></div>
                    <div style="font-size:13px;color:var(--sa-text3);margin-top:2px">Perfil do Cliente</div>
                </div>
                <button @click="detail = null"
                        style="width:32px;height:32px;border-radius:8px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--sa-text3);transition:all 150ms"
                        onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                        onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            {{-- Body (scrollable) --}}
            <div style="padding:24px;overflow-y:auto;display:flex;flex-direction:column;gap:20px;flex:1">

                {{-- Header do perfil: avatar + nome + ações --}}
                <div style="display:flex;align-items:center;gap:16px">
                    <div style="position:relative;flex-shrink:0">
                        <div style="width:72px;height:72px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:700;font-family:'Inter',sans-serif"
                             x-text="detail?.name.charAt(0).toUpperCase()"></div>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-family:'Poppins',sans-serif;font-size:20px;font-weight:700;color:var(--sa-text1)" x-text="detail?.name"></div>
                        <div style="display:flex;align-items:center;gap:10px;margin-top:6px;flex-wrap:wrap">
                            <span x-show="detail?.status === 'active'"
                                  style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(16,185,129,.12);color:#059669">
                                <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>Ativo
                            </span>
                            <span x-show="detail?.status !== 'active'"
                                  style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(107,114,128,.12);color:#6b7280">
                                <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>Inativo
                            </span>
                            {{-- WhatsApp --}}
                            <button @click="detail && window.open(`https://wa.me/${detail.phone.replace(/\D/g,'')}`, '_blank')"
                                    style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#25D366;background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.25);border-radius:20px;padding:4px 12px;cursor:pointer">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.5a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.09a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0121.15 15z"/></svg>
                                Abrir WhatsApp
                            </button>
                            {{-- E-mail --}}
                            <a :href="`mailto:${detail?.email}`"
                               style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:var(--sa-secondary);background:color-mix(in srgb,var(--sa-secondary) 10%,transparent);border:1px solid color-mix(in srgb,var(--sa-secondary) 25%,transparent);border-radius:20px;padding:4px 12px;text-decoration:none">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                E-mail
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Info grid 2 colunas --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div style="background:var(--sa-surface2);border-radius:8px;padding:10px 14px">
                        <div style="font-size:11px;color:var(--sa-text3);font-weight:600;text-transform:uppercase;letter-spacing:.4px">E-mail</div>
                        <div style="font-size:14px;font-weight:600;color:var(--sa-text1);margin-top:3px" x-text="detail?.email || '—'"></div>
                    </div>
                    <div style="background:var(--sa-surface2);border-radius:8px;padding:10px 14px">
                        <div style="font-size:11px;color:var(--sa-text3);font-weight:600;text-transform:uppercase;letter-spacing:.4px">Telefone</div>
                        <div style="font-size:14px;font-weight:600;color:var(--sa-text1);margin-top:3px" x-text="detail?.phone || '—'"></div>
                    </div>
                    <div style="background:var(--sa-surface2);border-radius:8px;padding:10px 14px">
                        <div style="font-size:11px;color:var(--sa-text3);font-weight:600;text-transform:uppercase;letter-spacing:.4px">Último Agend.</div>
                        <div style="font-size:14px;font-weight:600;color:var(--sa-text1);margin-top:3px" x-text="formatDate(detail?.last_date)"></div>
                    </div>
                    <div style="background:var(--sa-surface2);border-radius:8px;padding:10px 14px">
                        <div style="font-size:11px;color:var(--sa-text3);font-weight:600;text-transform:uppercase;letter-spacing:.4px">Total de Visitas</div>
                        <div style="font-size:14px;font-weight:600;color:var(--sa-text1);margin-top:3px" x-text="detail?.total ?? 0"></div>
                    </div>
                </div>

                {{-- Fotos de Atendimento --}}
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                        <div style="font-size:13px;font-weight:600;color:var(--sa-text1)">Fotos de Atendimento (Antes & Depois)</div>
                        <button @click="$refs.fotoInput.click()"
                                style="display:inline-flex;align-items:center;gap:6px;padding:7px 13px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;cursor:pointer;font-size:12px;font-weight:600;color:var(--sa-text2);transition:border-color 180ms,color 180ms"
                                onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                                onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Adicionar
                        </button>
                        <input type="file" x-ref="fotoInput" accept="image/*" class="sr-only" style="display:none"
                               @change="uploadFoto($event)">
                    </div>

                    <div class="sa-clients-fotos-grid">
                        <template x-for="foto in (detail?.fotos ?? [])" :key="foto.id">
                            <div style="aspect-ratio:4/3;border-radius:10px;overflow:hidden;position:relative;background:var(--sa-surface2);border:1px solid var(--sa-border)">
                                <img :src="foto.url" :alt="foto.legenda || foto.tipo"
                                     style="width:100%;height:100%;object-fit:cover">
                                <div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,0,0,.5));padding:6px 8px">
                                    <span style="font-size:10px;color:#fff;font-weight:600" x-text="foto.legenda || foto.tipo"></span>
                                </div>
                                <button @click="deleteFoto(foto)"
                                        style="position:absolute;top:4px;right:4px;width:22px;height:22px;border-radius:50%;background:rgba(0,0,0,.5);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#fff">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>
                            </div>
                        </template>

                        {{-- Slot de adicionar --}}
                        <div @click="$refs.fotoInput.click()"
                             style="aspect-ratio:4/3;border-radius:10px;border:2px dashed var(--sa-border);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;cursor:pointer;transition:all 160ms"
                             onmouseover="this.style.borderColor='var(--sa-primary)';this.style.background='color-mix(in srgb,var(--sa-primary) 4%,transparent)'"
                             onmouseout="this.style.borderColor='var(--sa-border)';this.style.background='transparent'">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--sa-text3)"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            <div style="font-size:10px;color:var(--sa-text3);font-family:monospace">Adicionar foto</div>
                        </div>
                    </div>

                    <p style="font-size:11px;color:var(--sa-text3);margin-top:8px;margin-bottom:0">Fotos adicionadas aqui aparecem automaticamente no portfólio da equipe.</p>
                </div>

            </div>

            {{-- Rodapé --}}
            <div style="padding:16px 24px;border-top:1px solid var(--sa-border);display:flex;gap:10px;justify-content:flex-end;flex-shrink:0">
                <button @click="detail = null"
                        style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;color:var(--sa-text2);transition:border-color 180ms,color 180ms"
                        onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                        onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                    Fechar
                </button>
                <button @click="detail && window.open(`https://wa.me/${detail.phone.replace(/\D/g,'')}`, '_blank')"
                        style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:1.5px solid rgba(37,211,102,.4);background:transparent;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;color:#25D366;transition:all 180ms"
                        onmouseover="this.style.background='rgba(37,211,102,.08)'"
                        onmouseout="this.style.background='transparent'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.5a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.09a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0121.15 15z"/></svg>
                    WhatsApp
                </button>
                <a :href="detail ? `/agendamentos/create?cliente_id=${detail.id}` : '#'"
                   style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:var(--sa-primary);color:#fff;text-decoration:none;transition:filter 200ms"
                   onmouseover="this.style.filter='brightness(1.1)'"
                   onmouseout="this.style.filter='none'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Novo Agendamento
                </a>
            </div>
        </div>
    </div>

</x-sa.page>

@push('scripts')
<script>
function clientesApp() {
    return {
        clientes: @json($clientesJson),
        search: '',
        statusFilter: 'all',
        sortCol: 'name',
        sortDir: 'asc',
        selected: [],
        page: 0,
        perPage: 8,
        detail: null,
        uploadingFoto: false,
        exportOpen: false,

        get filtered() {
            let list = [...this.clientes];

            if (this.search) {
                const q = this.search.toLowerCase();
                list = list.filter(c =>
                    c.name.toLowerCase().includes(q) ||
                    (c.email && c.email.toLowerCase().includes(q)) ||
                    (c.phone && c.phone.includes(q))
                );
            }

            if (this.statusFilter !== 'all') {
                list = list.filter(c => c.status === this.statusFilter);
            }

            list.sort((a, b) => {
                let va = a[this.sortCol] ?? '';
                let vb = b[this.sortCol] ?? '';
                if (typeof va === 'string') { va = va.toLowerCase(); vb = vb.toLowerCase(); }
                if (va < vb) return this.sortDir === 'asc' ? -1 : 1;
                if (va > vb) return this.sortDir === 'asc' ? 1 : -1;
                return 0;
            });

            return list;
        },

        get paged() {
            return this.filtered.slice(this.page * this.perPage, (this.page + 1) * this.perPage);
        },

        get totalPages() {
            return Math.ceil(this.filtered.length / this.perPage);
        },

        toggleSort(col) {
            if (this.sortCol === col) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortCol = col;
                this.sortDir = 'asc';
            }
            this.page = 0;
        },

        toggleSel(id) {
            const idx = this.selected.indexOf(id);
            if (idx === -1) this.selected.push(id);
            else this.selected.splice(idx, 1);
        },

        toggleAll(checked) {
            if (checked) {
                this.paged.forEach(c => {
                    if (!this.selected.includes(c.id)) this.selected.push(c.id);
                });
            } else {
                const pagedIds = this.paged.map(c => c.id);
                this.selected = this.selected.filter(id => !pagedIds.includes(id));
            }
        },

        openDetail(c) {
            this.detail = {
                ...c,
                fotos: [...(c.fotos ?? [])],
            };
        },

        formatDate(val) {
            if (!val) return '—';
            try {
                const d = new Date(val);
                return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
            } catch {
                return val;
            }
        },

        async confirmRemoveSelected() {
            const count = this.selected.length;
            const result = await Swal.fire({
                title: `Remover ${count} cliente${count > 1 ? 's' : ''}?`,
                text: 'Esta ação não pode ser desfeita.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, remover',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: 'transparent',
                customClass: { cancelButton: 'swal-cancel-muted' },
            });
            if (!result.isConfirmed) return;

            const resp = await fetch('{{ route('clientes.bulk-destroy') }}', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ ids: this.selected }),
            });

            if (resp.ok) {
                const data = await resp.json();
                this.selected = [];
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: `${data.deleted} cliente${data.deleted !== 1 ? 's' : ''} removido${data.deleted !== 1 ? 's' : ''}`, showConfirmButton: false, timer: 2500 });
                setTimeout(() => window.location.reload(), 800);
            } else {
                Swal.fire({ icon: 'error', title: 'Erro ao remover clientes.' });
            }
        },

        async uploadFoto(event) {
            const file = event.target.files[0];
            if (!file || !this.detail) return;
            event.target.value = '';

            const formData = new FormData();
            formData.append('imagem', file);
            formData.append('tipo', 'outro');

            const url = `{{ url('clientes') }}/${this.detail.id}/fotos`;

            try {
                this.uploadingFoto = true;
                const resp = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (!resp.ok) throw new Error('Upload falhou');

                const payload = await resp.json();
                const foto = payload.data ?? payload;

                this.detail.fotos = [...(this.detail.fotos ?? []), foto];

                const orig = this.clientes.find(c => c.id === this.detail.id);
                if (orig) {
                    if (!orig.fotos) orig.fotos = [];
                    orig.fotos = [...orig.fotos, foto];
                }

                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Foto enviada!', showConfirmButton: false, timer: 2000, timerProgressBar: true });
            } catch {
                Swal.fire({ icon: 'error', title: 'Erro ao enviar', text: 'Tente novamente.' });
            } finally {
                this.uploadingFoto = false;
            }
        },

        async deleteFoto(foto) {
            const r = await Swal.fire({
                title: 'Excluir foto?',
                text: 'Esta ação não pode ser desfeita.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: 'transparent',
                customClass: { cancelButton: 'swal-cancel-muted' },
            });

            if (!r.isConfirmed) return;

            try {
                const resp = await fetch(`/clientes/fotos/${foto.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });

                if (!resp.ok) throw new Error();

                if (this.detail) {
                    this.detail.fotos = this.detail.fotos.filter(f => f.id !== foto.id);
                }
                const orig = this.clientes.find(c => c.id === this.detail?.id);
                if (orig && orig.fotos) {
                    orig.fotos = orig.fotos.filter(f => f.id !== foto.id);
                }

                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Foto excluída!', showConfirmButton: false, timer: 2000, timerProgressBar: true });
            } catch {
                Swal.fire({ icon: 'error', title: 'Erro ao excluir', text: 'Tente novamente.' });
            }
        },
    };
}
</script>
@endpush
@endsection
