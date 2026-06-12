@extends('layouts.app')
@section('title', 'Painel do Sistema')
@section('page-title', 'Painel do Sistema')

@section('content')
{{-- AppHeader --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div>
        <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">Painel do Sistema <span style="color:var(--sa-secondary)">✦</span></h1>
        <p style="font-size:14px;color:var(--sa-text3);margin:0">Visão global do suaAgenda — empresas, uso e saúde</p>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
        <a href="{{ route('admin.empresas.index') }}"
           style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:var(--sa-primary);color:#fff;text-decoration:none;transition:filter 200ms"
           onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
            Ver empresas
        </a>
    </div>
</div>

{{-- Stat cards --}}
<div class="sa-grid-4" style="margin-bottom:20px">
    <div style="background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:148px;display:flex;flex-direction:column">
        <div style="font-size:11px;font-weight:700;color:var(--sa-primary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.75">EMPRESAS</div>
        <div style="font-family:'Poppins',sans-serif;font-size:32px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">{{ $totalEmpresas }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:8px">{{ $empresasAtivas }} ativas</div>
        <div style="position:absolute;bottom:-32px;right:-26px;opacity:.08;pointer-events:none">
            <svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg>
        </div>
    </div>
    <div style="background:color-mix(in srgb,var(--sa-secondary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-secondary) 14%,transparent);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:148px;display:flex;flex-direction:column">
        <div style="font-size:11px;font-weight:700;color:var(--sa-secondary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.9">TRIAL EXPIRANDO</div>
        <div style="font-family:'Poppins',sans-serif;font-size:32px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">{{ $trialExpirando }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:8px">próximos 7 dias</div>
        <div style="position:absolute;bottom:-32px;right:-26px;opacity:.08;pointer-events:none">
            <svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
    </div>
    <div style="background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:148px;display:flex;flex-direction:column">
        <div style="font-size:11px;font-weight:700;color:var(--sa-primary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.75">USUÁRIOS ATIVOS</div>
        <div style="font-family:'Poppins',sans-serif;font-size:32px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">{{ $usuariosAtivos }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:8px">em todas as empresas</div>
        <div style="position:absolute;bottom:-32px;right:-26px;opacity:.08;pointer-events:none">
            <svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        </div>
    </div>
    <div style="background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent);border-radius:16px;padding:22px 22px 0;position:relative;overflow:hidden;min-height:148px;display:flex;flex-direction:column">
        <div style="font-size:11px;font-weight:700;color:var(--sa-primary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.75">AGENDAMENTOS (30D)</div>
        <div style="font-family:'Poppins',sans-serif;font-size:32px;font-weight:800;color:var(--sa-text1);line-height:1;letter-spacing:-1px">{{ number_format($agendamentos30, 0, ',', '.') }}</div>
        <div style="font-size:12px;color:var(--sa-text3);margin-top:8px">volume global do sistema</div>
        <div style="position:absolute;bottom:-32px;right:-26px;opacity:.08;pointer-events:none">
            <svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
    </div>
</div>

<div class="sa-grid-2-360">
    <div style="display:flex;flex-direction:column;gap:20px;min-width:0">
        {{-- Série 30 dias --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <div style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin-bottom:18px">Agendamentos por dia — últimos 30 dias</div>
            <div style="display:flex;align-items:flex-end;gap:3px;height:120px">
                @foreach($serie30Dias as $dia)
                    <div title="{{ $dia['data'] }}: {{ $dia['total'] }}" style="flex:1;border-radius:3px 3px 0 0;background:{{ $loop->last ? 'var(--sa-secondary)' : 'color-mix(in srgb,var(--sa-primary) 30%,transparent)' }};height:{{ max(4, (int) round($dia['total'] / $maxSerie * 100)) }}%"></div>
                @endforeach
            </div>
        </div>

        {{-- Top empresas --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <div style="padding:18px 20px;border-bottom:1px solid var(--sa-border)">
                <div style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1)">Top empresas por volume (30 dias)</div>
            </div>
            @forelse($topEmpresas as $empresa)
                <a href="{{ route('admin.empresas.show', $empresa) }}" style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid var(--sa-border);text-decoration:none;transition:background 120ms"
                   onmouseover="this.style.background='var(--sa-surface2)'" onmouseout="this.style.background='transparent'">
                    <div style="width:34px;height:34px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;font-family:'Inter',sans-serif;flex-shrink:0">{{ strtoupper(substr($empresa->name, 0, 1)) }}</div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:14px;font-weight:600;color:var(--sa-text1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $empresa->name }}</div>
                        <div style="font-size:12px;color:var(--sa-text3)">{{ $empresa->plan_slug ?? 'sem plano' }}</div>
                    </div>
                    <div style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1)">{{ $empresa->agendamentos_30d }}</div>
                </a>
            @empty
                <p style="font-size:13px;color:var(--sa-text3);padding:20px;margin:0;text-align:center">Nenhuma empresa cadastrada.</p>
            @endforelse
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:20px">
        {{-- Distribuição por plano --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <div style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin-bottom:14px">Empresas por plano</div>
            @forelse($porPlano as $linha)
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--sa-border)">
                    <span style="font-size:13px;color:var(--sa-text2);text-transform:capitalize">{{ $linha['plano'] }}</span>
                    <span style="font-size:13px;font-weight:700;color:var(--sa-text1)">{{ $linha['total'] }}</span>
                </div>
            @empty
                <p style="font-size:13px;color:var(--sa-text3);margin:0">Sem dados.</p>
            @endforelse
        </div>

        {{-- Empresas recentes --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <div style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin-bottom:14px">Empresas recentes</div>
            @forelse($empresasRecentes as $empresa)
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--sa-border);gap:10px">
                    <a href="{{ route('admin.empresas.show', $empresa) }}" style="font-size:13px;color:var(--sa-text1);font-weight:600;text-decoration:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $empresa->name }}</a>
                    <span style="font-size:12px;color:var(--sa-text3);flex-shrink:0">{{ $empresa->created_at->format('d/m/Y') }}</span>
                </div>
            @empty
                <p style="font-size:13px;color:var(--sa-text3);margin:0">Sem cadastros recentes.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
