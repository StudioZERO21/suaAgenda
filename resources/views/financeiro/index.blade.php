@extends('layouts.app')
@section('title', 'Financeiro')

@section('content')
@php
    $maxReceita = max($receitaDiaria) ?: 1;
    $chartW = 560;
    $chartH = 130;
    $pts = [];
    $count = count($receitaDiaria);
    foreach ($receitaDiaria as $i => $v) {
        $x = $count > 1 ? ($i / ($count - 1)) * $chartW : 0;
        $y = $chartH - (($v / $maxReceita) * ($chartH - 16)) - 8;
        $pts[] = ['x' => $x, 'y' => $y];
    }
    $linePath = collect($pts)->map(fn ($p, $i) => ($i ? 'L' : 'M') . round($p['x'], 1) . ',' . round($p['y'], 1))->join(' ');
    $areaPath = $linePath . "L{$chartW},{$chartH}L0,{$chartH}Z";
    $mesAnterior = $inicio->copy()->subMonth()->translatedFormat('F');
@endphp

<x-sa.page x-data="{
    transacoes: @json($transacoes->values()),
    filters: { type: 'all', status: 'all', prof: 'all', method: 'all' },
    dateFrom: '{{ $inicio->format('Y-m-d') }}',
    dateTo: '{{ $fim->format('Y-m-d') }}',
    statusCfg: {
        paid:     { label: 'Pago',        bg: 'rgba(16,185,129,.1)',  color: '#059669' },
        pending:  { label: 'Pendente',    bg: 'rgba(245,158,11,.1)',  color: '#d97706' },
        refunded: { label: 'Reembolsado', bg: 'rgba(239,68,68,.08)', color: '#dc2626' },
    },
    lancModalOpen: false,
    lancSaving: false,
    lancForm: { tipo: 'receita', descricao: '', categoria: '', valor: '', data: '{{ now()->format('Y-m-d') }}', status: 'pendente', metodo_pagamento: '' },
    get filtersActive() {
        return this.filters.type !== 'all' || this.filters.status !== 'all'
            || this.filters.prof !== 'all' || this.filters.method !== 'all';
    },
    get filtered() {
        return this.transacoes.filter(tx => {
            if (this.filters.type !== 'all' && tx.tipo !== this.filters.type) return false;
            if (this.filters.status !== 'all' && tx.status_key !== this.filters.status) return false;
            if (this.filters.prof !== 'all' && !tx.profissional.startsWith(this.filters.prof)) return false;
            if (this.filters.method !== 'all' && tx.metodo !== this.filters.method) return false;
            if (this.dateFrom && tx.data < this.dateFrom) return false;
            if (this.dateTo && tx.data > this.dateTo) return false;
            return true;
        });
    },
    resetFilters() {
        this.filters = { type: 'all', status: 'all', prof: 'all', method: 'all' };
    },
    openLancModal() {
        this.lancForm = { tipo: 'receita', descricao: '', categoria: '', valor: '', data: '{{ now()->format('Y-m-d') }}', status: 'pendente', metodo_pagamento: '' };
        this.lancModalOpen = true;
    },
    async saveLancamento() {
        if (!this.lancForm.descricao || !this.lancForm.valor || !this.lancForm.data) {
            return Swal.fire({ title: 'Atenção', text: 'Preencha os campos obrigatórios.', icon: 'error', confirmButtonColor: '#1a1a1a' });
        }
        this.lancSaving = true;
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const r = await fetch('/financeiro/lancamentos', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(this.lancForm)
            });
            if (!r.ok) { const e = await r.json(); throw new Error(Object.values(e.errors || {}).flat().join(', ') || 'Erro ao salvar'); }
            const data = await r.json();
            this.transacoes.unshift(data);
            this.lancModalOpen = false;
            Swal.fire({ title: 'Lançamento criado!', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
        } catch (e) {
            Swal.fire({ title: 'Erro', text: e.message, icon: 'error', confirmButtonColor: '#1a1a1a' });
        } finally {
            this.lancSaving = false;
        }
    },
    async deleteLancamento(id) {
        const confirm = await Swal.fire({ title: 'Excluir lançamento?', text: 'Esta ação não pode ser desfeita.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim, excluir', cancelButtonText: 'Cancelar', confirmButtonColor: '#ef4444' });
        if (!confirm.isConfirmed) return;
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        await fetch('/financeiro/lancamentos/' + id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
        this.transacoes = this.transacoes.filter(tx => tx.id !== id);
        Swal.fire({ title: 'Removido!', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
    },
    fmtDate(iso) {
        const [y, m, d] = iso.split('-');
        return `${d}/${m}/${y}`;
    },
    fmtCurrency(v) {
        return 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    },
    profFirst(name) {
        return (name || '').split(' ')[0] || '—';
    }
}">
    <x-sa.app-header title="Financeiro" subtitle="Receita, comissões e pagamentos">
        <x-slot:actions>
            <div style="display:flex;gap:8px;align-items:center">
                <button type="button" @click="openLancModal()"
                    style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:var(--sa-primary);color:#fff;transition:filter 200ms"
                    onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Novo Lançamento
                </button>
                @foreach(['month' => 'Este mês', 'quarter' => 'Trimestre', 'year' => 'Este ano'] as $key => $label)
                <x-sa.btn :href="route('financeiro', ['periodo' => $key])" size="sm" :variant="$periodo === $key ? 'primary' : 'muted'">{{ $label }}</x-sa.btn>
                @endforeach
            </div>
        </x-slot:actions>
    </x-sa.app-header>

    <x-sa.body padding="24px 32px 0">
        <div class="sa-grid-4" style="margin-bottom:20px">
            <x-sa.tint-card label="Receita Total" :value="'R$ ' . number_format($receitaTotal, 2, ',', '.')" accent="var(--sa-secondary)" trend="+14%" :positive="true"
                sub="{{ $totalFinalizados }} finalizados"
                :icon="'<svg width=\'130\' height=\'130\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'var(--sa-secondary)\' stroke-width=\'1.5\'><line x1=\'12\' y1=\'1\' x2=\'12\' y2=\'23\'/><path d=\'M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6\'/></svg>'" />
            <x-sa.tint-card label="Ticket Médio" :value="'R$ ' . number_format($ticketMedio, 2, ',', '.')" trend="+5%" :positive="true" sub="por atendimento"
                :icon="'<svg width=\'130\' height=\'130\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'var(--sa-primary)\' stroke-width=\'1.5\'><polyline points=\'23 6 13.5 15.5 8.5 10.5 1 18\'/></svg>'" />
            <x-sa.tint-card label="Comissões" :value="'R$ ' . number_format($comissaoTotal, 2, ',', '.')" trend="30%" :positive="false" sub="da receita total"
                :icon="'<svg width=\'130\' height=\'130\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'var(--sa-primary)\' stroke-width=\'1.5\'><path d=\'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2\'/><circle cx=\'9\' cy=\'7\' r=\'4\'/></svg>'" />
            <x-sa.tint-card label="A Receber" :value="'R$ ' . number_format($aReceber, 2, ',', '.')" accent="#f59e0b" :sub="$qtdPendentes . ' pendentes'"
                :icon="'<svg width=\'130\' height=\'130\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'#f59e0b\' stroke-width=\'1.5\'><circle cx=\'12\' cy=\'12\' r=\'10\'/><polyline points=\'12 6 12 12 16 14\'/></svg>'" />
        </div>

        {{-- FinFilterBar --}}
        <div style="display:flex;gap:10px;align-items:center;padding:10px 14px;background:var(--sa-surface);border-radius:10px;border:1px solid var(--sa-border);margin-bottom:20px;flex-wrap:wrap">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
            </svg>
            @foreach([
                ['id' => 'type', 'label' => 'Tipo', 'opts' => [['v' => 'all', 'l' => 'Todos'], ['v' => 'receita', 'l' => 'Receita'], ['v' => 'despesa', 'l' => 'Despesa']]],
                ['id' => 'status', 'label' => 'Status', 'opts' => [['v' => 'all', 'l' => 'Todos'], ['v' => 'paid', 'l' => 'Pago'], ['v' => 'pending', 'l' => 'Pendente'], ['v' => 'refunded', 'l' => 'Reembolsado']]],
                ['id' => 'method', 'label' => 'Método', 'opts' => [['v' => 'all', 'l' => 'Todos'], ['v' => 'Pix', 'l' => 'Pix'], ['v' => 'Cartão Crédito', 'l' => 'Crédito'], ['v' => 'Cartão Débito', 'l' => 'Débito'], ['v' => 'Dinheiro', 'l' => 'Dinheiro']]],
            ] as $chip)
            <div style="display:flex;align-items:center;gap:5px">
                <span style="font-size:11px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.4px">{{ $chip['label'] }}</span>
                <select x-model="filters.{{ $chip['id'] }}"
                    :style="filters.{{ $chip['id'] }} !== 'all'
                        ? 'font-size:12px;padding:4px 9px;border:1px solid var(--sa-border);border-radius:7px;font-family:var(--sa-font-body);cursor:pointer;background:color-mix(in srgb,var(--sa-primary) 9%,transparent);color:var(--sa-primary);font-weight:600'
                        : 'font-size:12px;padding:4px 9px;border:1px solid var(--sa-border);border-radius:7px;font-family:var(--sa-font-body);cursor:pointer;background:var(--sa-surface2);color:var(--sa-text2);font-weight:400'">
                    @foreach($chip['opts'] as $opt)
                    <option value="{{ $opt['v'] }}">{{ $opt['l'] }}</option>
                    @endforeach
                </select>
            </div>
            @endforeach
            <div style="display:flex;align-items:center;gap:5px">
                <span style="font-size:11px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.4px">Profissional</span>
                <select x-model="filters.prof"
                    :style="filters.prof !== 'all'
                        ? 'font-size:12px;padding:4px 9px;border:1px solid var(--sa-border);border-radius:7px;font-family:var(--sa-font-body);cursor:pointer;background:color-mix(in srgb,var(--sa-primary) 9%,transparent);color:var(--sa-primary);font-weight:600'
                        : 'font-size:12px;padding:4px 9px;border:1px solid var(--sa-border);border-radius:7px;font-family:var(--sa-font-body);cursor:pointer;background:var(--sa-surface2);color:var(--sa-text2);font-weight:400'">
                    <option value="all">Todos</option>
                    @foreach($profissionaisFiltro as $prof)
                    <option value="{{ explode(' ', $prof)[0] }}">{{ explode(' ', $prof)[0] }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;align-items:center;gap:5px;margin-left:auto">
                <span style="font-size:11px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.4px">De</span>
                <input type="date" x-model="dateFrom" style="font-size:12px;padding:4px 8px;border:1px solid var(--sa-border);border-radius:7px;background:var(--sa-surface2);color:var(--sa-text1);font-family:var(--sa-font-body)">
                <span style="font-size:11px;color:var(--sa-text3)">a</span>
                <input type="date" x-model="dateTo" style="font-size:12px;padding:4px 8px;border:1px solid var(--sa-border);border-radius:7px;background:var(--sa-surface2);color:var(--sa-text1);font-family:var(--sa-font-body)">
            </div>
            <button type="button" x-show="filtersActive" x-cloak @click="resetFilters()"
                style="font-size:11px;font-weight:600;color:#ef4444;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:6px;padding:4px 10px;cursor:pointer;font-family:var(--sa-font-body)">
                ✕ Limpar
            </button>
        </div>

        <div style="display:grid;grid-template-columns:1fr 300px;gap:20px;margin-bottom:20px">
            <x-sa.card style="padding:24px">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px">
                    <div>
                        <h3 style="font-family:var(--sa-font-heading);font-size:15px;font-weight:600;color:var(--sa-text1);margin:0">Receita Diária</h3>
                        <p style="font-size:12px;color:var(--sa-text3);margin:4px 0 0">{{ $inicio->translatedFormat('F Y') }}</p>
                    </div>
                    <div style="text-align:right">
                        <div style="font-family:var(--sa-font-heading);font-size:22px;font-weight:800;color:var(--sa-secondary)">R$ {{ number_format($receitaTotal, 2, ',', '.') }}</div>
                        <div style="font-size:12px;color:#10b981;display:flex;align-items:center;gap:4px;justify-content:flex-end">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>
                            </svg>
                            +14% vs. {{ $mesAnterior }}
                        </div>
                    </div>
                </div>
                @if($count > 0)
                <svg width="100%" viewBox="0 0 {{ $chartW }} {{ $chartH }}" preserveAspectRatio="none" style="display:block">
                    <defs>
                        <linearGradient id="fg-fin" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="var(--sa-secondary)" stop-opacity=".2"/>
                            <stop offset="100%" stop-color="var(--sa-secondary)" stop-opacity=".01"/>
                        </linearGradient>
                    </defs>
                    @foreach([.25,.5,.75,1] as $f)
                    <line x1="0" y1="{{ $chartH - $f * ($chartH - 16) - 8 }}" x2="{{ $chartW }}" y2="{{ $chartH - $f * ($chartH - 16) - 8 }}" stroke="var(--sa-border)" stroke-width="1" stroke-dasharray="4 4" opacity=".5"/>
                    @endforeach
                    <path d="{{ $areaPath }}" fill="url(#fg-fin)"/>
                    <path d="{{ $linePath }}" fill="none" stroke="var(--sa-secondary)" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
                @else
                <p style="text-align:center;color:var(--sa-text3);font-size:14px;padding:32px 0">Sem dados no período.</p>
                @endif
            </x-sa.card>

            <x-sa.card style="padding:22px">
                <h3 style="font-family:var(--sa-font-heading);font-size:15px;font-weight:600;color:var(--sa-text1);margin:0 0 20px">Formas de Pagamento</h3>
                <div style="display:flex;flex-direction:column;gap:14px">
                    @foreach($metodos as $m)
                    <div>
                        <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                            <div style="display:flex;align-items:center;gap:8px">
                                <div style="width:8px;height:8px;border-radius:50%;background:{{ $m['cor'] }}"></div>
                                <span style="font-size:13px;color:var(--sa-text2)">{{ $m['label'] }}</span>
                            </div>
                            <span style="font-size:13px;font-weight:700;color:var(--sa-text1)">{{ $m['pct'] }}%</span>
                        </div>
                        <div style="height:5px;border-radius:3px;background:var(--sa-surface2);overflow:hidden">
                            <div style="height:100%;border-radius:3px;background:{{ $m['cor'] }};width:{{ $m['pct'] }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-sa.card>
        </div>

        <div style="display:grid;grid-template-columns:1fr 300px;gap:20px">
            <x-sa.card :flush="true">
                <div style="padding:18px 20px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--sa-border)">
                    <div>
                        <h3 style="font-family:var(--sa-font-heading);font-size:15px;font-weight:600;color:var(--sa-text1);margin:0">Transações</h3>
                        <span style="font-size:12px;color:var(--sa-text3)" x-text="filtered.length + ' resultado(s)'"></span>
                    </div>
                    <x-sa.btn variant="ghost" size="sm" onclick="Swal.fire({toast:true,position:'top-end',icon:'info',title:'Exportação CSV será habilitada na próxima etapa.',showConfirmButton:false,timer:2500})">Exportar</x-sa.btn>
                </div>
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse">
                        <thead>
                            <tr style="background:var(--sa-surface2)">
                                <th class="sa-th">Data</th>
                                <th class="sa-th">Descrição</th>
                                <th class="sa-th hide-mobile">Categoria</th>
                                <th class="sa-th hide-mobile">Profissional</th>
                                <th class="sa-th">Tipo</th>
                                <th class="sa-th hide-mobile">Método</th>
                                <th class="sa-th">Valor</th>
                                <th class="sa-th">Status</th>
                                <th class="sa-th" style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="filtered.length === 0">
                                <tr><td colspan="9" style="padding:32px;text-align:center;color:var(--sa-text3)">Nenhuma transação encontrada.</td></tr>
                            </template>
                            <template x-for="tx in filtered" :key="tx.id">
                                <tr class="sa-tr">
                                    <td class="sa-td" x-text="fmtDate(tx.data)"></td>
                                    <td class="sa-td" style="font-weight:600" x-text="tx.cliente"></td>
                                    <td class="sa-td hide-mobile" style="color:var(--sa-text2)" x-text="tx.servico"></td>
                                    <td class="sa-td hide-mobile" style="color:var(--sa-text2)" x-text="profFirst(tx.profissional)"></td>
                                    <td class="sa-td">
                                        <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px"
                                            :style="tx.tipo === 'receita'
                                                ? 'background:rgba(16,185,129,.1);color:#059669'
                                                : 'background:rgba(239,68,68,.08);color:#dc2626'"
                                            x-text="tx.tipo"></span>
                                    </td>
                                    <td class="sa-td hide-mobile">
                                        <span style="font-size:11px;font-weight:600;padding:3px 8px;border-radius:6px;background:var(--sa-surface2);color:var(--sa-text2)" x-text="tx.metodo"></span>
                                    </td>
                                    <td class="sa-td" style="font-weight:700" x-text="fmtCurrency(tx.valor)"></td>
                                    <td class="sa-td">
                                        <span style="font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px"
                                            :style="'background:' + statusCfg[tx.status_key].bg + ';color:' + statusCfg[tx.status_key].color"
                                            x-text="statusCfg[tx.status_key].label"></span>
                                    </td>
                                    <td class="sa-td" style="padding:10px 8px">
                                        <template x-if="tx.source === 'lancamento'">
                                            <button type="button" @click="deleteLancamento(tx.id)"
                                                style="width:28px;height:28px;border-radius:6px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--sa-text3);transition:all 150ms"
                                                onmouseover="this.style.borderColor='#ef4444';this.style.color='#ef4444'"
                                                onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                                            </button>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </x-sa.card>

            <x-sa.card style="padding:22px">
                <h3 style="font-family:var(--sa-font-heading);font-size:15px;font-weight:600;color:var(--sa-text1);margin:0 0 20px">Comissões por Profissional</h3>
                <div style="display:flex;flex-direction:column;gap:20px">
                    @forelse($comissoesProfissionais as $item)
                    <div style="display:flex;align-items:center;gap:14px">
                        <x-sa.avatar :name="$item['name']" size="36" :color="$item['cor']" />
                        <div style="flex:1;min-width:0">
                            <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                                <span style="font-size:13px;font-weight:600;color:var(--sa-text1)">{{ $item['name'] }}</span>
                                <span style="font-size:13px;font-weight:700">R$ {{ number_format($item['valor'], 2, ',', '.') }}</span>
                            </div>
                            <div style="height:5px;border-radius:3px;background:var(--sa-surface2);overflow:hidden">
                                <div style="height:100%;border-radius:3px;background:{{ $item['cor'] }};width:{{ round($item['pct'] * 100) }}%"></div>
                            </div>
                            <div style="font-size:11px;color:var(--sa-text3);margin-top:3px">{{ round($item['pct'] * 100) }}% da receita</div>
                        </div>
                    </div>
                    @empty
                    <p style="font-size:13px;color:var(--sa-text3);text-align:center;padding:16px 0">Sem comissões no período.</p>
                    @endforelse
                </div>
                @if(count($comissoesProfissionais) > 0)
                <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--sa-border);display:flex;justify-content:space-between">
                    <span style="font-size:13px;color:var(--sa-text2)">Total comissões</span>
                    <span style="font-size:16px;font-weight:800;font-family:var(--sa-font-heading)">R$ {{ number_format($comissaoTotal, 2, ',', '.') }}</span>
                </div>
                @endif
            </x-sa.card>
        </div>
    </x-sa.body>

    {{-- Novo Lançamento Modal --}}
    <div x-show="lancModalOpen" x-cloak
         @keydown.escape.window="lancModalOpen = false"
         style="position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:1000;padding:20px"
         @click.self="lancModalOpen = false">
        <div style="background:var(--sa-surface);border-radius:16px;width:100%;max-width:520px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.2)">
            <div style="padding:24px 28px 0;display:flex;justify-content:space-between;align-items:flex-start;flex-shrink:0">
                <div>
                    <h3 style="font-family:var(--sa-font-heading);font-size:18px;font-weight:600;color:var(--sa-text1);margin:0">Novo Lançamento</h3>
                    <p style="font-size:13px;color:var(--sa-text3);margin:4px 0 0">Registre uma receita ou despesa manual</p>
                </div>
                <button type="button" @click="lancModalOpen = false" style="background:none;border:none;cursor:pointer;color:var(--sa-text3);padding:4px;display:flex;border-radius:6px">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div style="padding:20px 28px;overflow-y:auto;flex:1;display:flex;flex-direction:column;gap:16px">
                {{-- Tipo --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Tipo <span style="color:var(--sa-secondary)">*</span></label>
                        <select x-model="lancForm.tipo" style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;appearance:none"
                            onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                            <option value="receita">Receita</option>
                            <option value="despesa">Despesa</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Status <span style="color:var(--sa-secondary)">*</span></label>
                        <select x-model="lancForm.status" style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;appearance:none"
                            onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                            <option value="pendente">Pendente</option>
                            <option value="pago">Pago</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                </div>
                {{-- Descricao --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Descrição <span style="color:var(--sa-secondary)">*</span></label>
                    <input type="text" x-model="lancForm.descricao" placeholder="Ex: Pagamento de aluguel"
                        style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms;box-sizing:border-box"
                        onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'"
                        onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                </div>
                {{-- Categoria + Metodo --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Categoria</label>
                        <select x-model="lancForm.categoria" style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;appearance:none"
                            onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                            <option value="">Sem categoria</option>
                            <option value="Serviço">Serviço</option>
                            <option value="Produto">Produto</option>
                            <option value="Aluguel">Aluguel</option>
                            <option value="Insumos">Insumos</option>
                            <option value="Salários">Salários</option>
                            <option value="Outros">Outros</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Método de Pagamento</label>
                        <select x-model="lancForm.metodo_pagamento" style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;appearance:none"
                            onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                            <option value="">—</option>
                            <option value="Pix">Pix</option>
                            <option value="Cartão Crédito">Cartão Crédito</option>
                            <option value="Cartão Débito">Cartão Débito</option>
                            <option value="Dinheiro">Dinheiro</option>
                            <option value="Transferência">Transferência</option>
                        </select>
                    </div>
                </div>
                {{-- Valor + Data --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Valor (R$) <span style="color:var(--sa-secondary)">*</span></label>
                        <input type="number" x-model="lancForm.valor" step="0.01" min="0.01" placeholder="0,00"
                            style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms;box-sizing:border-box"
                            onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'"
                            onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Data <span style="color:var(--sa-secondary)">*</span></label>
                        <input type="date" x-model="lancForm.data"
                            style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms;box-sizing:border-box"
                            onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'"
                            onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    </div>
                </div>
            </div>
            <div style="padding:16px 28px 24px;border-top:1px solid var(--sa-border);display:flex;gap:10px;justify-content:flex-end;flex-shrink:0">
                <button type="button" @click="lancModalOpen = false"
                    style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;transition:border-color 180ms,color 180ms"
                    onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                    onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                    Cancelar
                </button>
                <button type="button" @click="saveLancamento()" :disabled="lancSaving"
                    style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:var(--sa-primary);color:#fff;transition:filter 200ms"
                    onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <span x-text="lancSaving ? 'Salvando...' : 'Salvar Lançamento'"></span>
                </button>
            </div>
        </div>
    </div>
</x-sa.page>
@endsection
