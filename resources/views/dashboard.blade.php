@extends('layouts.app')
@section('title', 'Dashboard')

@push('styles')
<style>
    .sa-view-toggle { display:flex; gap:0; background:var(--sa-surface2); border:1px solid var(--sa-border); border-radius:8px; overflow:hidden; }
    .sa-view-toggle button {
        display:flex; align-items:center; gap:6px; padding:7px 14px; border:none; cursor:pointer;
        font-size:12px; font-family:var(--sa-font-body); transition:all 150ms;
        background:transparent; color:var(--sa-text2); font-weight:500;
    }
    .sa-view-toggle button.active { background:var(--sa-primary); color:#fff; font-weight:700; }
    .sa-view-toggle button:first-child { border-right:1px solid var(--sa-border); }
    .sa-dash-grid { display:grid; grid-template-columns:1fr 360px; gap:20px; }
    .sa-kanban-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; overflow-x:auto; padding:4px 2px 12px; }
    .sa-kanban-col { min-width:180px; }
    .sa-kanban-head { display:flex; align-items:center; gap:7px; padding:8px 10px; border-radius:9px; margin-bottom:8px; }
    .sa-kanban-zone { min-height:80px; border-radius:9px; padding:2px 0; }
    .sa-kanban-card { background:var(--sa-surface); border:1px solid var(--sa-border); border-radius:10px; padding:11px 12px; margin:0 6px 8px; border-left:3px solid var(--col); transition:box-shadow 160ms; }
    .sa-kanban-card:hover { box-shadow:0 4px 12px rgba(0,0,0,.08); }
    .sa-donut-wrap { display:flex; align-items:center; gap:28px; }
    .sa-donut-svg { transform:rotate(-90deg); }
    .sa-donut-center { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; }
    .sa-timeline-rail { position:absolute; left:15px; top:8px; bottom:8px; width:2px; background:linear-gradient(to bottom,var(--sa-secondary),var(--sa-border) 80%); border-radius:1px; opacity:.5; }
    @media (max-width:1080px) { .sa-dash-grid { grid-template-columns:1fr; } .sa-kanban-grid { grid-template-columns:repeat(5,minmax(160px,1fr)); } }
</style>
@endpush

@section('content')
@if(!$stats)
<x-sa.page>
    <x-sa.app-header title="Dashboard" subtitle="Painel administrativo" />
    <x-sa.body padding="24px 32px 0">
        <x-sa.card padding="32px" style="text-align:center">
            <h1 style="font-family:var(--sa-font-heading);font-size:22px;font-weight:700;margin:0 0 8px">Bem-vindo, {{ auth()->user()->name }}!</h1>
            <p style="font-size:14px;color:var(--sa-text3);margin:0">Selecione uma empresa para visualizar o painel.</p>
        </x-sa.card>
    </x-sa.body>
</x-sa.page>
@else

@php
    $hoje = \Carbon\Carbon::today();
    $primeiroNome = explode(' ', auth()->user()->name)[0];
    $iconSvgs = [
        'calendar' => '<svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
        'dollar'   => '<svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="1.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>',
        'users'    => '<svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>',
        'check'    => '<svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="1.5"><polyline points="20 6 9 17 4 12"/></svg>',
    ];
    $kanbanCols = [
        ['id' => 'pendente',    'label' => 'Aguardando',      'color' => '#f59e0b'],
        ['id' => 'confirmado',  'label' => 'Confirmado',      'color' => '#6366f1'],
        ['id' => 'em_atendimento', 'label' => 'Em atendimento', 'color' => '#0ea5e9', 'statuses' => []],
        ['id' => 'finalizado',  'label' => 'Concluído',       'color' => '#10b981'],
        ['id' => 'cancelado',   'label' => 'Cancelado',       'color' => '#ef4444'],
    ];
    $statusDot = ['confirmado' => '#10b981', 'pendente' => '#f59e0b', 'cancelado' => '#ef4444'];
    $agrupados = $stats['proximosAgendamentos']->groupBy(fn ($ag) => $ag->data_hora->format('Y-m-d'));
    $donutR = 58;
    $donutCirc = 2 * M_PI * $donutR;
    $donutCum = 0;
@endphp

<x-sa.page x-data="{ view: 'timeline' }">
    <x-sa.app-header
        :title="'Olá, ' . $primeiroNome . ' ✦'"
        :subtitle="$hoje->format('d/m/Y') . ' — ' . $stats['agendamentosHoje'] . ' agendamentos hoje'">
        @can('create', \App\Models\Agendamento::class)
        <x-slot:actions>
            <x-sa.btn href="{{ route('agendamentos.create') }}" :icon="view('components.sa.icons.plus')->render()">
                Novo Agendamento
            </x-sa.btn>
        </x-slot:actions>
        @endcan
    </x-sa.app-header>

    <x-sa.body padding="24px 32px 0">
        {{-- KPI Cards --}}
        <div class="sa-grid-4" style="margin-bottom:24px">
            @foreach($stats['cards'] as $card)
            <x-sa.tint-card
                :label="$card['label']"
                :value="$card['value']"
                :trend="$card['trend']['text'] ?? null"
                :positive="$card['trend']['positive'] ?? true"
                :icon="$iconSvgs[$card['icon']] ?? null" />
            @endforeach
        </div>

        {{-- Toggle + título --}}
        <div style="margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
            <h3 style="font-family:var(--sa-font-heading);font-size:16px;font-weight:600;color:var(--sa-text1);margin:0"
                x-text="view === 'kanban' ? 'Kanban de Atendimentos' : 'Próximos Agendamentos'"></h3>
            <div class="sa-view-toggle">
                <button type="button" :class="{ active: view === 'timeline' }" @click="view = 'timeline'">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Linha do Tempo
                </button>
                <button type="button" :class="{ active: view === 'kanban' }" @click="view = 'kanban'">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5z"/></svg>
                    Kanban
                </button>
            </div>
        </div>

        {{-- KANBAN --}}
        <div x-show="view === 'kanban'" x-cloak>
            <div x-data="dashboardKanbanApp()">
                <div class="sa-kanban-grid">
                    <template x-for="col in cols" :key="col.id">
                        <div class="sa-kanban-col"
                             @dragover.prevent="dragOverCol = col.id"
                             @dragleave.prevent="dragOverCol = null"
                             @drop.prevent="drop(col.id)">
                            <div class="sa-kanban-head"
                                 :style="'background:' + col.color + '18;border:1px solid ' + col.color + '30'">
                                <div :style="'width:8px;height:8px;border-radius:50%;background:' + col.color"></div>
                                <span :style="'font-size:12px;font-weight:700;color:' + col.color" x-text="col.label"></span>
                                <span :style="'font-size:11px;font-weight:700;color:' + col.color + ';margin-left:auto;background:' + col.color + '20;border-radius:20px;padding:1px 7px'"
                                      x-text="cardsFor(col.id).length"></span>
                            </div>
                            <div class="sa-kanban-zone"
                                 :style="'background:' + col.color + '06;border:1px dashed ' + (dragOverCol === col.id ? col.color : col.color + '25') + ';transition:border-color 150ms'">
                                <template x-if="cardsFor(col.id).length === 0">
                                    <div :style="'padding:16px 8px;text-align:center;font-size:11px;color:' + col.color + '99'">Nenhum aqui</div>
                                </template>
                                <template x-for="card in cardsFor(col.id)" :key="card.id">
                                    <div class="sa-kanban-card"
                                         :style="'--col:' + col.color + ';cursor:' + (card.canEdit ? 'grab' : 'default')"
                                         :draggable="card.canEdit"
                                         @dragstart="startDrag(card)"
                                         @dragend="dragging = null">
                                        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px">
                                            <div style="font-size:13px;font-weight:700;color:var(--sa-text1)" x-text="card.cliente"></div>
                                            <span :style="'font-size:10px;font-weight:700;color:' + col.color + ';background:' + col.color + '18;padding:2px 7px;border-radius:20px'"
                                                  x-text="card.hora"></span>
                                        </div>
                                        <div style="font-size:12px;color:var(--sa-text3);margin-bottom:8px" x-text="card.servico"></div>
                                        <div style="display:flex;justify-content:space-between;align-items:center">
                                            <div style="display:flex;align-items:center;gap:5px">
                                                <div :style="'width:20px;height:20px;border-radius:50%;background:' + card.cor + ';color:#fff;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0'"
                                                     x-text="card.profissional.charAt(0).toUpperCase()"></div>
                                                <span style="font-size:11px;color:var(--sa-text3)" x-text="card.profissional"></span>
                                            </div>
                                            <template x-if="!card.canEdit">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- TIMELINE + painel direito --}}
        <div x-show="view === 'timeline'" class="sa-dash-grid">
            <x-sa.card :flush="true">
                <div style="padding:20px 20px 0;display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                    <div>
                        <h3 style="font-family:var(--sa-font-heading);font-size:16px;font-weight:600;margin:0">Próximos Agendamentos</h3>
                        <p style="font-size:13px;color:var(--sa-text3);margin:3px 0 0">Linha do tempo</p>
                    </div>
                    <x-sa.btn href="{{ route('agendamentos.index') }}" variant="ghost" size="sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        Agenda
                    </x-sa.btn>
                </div>

                <div style="padding:0 16px 16px;position:relative">
                    @if($stats['proximosAgendamentos']->isEmpty())
                    <div style="padding:48px 0;text-align:center;color:var(--sa-text3);font-size:14px">Nenhum agendamento próximo</div>
                    @else
                    <div class="sa-timeline-rail"></div>
                    @foreach($agrupados as $data => $agendamentos)
                    <div style="margin-bottom:20px">
                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;padding-left:36px">
                            <span style="font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:{{ $data === $hoje->format('Y-m-d') ? 'var(--sa-secondary)' : 'var(--sa-text3)' }}">
                                {{ $data === $hoje->format('Y-m-d') ? '✦ Hoje' : \Carbon\Carbon::parse($data)->translatedFormat('D, d/m') }}
                            </span>
                            <div style="flex:1;height:1px;background:var(--sa-border)"></div>
                        </div>
                        @foreach($agendamentos as $ag)
                        @php $dot = $statusDot[$ag->status] ?? '#999'; @endphp
                        <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:10px;padding-left:6px">
                            <div style="position:relative;flex-shrink:0;margin-top:10px;z-index:1">
                                <div style="width:20px;height:20px;border-radius:50%;background:{{ $dot }}18;border:2px solid {{ $dot }};display:flex;align-items:center;justify-content:center">
                                    <div style="width:6px;height:6px;border-radius:50%;background:{{ $dot }}"></div>
                                </div>
                            </div>
                            <div style="flex:1;background:var(--sa-surface2);border-radius:10px;padding:10px 12px;border:1px solid var(--sa-border);border-left:3px solid {{ $dot }}">
                                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                                    <div style="display:flex;align-items:center;gap:8px;flex:1;min-width:0">
                                        <x-sa.avatar :name="$ag->cliente?->name ?? '?'" :size="28" :color="$dot" />
                                        <div style="min-width:0">
                                            <div style="font-size:13px;font-weight:700;color:var(--sa-text1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $ag->cliente?->name ?? '—' }}</div>
                                            <div style="font-size:11px;color:var(--sa-text3);margin-top:1px;display:flex;align-items:center;gap:4px">
                                                {{ $ag->servico?->nome ?? '—' }}
                                                <span style="opacity:.4">·</span>
                                                @php $profCor = $stats['profissionais']->firstWhere('id', $ag->profissional_id)?->cor ?? '#888'; @endphp
                                                <x-sa.avatar :name="$ag->profissional?->name ?? '?'" :size="14" :color="$profCor" />
                                                <span>{{ explode(' ', $ag->profissional?->name ?? '—')[0] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="flex-shrink:0;text-align:right">
                                        <div style="font-family:var(--sa-font-heading);font-size:13px;font-weight:800;color:var(--sa-secondary)">{{ $ag->data_hora->format('H:i') }}</div>
                                        <x-sa.badge :status="$ag->status" :label="ucfirst($ag->status)" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endforeach
                    @if($stats['proximosAgendamentos']->count() >= 8)
                    <div style="padding-left:36px;margin-top:8px">
                        <a href="{{ route('agendamentos.index') }}" style="font-size:13px;font-weight:600;color:var(--sa-secondary);text-decoration:none;display:inline-flex;align-items:center;gap:4px">
                            Ver agenda completa
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                    </div>
                    @endif
                    @endif
                </div>
            </x-sa.card>

            {{-- Painel direito --}}
            <div style="display:flex;flex-direction:column;gap:16px">
                {{-- Donut --}}
                <x-sa.card padding="22px">
                    <h4 style="font-family:var(--sa-font-heading);font-size:14px;font-weight:600;margin:0 0 20px;display:flex;align-items:center;gap:8px">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                        Status dos Agendamentos
                    </h4>
                    <div class="sa-donut-wrap">
                        <div style="position:relative;flex-shrink:0;width:160px;height:160px">
                            <svg class="sa-donut-svg" width="160" height="160" viewBox="0 0 160 160">
                                <circle cx="80" cy="80" r="{{ $donutR }}" fill="none" stroke="var(--sa-surface2)" stroke-width="18"/>
                                @php $donutCum = 0; @endphp
                                @foreach($stats['donut']['segments'] as $seg)
                                @php
                                    $dashLen = ($seg['pct'] / 100) * $donutCirc;
                                    $offset = -($donutCum / 100) * $donutCirc;
                                    $donutCum += $seg['pct'];
                                @endphp
                                <circle cx="80" cy="80" r="{{ $donutR }}" fill="none" stroke="{{ $seg['color'] }}" stroke-width="18" stroke-linecap="round"
                                    stroke-dasharray="{{ $dashLen }} {{ $donutCirc }}" stroke-dashoffset="{{ $offset }}"/>
                                @endforeach
                            </svg>
                            <div class="sa-donut-center">
                                <div style="font-family:var(--sa-font-heading);font-size:26px;font-weight:800;line-height:1">{{ $stats['donut']['total'] }}</div>
                                <div style="font-size:10px;color:var(--sa-text3);font-weight:600;letter-spacing:.5px;margin-top:2px">TOTAL</div>
                            </div>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:16px;flex:1">
                            @foreach($stats['donut']['segments'] as $seg)
                            <div>
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px">
                                    <div style="display:flex;align-items:center;gap:8px">
                                        <div style="width:8px;height:8px;border-radius:50%;background:{{ $seg['color'] }}"></div>
                                        <span style="font-size:12px;color:var(--sa-text2);font-weight:500">{{ $seg['label'] }}</span>
                                    </div>
                                    <span style="font-family:var(--sa-font-heading);font-size:15px;font-weight:700">{{ $seg['pct'] }}%</span>
                                </div>
                                <div style="height:4px;border-radius:2px;background:var(--sa-surface2);overflow:hidden">
                                    <div style="height:100%;border-radius:2px;background:{{ $seg['color'] }};width:{{ $seg['pct'] }}%"></div>
                                </div>
                                <div style="font-size:11px;color:var(--sa-text3);margin-top:3px">{{ $seg['count'] }} agendamentos</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </x-sa.card>

                {{-- Resumo de Hoje --}}
                <x-sa.card padding="20px">
                    <h4 style="font-family:var(--sa-font-heading);font-size:14px;font-weight:600;margin:0 0 14px">Resumo de Hoje</h4>
                    @php
                        $resumo = [
                            ['label' => 'Agendamentos', 'value' => $stats['agendamentosHoje'], 'icon' => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>'],
                            ['label' => 'Receita Prevista', 'value' => 'R$ '.number_format($stats['receitaPrevistaHoje'], 2, ',', '.'), 'icon' => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>'],
                            ['label' => 'Confirmados', 'value' => $stats['confirmadosHoje'], 'icon' => '<polyline points="20 6 9 17 4 12"/>'],
                            ['label' => 'Pendentes', 'value' => $stats['pendentesHoje'], 'icon' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
                        ];
                    @endphp
                    @foreach($resumo as $i => $item)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:9px 0;{{ $i < count($resumo)-1 ? 'border-bottom:1px solid var(--sa-border)' : '' }}">
                        <div style="display:flex;align-items:center;gap:8px">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2">{!! $item['icon'] !!}</svg>
                            <span style="font-size:13px;color:var(--sa-text2)">{{ $item['label'] }}</span>
                        </div>
                        <span style="font-family:var(--sa-font-heading);font-size:15px;font-weight:800">{{ $item['value'] }}</span>
                    </div>
                    @endforeach
                </x-sa.card>

                {{-- Por Profissional --}}
                <x-sa.card padding="20px">
                    <h4 style="font-family:var(--sa-font-heading);font-size:14px;font-weight:600;margin:0 0 14px">Por Profissional</h4>
                    @forelse($stats['profissionais'] as $prof)
                    <div style="margin-bottom:12px">
                        <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                            <span style="font-size:12px;color:var(--sa-text2);font-weight:500">{{ explode(' ', $prof->name)[0] }}</span>
                            <span style="font-size:12px;font-weight:700">{{ $prof->agendamentos_mes_count }}</span>
                        </div>
                        <div style="height:6px;border-radius:3px;background:var(--sa-surface2);overflow:hidden">
                            <div style="height:100%;border-radius:3px;background:{{ $prof->cor }};width:{{ min(($prof->agendamentos_mes_count / max($stats['maxProfCount'], 20)) * 100, 100) }}%"></div>
                        </div>
                    </div>
                    @empty
                    <p style="font-size:13px;color:var(--sa-text3);margin:0">Nenhum profissional cadastrado.</p>
                    @endforelse
                </x-sa.card>
            </div>
        </div>
    </x-sa.body>
</x-sa.page>

@endif
@endsection

@push('scripts')
@if($stats)
<script>
function dashboardKanbanApp() {
    return {
        cards: @json($stats['kanbanCards']),
        cols: [
            { id: 'pendente',       label: 'Aguardando',     color: '#f59e0b' },
            { id: 'confirmado',     label: 'Confirmado',     color: '#6366f1' },
            { id: 'em_atendimento', label: 'Em atendimento', color: '#0ea5e9' },
            { id: 'finalizado',     label: 'Concluído',      color: '#10b981' },
            { id: 'cancelado',      label: 'Cancelado',      color: '#ef4444' },
        ],
        dragging: null,
        dragOverCol: null,
        cardsFor(status) {
            return this.cards.filter(c => c.status === status);
        },
        startDrag(card) {
            if (!card.canEdit) return;
            this.dragging = card;
        },
        async drop(status) {
            if (!this.dragging) return;
            const card = this.dragging;
            this.dragging = null;
            this.dragOverCol = null;
            if (card.status === status) return;
            const colLabel = this.cols.find(c => c.id === status)?.label || status;
            const result = await Swal.fire({
                title: 'Mover atendimento?',
                text: `Confirma mover "${card.cliente}" para "${colLabel}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim, mover',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#1a1a1a',
                cancelButtonColor: 'transparent',
                customClass: { cancelButton: 'swal-cancel-muted' },
            });
            if (!result.isConfirmed) return;
            const oldStatus = card.status;
            card.status = status;
            try {
                const res = await fetch(card.statusUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ status }),
                });
                if (!res.ok) {
                    card.status = oldStatus;
                    Swal.fire({ title: 'Erro', text: 'Não foi possível atualizar o status.', icon: 'error' });
                    return;
                }
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: `Movido para "${colLabel}"`, timer: 2000, showConfirmButton: false });
            } catch {
                card.status = oldStatus;
                Swal.fire({ title: 'Erro', text: 'Falha de conexão.', icon: 'error' });
            }
        },
    };
}
</script>
@endif
@endpush
