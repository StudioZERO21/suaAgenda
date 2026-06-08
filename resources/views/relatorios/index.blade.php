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
@endphp

<x-sa.page>
    <x-sa.app-header
        title="Relatórios"
        subtitle="Análise completa do desempenho do negócio — {{ $inicio->format('d/m/Y') }} a {{ $fim->format('d/m/Y') }}" />
    <x-sa.body padding="16px 32px 0">

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
            <input type="date" name="de" value="{{ $request->input('de', $inicio->format('Y-m-d')) }}" class="sa-search-input" style="width:auto;padding:5px 8px;font-size:12px">
            <span style="font-size:11px;color:var(--sa-text3)">a</span>
            <input type="date" name="ate" value="{{ $request->input('ate', $fim->format('Y-m-d')) }}" class="sa-search-input" style="width:auto;padding:5px 8px;font-size:12px">
            <button type="submit" name="preset" value="custom" class="sa-btn sa-btn--primary sa-btn--sm">Filtrar</button>
        </div>
        @endif
    </form>

    <div class="sa-grid-4" style="margin-bottom:20px">
        <x-sa.tint-card label="Receita Total" :value="'R$ ' . number_format((float)$receitaTotal, 2, ',', '.')" accent="var(--sa-secondary)" :sub="$totalFinalizados . ' finalizados'"
            :icon="'<svg width=\'130\' height=\'130\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'var(--sa-secondary)\' stroke-width=\'1.5\'><line x1=\'12\' y1=\'1\' x2=\'12\' y2=\'23\'/><path d=\'M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6\'/></svg>'" />
        <x-sa.tint-card label="Agendamentos" :value="$totalAgendamentos" sub="no período"
            :icon="'<svg width=\'130\' height=\'130\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'var(--sa-primary)\' stroke-width=\'1.5\'><rect x=\'3\' y=\'4\' width=\'18\' height=\'18\' rx=\'2\'/><line x1=\'16\' y1=\'2\' x2=\'16\' y2=\'6\'/><line x1=\'8\' y1=\'2\' x2=\'8\' y2=\'6\'/><line x1=\'3\' y1=\'10\' x2=\'21\' y2=\'10\'/></svg>'" />
        <x-sa.tint-card label="Ticket Médio" :value="'R$ ' . number_format((float)$ticketMedio, 2, ',', '.')" accent="var(--sa-secondary)" sub="por atendimento"
            :icon="'<svg width=\'130\' height=\'130\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'var(--sa-secondary)\' stroke-width=\'1.5\'><polyline points=\'23 6 13.5 15.5 8.5 10.5 1 18\'/></svg>'" />
        <x-sa.tint-card label="Novos Clientes" :value="$novosClientes" accent="#10b981" sub="cadastrados no período"
            :icon="'<svg width=\'130\' height=\'130\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'#10b981\' stroke-width=\'1.5\'><path d=\'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2\'/><circle cx=\'9\' cy=\'7\' r=\'4\'/></svg>'" />
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
        <x-sa.card style="padding:24px">
            <h2 style="font-family:var(--sa-font-heading);font-size:14px;font-weight:700;color:var(--sa-text1);margin:0 0 20px">Receita por Serviço</h2>
            @forelse($receitaPorServico as $item)
            @php $pct = $maxServico > 0 ? ($item['total'] / $maxServico) * 100 : 0; @endphp
            <div style="margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                    <div style="display:flex;align-items:center;gap:8px">
                        <span style="width:8px;height:8px;border-radius:50%;background:{{ $item['cor'] }}"></span>
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

        <x-sa.card style="padding:24px">
            <h2 style="font-family:var(--sa-font-heading);font-size:14px;font-weight:700;color:var(--sa-text1);margin:0 0 20px">Por Profissional</h2>
            @forelse($agendamentosPorProfissional as $item)
            @php $pct = $maxProfissional > 0 ? ($item['total'] / $maxProfissional) * 100 : 0; @endphp
            <div style="margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                    <div style="display:flex;align-items:center;gap:8px">
                        <x-sa.avatar :name="$item['name']" :size="26" />
                        <div>
                            <div style="font-size:13px;font-weight:600">{{ $item['name'] }}</div>
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
    </x-sa.body>
</x-sa.page>
@endsection
