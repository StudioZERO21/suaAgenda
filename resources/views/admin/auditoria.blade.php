@extends('layouts.app')
@section('title', 'Auditoria')
@section('page-title', 'Auditoria')

@section('content')
<div x-data="auditoriaApp()" x-init="carregar()">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
        <div>
            <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">Auditoria</h1>
            <p style="font-size:14px;color:var(--sa-text3);margin:0">Trilha de atividades do sistema (LGPD) — quem fez o quê, quando</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:16px 20px;box-shadow:0 1px 3px rgba(0,0,0,.05);margin-bottom:16px">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px">
            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Empresa</label>
                <select x-model="filtros.empresa_id" @change="pagina = 1; carregar()"
                        style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;appearance:none">
                    <option value="">Todas</option>
                    @foreach($empresas as $empresa)
                        <option value="{{ $empresa->id }}">{{ $empresa->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Evento</label>
                <select x-model="filtros.evento" @change="pagina = 1; carregar()"
                        style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;appearance:none">
                    <option value="">Todos</option>
                    @foreach($eventos as $evento)
                        <option value="{{ $evento }}">{{ $evento }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Registro</label>
                <select x-model="filtros.tipo" @change="pagina = 1; carregar()"
                        style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;appearance:none">
                    <option value="">Todos</option>
                    @foreach($tipos as $tipo)
                        <option value="{{ $tipo['value'] }}">{{ $tipo['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">De</label>
                <input type="date" x-model="filtros.de" @change="pagina = 1; carregar()"
                       style="width:100%;padding:9px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none">
            </div>
            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Até</label>
                <input type="date" x-model="filtros.ate" @change="pagina = 1; carregar()"
                       style="width:100%;padding:9px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none">
            </div>
        </div>
    </div>

    {{-- Tabela --}}
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;min-width:760px">
                <thead>
                    <tr style="background:var(--sa-surface2);border-bottom:1px solid var(--sa-border)">
                        <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">Quando</th>
                        <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">Quem</th>
                        <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">Evento</th>
                        <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">Registro</th>
                        <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">Descrição</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="item in items" :key="item.id">
                        <tr style="border-bottom:1px solid var(--sa-border);transition:background 120ms;cursor:pointer"
                            @mouseenter="$el.style.background='var(--sa-surface2)'"
                            @mouseleave="$el.style.background='transparent'"
                            @click="detalhe = item">
                            <td style="padding:12px 16px;font-size:13px;color:var(--sa-text2);white-space:nowrap" x-text="item.quando"></td>
                            <td style="padding:12px 16px;font-size:13px;color:var(--sa-text1);font-weight:600" x-text="item.causer"></td>
                            <td style="padding:12px 16px">
                                <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600"
                                      :style="badgeStyle(item.evento)">
                                    <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                                    <span x-text="item.evento"></span>
                                </span>
                            </td>
                            <td style="padding:12px 16px;font-size:13px;color:var(--sa-text2)" x-text="item.tipo"></td>
                            <td style="padding:12px 16px;font-size:13px;color:var(--sa-text2);max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" x-text="item.descricao"></td>
                        </tr>
                    </template>
                    <tr x-show="!carregando && items.length === 0">
                        <td colspan="5" style="padding:32px 16px;text-align:center;font-size:14px;color:var(--sa-text3)">Nenhuma atividade encontrada para os filtros.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 20px;border-top:1px solid var(--sa-border)">
            <span style="font-size:13px;color:var(--sa-text3)" x-text="total + ' registros'"></span>
            <div style="display:flex;gap:8px;align-items:center">
                <button type="button" @click="if (pagina > 1) { pagina--; carregar(); }" :disabled="pagina <= 1"
                        style="padding:7px 14px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;font-size:13px;color:var(--sa-text2)">Anterior</button>
                <span style="font-size:13px;color:var(--sa-text3)" x-text="pagina + ' / ' + ultimaPagina"></span>
                <button type="button" @click="if (pagina < ultimaPagina) { pagina++; carregar(); }" :disabled="pagina >= ultimaPagina"
                        style="padding:7px 14px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;font-size:13px;color:var(--sa-text2)">Próxima</button>
            </div>
        </div>
    </div>

    {{-- Modal detalhe --}}
    <div x-show="detalhe !== null" x-cloak @keydown.escape.window="detalhe = null"
         style="position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:1000;padding:20px"
         @click.self="detalhe = null">
        <div style="background:var(--sa-surface);border-radius:16px;width:min(600px, calc(100vw - 32px));max-height:85vh;overflow-y:auto;box-shadow:0 24px 64px rgba(0,0,0,.2);animation:sa-modal-in 250ms ease;padding:24px 28px">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px">
                <h3 style="font-family:'Poppins',sans-serif;font-size:17px;font-weight:700;color:var(--sa-text1);margin:0">Detalhe da atividade</h3>
                <button type="button" @click="detalhe = null" style="background:none;border:none;cursor:pointer;color:var(--sa-text3);padding:4px">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <template x-if="detalhe">
                <div style="display:flex;flex-direction:column;gap:10px">
                    <div><span style="font-size:12px;color:var(--sa-text3)">Quando:</span> <span style="font-size:13px;color:var(--sa-text1)" x-text="detalhe.quando"></span></div>
                    <div><span style="font-size:12px;color:var(--sa-text3)">Quem:</span> <span style="font-size:13px;color:var(--sa-text1)" x-text="detalhe.causer + (detalhe.causer_email ? ' (' + detalhe.causer_email + ')' : '')"></span></div>
                    <div><span style="font-size:12px;color:var(--sa-text3)">Evento:</span> <span style="font-size:13px;color:var(--sa-text1)" x-text="detalhe.evento + ' · ' + detalhe.tipo"></span></div>
                    <div>
                        <div style="font-size:12px;color:var(--sa-text3);margin-bottom:4px">Alterações / propriedades:</div>
                        <pre style="background:var(--sa-surface2);border:1px solid var(--sa-border);border-radius:8px;padding:12px;font-size:12px;color:var(--sa-text2);overflow-x:auto;margin:0"
                             x-text="JSON.stringify(detalhe.mudancas && Object.keys(detalhe.mudancas).length ? detalhe.mudancas : detalhe.properties, null, 2)"></pre>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

@push('scripts')
<script>
function auditoriaApp() {
    return {
        items: [],
        total: 0,
        pagina: 1,
        ultimaPagina: 1,
        carregando: false,
        detalhe: null,
        filtros: { empresa_id: '', evento: '', tipo: '', de: '', ate: '' },

        badgeStyle(evento) {
            const mapa = {
                created: 'background:rgba(16,185,129,.12);color:#059669',
                updated: 'background:rgba(245,158,11,.12);color:#d97706',
                deleted: 'background:rgba(239,68,68,.1);color:#dc2626',
                login: 'background:rgba(99,102,241,.12);color:#6366f1',
                logout: 'background:rgba(107,114,128,.12);color:#6b7280',
                login_falho: 'background:rgba(239,68,68,.1);color:#dc2626',
            };
            return mapa[evento] || 'background:rgba(107,114,128,.12);color:#6b7280';
        },

        async carregar() {
            this.carregando = true;
            const params = new URLSearchParams({ page: this.pagina });
            Object.entries(this.filtros).forEach(([k, v]) => { if (v) params.append(k, v); });
            try {
                const r = await fetch('{{ route('admin.auditoria.json') }}?' + params, { headers: { 'Accept': 'application/json' } });
                const data = await r.json();
                this.items = data.items;
                this.total = data.total;
                this.ultimaPagina = data.ultima_pagina;
            } finally {
                this.carregando = false;
            }
        },
    };
}
</script>
@endpush
@endsection
