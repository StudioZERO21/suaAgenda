@extends('layouts.app')
@section('title', 'Relatórios')
@section('page-title', 'Relatórios')

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


    {{-- Cabeçalho --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
        <div>
            <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">Relatórios</h1>
            <p style="font-size:14px;color:var(--sa-text3);margin:0">{{ $inicio->format('d/m/Y') }} — {{ $fim->format('d/m/Y') }}</p>
        </div>
    </div>

    {{-- Filtros de período --}}
    <form method="GET" id="form-relatorio" style="background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:10px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <div style="display:flex;gap:4px;flex-wrap:wrap">
            @foreach($presets as $key => $label)
            <button type="submit" name="preset" value="{{ $key }}"
                    style="padding:5px 12px;border-radius:7px;border:none;cursor:pointer;font-size:12px;font-family:'Inter',sans-serif;transition:all 150ms;
                           {{ $preset === $key ? 'background:var(--sa-primary);color:#fff;font-weight:700' : 'background:var(--sa-surface2);color:var(--sa-text2);font-weight:500' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>
        @if($preset === 'custom')
        <div style="display:flex;align-items:center;gap:6px;margin-left:auto">
            <input type="date" name="de" value="{{ $request->input('de', $inicio->format('Y-m-d')) }}"
                   style="font-size:12px;padding:5px 8px;border:1.5px solid var(--sa-border);border-radius:7px;background:var(--sa-surface);color:var(--sa-text1);outline:none;transition:border-color 180ms,outline 180ms"
                   onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
            <span style="font-size:11px;color:var(--sa-text3)">a</span>
            <input type="date" name="ate" value="{{ $request->input('ate', $fim->format('Y-m-d')) }}"
                   style="font-size:12px;padding:5px 8px;border:1.5px solid var(--sa-border);border-radius:7px;background:var(--sa-surface);color:var(--sa-text1);outline:none;transition:border-color 180ms,outline 180ms"
                   onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
            <button type="submit" name="preset" value="custom"
                    style="padding:5px 12px;border-radius:7px;border:none;cursor:pointer;font-size:12px;font-weight:600;font-family:'Inter',sans-serif;background:var(--sa-primary);color:#fff;transition:filter 200ms"
                    onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">Filtrar</button>
        </div>
        @endif
    </form>

    {{-- KPI Cards (TintCard) --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px">

        <div style="background:color-mix(in srgb,var(--sa-secondary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-secondary) 14%,transparent);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:130px;display:flex;flex-direction:column">
            <div style="font-size:11px;font-weight:700;color:var(--sa-secondary);letter-spacing:1px;text-transform:uppercase;margin-bottom:10px;opacity:.85">Receita Total</div>
            <div style="font-family:'Poppins',sans-serif;font-size:24px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-0.5px">R$ {{ number_format((float)$receitaTotal, 2, ',', '.') }}</div>
            <div style="font-size:12px;color:var(--sa-text3);margin-top:6px">{{ $totalFinalizados }} finalizados</div>
            <div style="position:absolute;bottom:-20px;right:-16px;opacity:.1;pointer-events:none">
                <svg width="90" height="90" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            </div>
        </div>

        <div style="background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:130px;display:flex;flex-direction:column">
            <div style="font-size:11px;font-weight:700;color:var(--sa-primary);letter-spacing:1px;text-transform:uppercase;margin-bottom:10px;opacity:.75">Agendamentos</div>
            <div style="font-family:'Poppins',sans-serif;font-size:34px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">{{ $totalAgendamentos }}</div>
            <div style="font-size:12px;color:var(--sa-text3);margin-top:6px">no período</div>
            <div style="position:absolute;bottom:-20px;right:-16px;opacity:.08;pointer-events:none">
                <svg width="90" height="90" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
        </div>

        <div style="background:color-mix(in srgb,var(--sa-secondary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-secondary) 14%,transparent);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:130px;display:flex;flex-direction:column">
            <div style="font-size:11px;font-weight:700;color:var(--sa-secondary);letter-spacing:1px;text-transform:uppercase;margin-bottom:10px;opacity:.85">Ticket Médio</div>
            <div style="font-family:'Poppins',sans-serif;font-size:24px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-0.5px">R$ {{ number_format((float)$ticketMedio, 2, ',', '.') }}</div>
            <div style="font-size:12px;color:var(--sa-text3);margin-top:6px">por atendimento</div>
            <div style="position:absolute;bottom:-20px;right:-16px;opacity:.1;pointer-events:none">
                <svg width="90" height="90" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            </div>
        </div>

        <div style="background:color-mix(in srgb,#10b981 8%,transparent);border:1px solid color-mix(in srgb,#10b981 14%,transparent);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:130px;display:flex;flex-direction:column">
            <div style="font-size:11px;font-weight:700;color:#059669;letter-spacing:1px;text-transform:uppercase;margin-bottom:10px;opacity:.75">Novos Clientes</div>
            <div style="font-family:'Poppins',sans-serif;font-size:34px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">{{ $novosClientes }}</div>
            <div style="font-size:12px;color:var(--sa-text3);margin-top:6px">cadastrados no período</div>
            <div style="position:absolute;bottom:-20px;right:-16px;opacity:.08;pointer-events:none">
                <svg width="90" height="90" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
            </div>
        </div>

    </div>

    {{-- Linha 2: Receita por serviço + Por profissional --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">

        {{-- Receita por serviço --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <h2 style="font-size:14px;font-weight:700;color:var(--sa-text1);margin:0 0 20px">Receita por Serviço</h2>
            @forelse($receitaPorServico as $item)
            @php $pct = $maxServico > 0 ? ($item['total'] / $maxServico) * 100 : 0; @endphp
            <div style="margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                    <div style="display:flex;align-items:center;gap:8px">
                        <span style="width:8px;height:8px;border-radius:50%;background:{{ $item['cor'] }};flex-shrink:0"></span>
                        <span style="font-size:13px;color:var(--sa-text1)">{{ $item['nome'] }}</span>
                    </div>
                    <div style="text-align:right">
                        <span style="font-size:13px;font-weight:700;color:var(--sa-text1)">R$ {{ number_format($item['total'], 2, ',', '.') }}</span>
                        <span style="font-size:11px;color:var(--sa-text3);margin-left:6px">{{ $item['quantidade'] }}x</span>
                    </div>
                </div>
                <div style="height:8px;border-radius:4px;background:var(--sa-surface2);overflow:hidden">
                    <div style="height:100%;border-radius:4px;background:{{ $item['cor'] }};width:{{ $pct }}%;transition:width 600ms ease"></div>
                </div>
            </div>
            @empty
            <p style="font-size:14px;color:var(--sa-text3);margin:0;text-align:center;padding:24px 0">Nenhum serviço finalizado no período.</p>
            @endforelse
        </div>

        {{-- Agendamentos por profissional --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <h2 style="font-size:14px;font-weight:700;color:var(--sa-text1);margin:0 0 20px">Por Profissional</h2>
            @forelse($agendamentosPorProfissional as $item)
            @php $pct = $maxProfissional > 0 ? ($item['total'] / $maxProfissional) * 100 : 0; @endphp
            <div style="margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="width:26px;height:26px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0">{{ strtoupper(substr($item['name'], 0, 1)) }}</div>
                        <div>
                            <div style="font-size:13px;font-weight:600;color:var(--sa-text1)">{{ $item['name'] }}</div>
                            <div style="font-size:11px;color:var(--sa-text3)">{{ $item['quantidade'] }} atend. · {{ $item['finalizados'] }} finalizados</div>
                        </div>
                    </div>
                    <span style="font-size:13px;font-weight:700;color:var(--sa-text1);white-space:nowrap">R$ {{ number_format($item['total'], 2, ',', '.') }}</span>
                </div>
                <div style="height:6px;border-radius:3px;background:var(--sa-surface2);overflow:hidden">
                    <div style="height:100%;border-radius:3px;background:var(--sa-primary);width:{{ $pct }}%;transition:width 600ms ease"></div>
                </div>
            </div>
            @empty
            <p style="font-size:14px;color:var(--sa-text3);margin:0;text-align:center;padding:24px 0">Nenhum agendamento no período.</p>
            @endforelse
        </div>

    </div>

    {{-- Taxa de conclusão --}}
    @if($totalAgendamentos > 0)
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        @php
            $pctFinalizado = round($totalFinalizados / $totalAgendamentos * 100);
            $pctPendente   = round(($totalAgendamentos - $totalFinalizados) / $totalAgendamentos * 100);
        @endphp
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
            <h2 style="font-size:14px;font-weight:700;color:var(--sa-text1);margin:0">Taxa de Conclusão</h2>
            <span style="font-size:24px;font-weight:800;color:var(--sa-text1);font-family:'Poppins',sans-serif">{{ $pctFinalizado }}%</span>
        </div>
        <div style="height:12px;border-radius:6px;background:var(--sa-surface2);overflow:hidden;display:flex;gap:2px">
            <div style="height:100%;background:#10b981;width:{{ $pctFinalizado }}%;border-radius:6px 0 0 6px;transition:width 800ms ease"></div>
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
    </div>
    @endif
@endsection
