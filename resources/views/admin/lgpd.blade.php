@extends('layouts.app')
@section('title', 'LGPD')
@section('page-title', 'LGPD')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div>
        <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">Compliance LGPD</h1>
        <p style="font-size:14px;color:var(--sa-text3);margin:0">Consentimento, portabilidade e direito ao esquecimento por empresa</p>
    </div>
</div>

<div style="max-width:1100px">
    <div class="sa-grid-3" style="margin-bottom:20px">
        <div style="background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent);border-radius:16px;padding:22px;min-height:110px">
            <div style="font-size:11px;font-weight:700;color:var(--sa-primary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.75">EXPORTAÇÕES (30D)</div>
            <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--sa-text1);line-height:1">{{ $totais['exportacoes_30d'] }}</div>
        </div>
        <div style="background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent);border-radius:16px;padding:22px;min-height:110px">
            <div style="font-size:11px;font-weight:700;color:var(--sa-primary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.75">ANONIMIZAÇÕES (30D)</div>
            <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--sa-text1);line-height:1">{{ $totais['anonimizacoes_30d'] }}</div>
        </div>
        <div style="background:{{ $totais['clientes_sem_consent'] > 0 ? 'rgba(245,158,11,.08)' : 'color-mix(in srgb,var(--sa-primary) 8%,transparent)' }};border:1px solid {{ $totais['clientes_sem_consent'] > 0 ? 'rgba(245,158,11,.25)' : 'color-mix(in srgb,var(--sa-primary) 14%,transparent)' }};border-radius:16px;padding:22px;min-height:110px">
            <div style="font-size:11px;font-weight:700;color:{{ $totais['clientes_sem_consent'] > 0 ? '#d97706' : 'var(--sa-primary)' }};letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.85">SEM CONSENTIMENTO</div>
            <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--sa-text1);line-height:1">{{ number_format($totais['clientes_sem_consent'], 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="sa-grid-2-360">
        {{-- Consent por empresa --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <div style="padding:18px 20px;border-bottom:1px solid var(--sa-border)">
                <div style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1)">Consentimento por empresa</div>
            </div>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;min-width:480px">
                    <thead>
                        <tr style="background:var(--sa-surface2);border-bottom:1px solid var(--sa-border)">
                            <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Empresa</th>
                            <th style="padding:11px 16px;text-align:right;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Clientes</th>
                            <th style="padding:11px 16px;text-align:right;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Consent</th>
                            <th style="padding:11px 16px;text-align:right;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Anonim.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($consentPorEmpresa as $linha)
                        <tr style="border-bottom:1px solid var(--sa-border)">
                            <td style="padding:12px 16px;font-size:13px;color:var(--sa-text1);font-weight:600">{{ $linha['empresa'] }}</td>
                            <td style="padding:12px 16px;font-size:13px;color:var(--sa-text2);text-align:right">{{ $linha['total_clientes'] }}</td>
                            <td style="padding:12px 16px;text-align:right">
                                <span style="font-size:12px;font-weight:700;color:{{ $linha['pct_consent'] >= 80 ? '#059669' : ($linha['pct_consent'] >= 50 ? '#d97706' : '#dc2626') }}">{{ $linha['pct_consent'] }}%</span>
                            </td>
                            <td style="padding:12px 16px;font-size:13px;color:var(--sa-text2);text-align:right">{{ $linha['anonimizados'] }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" style="padding:24px 16px;text-align:center;font-size:13px;color:var(--sa-text3)">Sem empresas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Atividades LGPD recentes --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05);align-self:start">
            <div style="padding:18px 20px;border-bottom:1px solid var(--sa-border)">
                <div style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1)">Atividades recentes</div>
            </div>
            @forelse($atividadesLgpd as $atividade)
                <div style="padding:12px 20px;border-bottom:1px solid var(--sa-border)">
                    <div style="display:flex;justify-content:space-between;gap:10px;margin-bottom:3px">
                        <span style="display:inline-flex;align-items:center;gap:5px;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;{{ $atividade['evento'] === 'anonimizado' ? 'background:rgba(239,68,68,.1);color:#dc2626' : 'background:rgba(99,102,241,.12);color:#6366f1' }}">
                            <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                            {{ $atividade['evento'] }}
                        </span>
                        <span style="font-size:11px;color:var(--sa-text3);flex-shrink:0">{{ $atividade['quando'] }}</span>
                    </div>
                    <div style="font-size:12px;color:var(--sa-text2)">{{ $atividade['descricao'] }} — {{ $atividade['causer'] }}</div>
                </div>
            @empty
                <p style="font-size:13px;color:var(--sa-text3);padding:20px;margin:0;text-align:center">Nenhuma atividade LGPD registrada.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
