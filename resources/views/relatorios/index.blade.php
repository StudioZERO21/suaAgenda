@extends('layouts.app')
@section('title', 'Relatórios')

@section('content')
@php
    $preset  = $request->input('preset', '30d');
    $presets = [
        '7d'     => '7 dias',
        '30d'    => '30 dias',
        '3m'     => '3 meses',
        '6m'     => '6 meses',
        'mes'    => 'Este mês',
        'custom' => 'Personalizado',
    ];
    $margemPct = $receitaBruta > 0 ? round($lucroLiquido / $receitaBruta * 100) : 0;
@endphp

<x-sa.page x-data="{ tab: 'overview' }">
    <x-sa.app-header
        title="Relatórios"
        subtitle="Análise completa do desempenho — {{ $inicio->format('d/m/Y') }} a {{ $fim->format('d/m/Y') }}" />
    <x-sa.body padding="16px 32px 0">

    {{-- Date range filter --}}
    <form method="GET" id="form-relatorio" style="background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:10px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2" style="flex-shrink:0"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <div style="display:flex;gap:4px;flex-wrap:wrap">
            @foreach($presets as $key => $label)
            <button type="submit" name="preset" value="{{ $key }}"
                    style="padding:5px 12px;border-radius:7px;border:none;cursor:pointer;font-size:12px;font-family:var(--sa-font-body);transition:all 150ms;{{ $preset === $key ? 'background:var(--sa-primary);color:#fff;font-weight:700' : 'background:var(--sa-surface2);color:var(--sa-text2);font-weight:500' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>
        @if($preset === 'custom')
        <div style="display:flex;align-items:center;gap:6px;margin-left:auto">
            <input type="date" name="de" value="{{ $request->input('de', $inicio->format('Y-m-d')) }}" style="padding:5px 8px;font-size:12px;border:1px solid var(--sa-border);border-radius:7px;background:var(--sa-surface);color:var(--sa-text1);font-family:var(--sa-font-body)">
            <span style="font-size:11px;color:var(--sa-text3)">a</span>
            <input type="date" name="ate" value="{{ $request->input('ate', $fim->format('Y-m-d')) }}" style="padding:5px 8px;font-size:12px;border:1px solid var(--sa-border);border-radius:7px;background:var(--sa-surface);color:var(--sa-text1);font-family:var(--sa-font-body)">
            <button type="submit" name="preset" value="custom" style="padding:5px 12px;border-radius:7px;border:none;cursor:pointer;font-size:12px;font-family:var(--sa-font-body);background:var(--sa-primary);color:#fff;font-weight:600">Filtrar</button>
        </div>
        @endif
    </form>

    {{-- Tab navigation --}}
    <div style="display:flex;gap:4px;border-bottom:1px solid var(--sa-border);margin-bottom:20px">
        @foreach([
            ['overview',       'Visão Geral',   '<path d=\'M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z\'/>'],
            ['receita',        'Receita',        '<line x1=\'12\' y1=\'1\' x2=\'12\' y2=\'23\'/><path d=\'M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6\'/>'],
            ['profissionais',  'Profissionais',  '<path d=\'M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2\'/><circle cx=\'12\' cy=\'7\' r=\'4\'/>'],
        ] as [$id, $label, $path])
        <button type="button" @click="tab = '{{ $id }}'"
            :style="tab === '{{ $id }}'
                ? 'border-bottom:2px solid var(--sa-primary);color:var(--sa-primary);font-weight:600;background:transparent;border-left:none;border-right:none;border-top:none;'
                : 'border-bottom:2px solid transparent;color:var(--sa-text3);font-weight:500;background:transparent;border-left:none;border-right:none;border-top:none;'"
            style="display:flex;align-items:center;gap:7px;padding:9px 16px;cursor:pointer;font-size:13px;font-family:var(--sa-font-body);margin-bottom:-1px;transition:all 160ms">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">{!! $path !!}</svg>
            {{ $label }}
        </button>
        @endforeach
    </div>

    {{-- ── VISÃO GERAL ────────────────────────────────────────── --}}
    <div x-show="tab === 'overview'" x-cloak>
        <div class="sa-grid-4" style="margin-bottom:20px">
            <x-sa.tint-card label="Receita Bruta" :value="'R$ ' . number_format($receitaBruta, 2, ',', '.')" accent="var(--sa-secondary)" :sub="$totalFinalizados . ' finalizados'"
                :icon="'<svg width=\'130\' height=\'130\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'var(--sa-secondary)\' stroke-width=\'1.5\'><line x1=\'12\' y1=\'1\' x2=\'12\' y2=\'23\'/><path d=\'M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6\'/></svg>'" />
            <x-sa.tint-card label="Lucro Líquido" :value="'R$ ' . number_format($lucroLiquido, 2, ',', '.')" accent="{{ $lucroLiquido >= 0 ? '#10b981' : '#ef4444' }}" :sub="'margem ' . $margemPct . '%'"
                :icon="'<svg width=\'130\' height=\'130\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'var(--sa-primary)\' stroke-width=\'1.5\'><polyline points=\'23 6 13.5 15.5 8.5 10.5 1 18\'/></svg>'" />
            <x-sa.tint-card label="Agendamentos" :value="$totalAgendamentos" sub="no período"
                :icon="'<svg width=\'130\' height=\'130\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'var(--sa-primary)\' stroke-width=\'1.5\'><rect x=\'3\' y=\'4\' width=\'18\' height=\'18\' rx=\'2\'/><line x1=\'16\' y1=\'2\' x2=\'16\' y2=\'6\'/><line x1=\'8\' y1=\'2\' x2=\'8\' y2=\'6\'/><line x1=\'3\' y1=\'10\' x2=\'21\' y2=\'10\'/></svg>'" />
            <x-sa.tint-card label="Novos Clientes" :value="$novosClientes" accent="#10b981" sub="cadastrados no período"
                :icon="'<svg width=\'130\' height=\'130\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'#10b981\' stroke-width=\'1.5\'><path d=\'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2\'/><circle cx=\'9\' cy=\'7\' r=\'4\'/></svg>'" />
        </div>

        <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;margin-bottom:20px">
            {{-- Receita por Serviço --}}
            <x-sa.card style="padding:24px">
                <h2 style="font-family:var(--sa-font-heading);font-size:14px;font-weight:700;color:var(--sa-text1);margin:0 0 20px">Receita por Serviço</h2>
                @forelse($receitaPorServico as $item)
                @php $pct = $maxServico > 0 ? ($item['total'] / $maxServico) * 100 : 0; @endphp
                <div style="margin-bottom:14px">
                    <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="width:8px;height:8px;border-radius:50%;background:{{ $item['cor'] }};flex-shrink:0"></span>
                            <span style="font-size:13px;color:var(--sa-text1)">{{ $item['nome'] }}</span>
                        </div>
                        <div style="text-align:right">
                            <span style="font-size:13px;font-weight:700">R$ {{ number_format($item['total'], 2, ',', '.') }}</span>
                            <span style="font-size:11px;color:var(--sa-text3);margin-left:6px">{{ $item['quantidade'] }}x</span>
                        </div>
                    </div>
                    <div style="height:8px;border-radius:4px;background:var(--sa-surface2);overflow:hidden">
                        <div style="height:100%;border-radius:4px;background:{{ $item['cor'] }};width:{{ $pct }}%"></div>
                    </div>
                </div>
                @empty
                <p style="font-size:14px;color:var(--sa-text3);text-align:center;padding:24px 0">Nenhum serviço finalizado no período.</p>
                @endforelse
            </x-sa.card>

            {{-- Resumo Financeiro --}}
            <x-sa.card style="padding:22px">
                <h2 style="font-family:var(--sa-font-heading);font-size:14px;font-weight:700;color:var(--sa-text1);margin:0 0 16px">Resumo Financeiro</h2>
                @php
                    $resumo = [
                        ['label' => 'Receita agendamentos', 'value' => 'R$ ' . number_format($receitaAgendamentos, 2, ',', '.'), 'color' => 'var(--sa-secondary)'],
                        ['label' => 'Receita manual',       'value' => 'R$ ' . number_format($receitaLancamentos, 2, ',', '.'), 'color' => 'var(--sa-secondary)'],
                        ['label' => 'Total despesas',       'value' => 'R$ ' . number_format($totalDespesas, 2, ',', '.'), 'color' => '#ef4444'],
                        ['label' => 'Lucro líquido',        'value' => 'R$ ' . number_format($lucroLiquido, 2, ',', '.'), 'color' => $lucroLiquido >= 0 ? '#10b981' : '#ef4444'],
                        ['label' => 'Ticket médio',         'value' => 'R$ ' . number_format($ticketMedio, 2, ',', '.'), 'color' => 'var(--sa-text1)'],
                        ['label' => 'Novos clientes',       'value' => $novosClientes, 'color' => 'var(--sa-text1)'],
                    ];
                @endphp
                @foreach($resumo as $i => $row)
                <div style="display:flex;justify-content:space-between;padding:9px 0;{{ $i < count($resumo) - 1 ? 'border-bottom:1px solid var(--sa-border)' : '' }}">
                    <span style="font-size:13px;color:var(--sa-text3)">{{ $row['label'] }}</span>
                    <span style="font-size:13px;font-weight:700;color:{{ $row['color'] }}">{{ $row['value'] }}</span>
                </div>
                @endforeach

                @if($receitaBruta > 0)
                <div style="margin-top:14px;padding-top:12px;border-top:2px solid var(--sa-border)">
                    <div style="display:flex;justify-content:space-between;margin-bottom:6px">
                        <span style="font-size:11px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.4px">Margem</span>
                        <span style="font-size:15px;font-weight:800;font-family:var(--sa-font-heading);color:{{ $lucroLiquido >= 0 ? '#10b981' : '#ef4444' }}">{{ $margemPct }}%</span>
                    </div>
                    <div style="height:6px;border-radius:3px;background:var(--sa-surface2);overflow:hidden">
                        <div style="height:100%;border-radius:3px;background:{{ $lucroLiquido >= 0 ? '#10b981' : '#ef4444' }};width:{{ max(0, min(100, $margemPct)) }}%"></div>
                    </div>
                </div>
                @endif
            </x-sa.card>
        </div>

        @if($totalAgendamentos > 0)
        <x-sa.card style="padding:24px">
            @php
                $pctFinalizado = round($totalFinalizados / $totalAgendamentos * 100);
                $pctPendente   = round(($totalAgendamentos - $totalFinalizados) / $totalAgendamentos * 100);
            @endphp
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
                <h2 style="font-family:var(--sa-font-heading);font-size:14px;font-weight:700;margin:0">Taxa de Conclusão</h2>
                <span style="font-size:24px;font-weight:800;font-family:var(--sa-font-heading)">{{ $pctFinalizado }}%</span>
            </div>
            <div style="height:12px;border-radius:6px;background:var(--sa-surface2);overflow:hidden;display:flex;gap:2px">
                <div style="height:100%;background:#10b981;width:{{ $pctFinalizado }}%;border-radius:6px 0 0 6px"></div>
                <div style="height:100%;background:var(--sa-border);flex:1;border-radius:0 6px 6px 0"></div>
            </div>
            <div style="display:flex;gap:20px;margin-top:10px">
                <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--sa-text2)">
                    <span style="width:8px;height:8px;border-radius:50%;background:#10b981"></span>
                    {{ $totalFinalizados }} finalizados ({{ $pctFinalizado }}%)
                </span>
                <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--sa-text2)">
                    <span style="width:8px;height:8px;border-radius:50%;background:var(--sa-border)"></span>
                    {{ $totalAgendamentos - $totalFinalizados }} outros ({{ $pctPendente }}%)
                </span>
            </div>
        </x-sa.card>
        @endif
    </div>

    {{-- ── RECEITA & DESPESAS ──────────────────────────────────── --}}
    <div x-show="tab === 'receita'" x-cloak>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            {{-- Receita por Serviço --}}
            <x-sa.card style="padding:24px">
                <h2 style="font-family:var(--sa-font-heading);font-size:14px;font-weight:700;color:var(--sa-text1);margin:0 0 20px">Receita por Serviço</h2>
                @forelse($receitaPorServico as $item)
                @php $pct = $maxServico > 0 ? ($item['total'] / $maxServico) * 100 : 0; @endphp
                <div style="margin-bottom:14px">
                    <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="width:8px;height:8px;border-radius:50%;background:{{ $item['cor'] }};flex-shrink:0"></span>
                            <span style="font-size:13px;color:var(--sa-text1)">{{ $item['nome'] }}</span>
                        </div>
                        <div>
                            <span style="font-size:13px;font-weight:700">R$ {{ number_format($item['total'], 2, ',', '.') }}</span>
                            <span style="font-size:11px;color:var(--sa-text3);margin-left:6px">{{ $item['quantidade'] }}x</span>
                        </div>
                    </div>
                    <div style="height:8px;border-radius:4px;background:var(--sa-surface2);overflow:hidden">
                        <div style="height:100%;border-radius:4px;background:{{ $item['cor'] }};width:{{ $pct }}%"></div>
                    </div>
                </div>
                @empty
                <p style="font-size:14px;color:var(--sa-text3);text-align:center;padding:24px 0">Nenhum serviço finalizado no período.</p>
                @endforelse
            </x-sa.card>

            {{-- Despesas por Categoria --}}
            <x-sa.card style="padding:24px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
                    <h2 style="font-family:var(--sa-font-heading);font-size:14px;font-weight:700;color:var(--sa-text1);margin:0">Despesas por Categoria</h2>
                    <span style="font-size:13px;font-weight:700;color:#ef4444">R$ {{ number_format($totalDespesas, 2, ',', '.') }}</span>
                </div>
                @php $despesaCores = ['#ef4444','#f59e0b','#6366f1','#ec4899','#0ea5e9','#14b8a6','#8b5cf6']; @endphp
                @forelse($despesasPorCategoria as $i => $item)
                @php $pct = $maxDespesa > 0 ? ($item['total'] / $maxDespesa) * 100 : 0;
                     $cor = $despesaCores[$i % count($despesaCores)]; @endphp
                <div style="margin-bottom:14px">
                    <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="width:8px;height:8px;border-radius:50%;background:{{ $cor }};flex-shrink:0"></span>
                            <span style="font-size:13px;color:var(--sa-text1)">{{ $item['categoria'] }}</span>
                        </div>
                        <div>
                            <span style="font-size:13px;font-weight:700">R$ {{ number_format($item['total'], 2, ',', '.') }}</span>
                            <span style="font-size:11px;color:var(--sa-text3);margin-left:6px">{{ $item['quantidade'] }}x</span>
                        </div>
                    </div>
                    <div style="height:8px;border-radius:4px;background:var(--sa-surface2);overflow:hidden">
                        <div style="height:100%;border-radius:4px;background:{{ $cor }};width:{{ $pct }}%"></div>
                    </div>
                </div>
                @empty
                <p style="font-size:14px;color:var(--sa-text3);text-align:center;padding:24px 0">Nenhuma despesa lançada no período.</p>
                @endforelse

                @if($totalDespesas > 0 || $receitaBruta > 0)
                <div style="margin-top:20px;padding-top:14px;border-top:1px solid var(--sa-border)">
                    @php $margem = $receitaBruta > 0 ? max(0, min(100, round($lucroLiquido / $receitaBruta * 100))) : 0; @endphp
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                        <span style="font-size:12px;color:var(--sa-text3)">Receita vs. Despesas</span>
                        <span style="font-size:13px;font-weight:700;color:{{ $lucroLiquido >= 0 ? '#10b981' : '#ef4444' }}">
                            {{ $lucroLiquido >= 0 ? '+' : '' }}R$ {{ number_format($lucroLiquido, 2, ',', '.') }}
                        </span>
                    </div>
                    <div style="height:8px;border-radius:4px;background:rgba(239,68,68,.15);overflow:hidden">
                        <div style="height:100%;border-radius:4px;background:#10b981;width:{{ $margem }}%"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-top:6px">
                        <span style="font-size:10px;color:#10b981;font-weight:600">Receita {{ $margem }}%</span>
                        <span style="font-size:10px;color:#ef4444;font-weight:600">Despesas {{ 100 - $margem }}%</span>
                    </div>
                </div>
                @endif
            </x-sa.card>
        </div>
    </div>

    {{-- ── PROFISSIONAIS ───────────────────────────────────────── --}}
    <div x-show="tab === 'profissionais'" x-cloak>
        <x-sa.card style="padding:24px">
            <h2 style="font-family:var(--sa-font-heading);font-size:14px;font-weight:700;color:var(--sa-text1);margin:0 0 20px">Desempenho por Profissional</h2>
            @forelse($agendamentosPorProfissional as $item)
            @php $pct = $maxProfissional > 0 ? ($item['total'] / $maxProfissional) * 100 : 0; @endphp
            <div style="margin-bottom:18px">
                <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                    <div style="display:flex;align-items:center;gap:8px">
                        <x-sa.avatar :name="$item['name']" :size="28" />
                        <div>
                            <div style="font-size:13px;font-weight:600;color:var(--sa-text1)">{{ $item['name'] }}</div>
                            <div style="font-size:11px;color:var(--sa-text3)">{{ $item['quantidade'] }} atend. · {{ $item['finalizados'] }} finalizados</div>
                        </div>
                    </div>
                    <span style="font-size:13px;font-weight:700;white-space:nowrap">R$ {{ number_format($item['total'], 2, ',', '.') }}</span>
                </div>
                <div style="height:6px;border-radius:3px;background:var(--sa-surface2);overflow:hidden">
                    <div style="height:100%;border-radius:3px;background:var(--sa-primary);width:{{ $pct }}%"></div>
                </div>
            </div>
            @empty
            <p style="font-size:14px;color:var(--sa-text3);text-align:center;padding:24px 0">Nenhum agendamento no período.</p>
            @endforelse
        </x-sa.card>
    </div>

    </x-sa.body>
</x-sa.page>
@endsection
