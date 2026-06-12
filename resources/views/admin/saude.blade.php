@extends('layouts.app')
@section('title', 'Saúde do Sistema')
@section('page-title', 'Saúde do Sistema')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div>
        <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">Saúde do Sistema</h1>
        <p style="font-size:14px;color:var(--sa-text3);margin:0">Filas, banco, armazenamento e atividade — {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</div>

<div style="max-width:1100px">
    <div class="sa-grid-4" style="margin-bottom:20px">
        <div style="background:{{ $failedJobs > 0 ? 'rgba(239,68,68,.08)' : 'color-mix(in srgb,var(--sa-primary) 8%,transparent)' }};border:1px solid {{ $failedJobs > 0 ? 'rgba(239,68,68,.25)' : 'color-mix(in srgb,var(--sa-primary) 14%,transparent)' }};border-radius:16px;padding:22px;min-height:110px">
            <div style="font-size:11px;font-weight:700;color:{{ $failedJobs > 0 ? '#dc2626' : 'var(--sa-primary)' }};letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.85">JOBS COM FALHA</div>
            <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--sa-text1);line-height:1">{{ $failedJobs }}</div>
        </div>
        <div style="background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent);border-radius:16px;padding:22px;min-height:110px">
            <div style="font-size:11px;font-weight:700;color:var(--sa-primary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.75">FILA PENDENTE</div>
            <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--sa-text1);line-height:1">{{ $jobsPendentes }}</div>
        </div>
        <div style="background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent);border-radius:16px;padding:22px;min-height:110px">
            <div style="font-size:11px;font-weight:700;color:var(--sa-primary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.75">SESSÕES ATIVAS (30MIN)</div>
            <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--sa-text1);line-height:1">{{ $sessoesAtivas }}</div>
        </div>
        <div style="background:color-mix(in srgb,var(--sa-secondary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-secondary) 14%,transparent);border-radius:16px;padding:22px;min-height:110px">
            <div style="font-size:11px;font-weight:700;color:var(--sa-secondary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.9">ATIVIDADES (24H)</div>
            <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--sa-text1);line-height:1">{{ number_format($atividades24h, 0, ',', '.') }}</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px">
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <div style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin-bottom:14px">Armazenamento e banco</div>
            @foreach([
                'Banco de dados' => $tamanhoBancoMb !== null ? $tamanhoBancoMb.' MB' : 'n/d ('.config('database.default').')',
                'Storage (uploads)' => $storageMb.' MB',
                'Último agendamento criado' => $ultimoAgendamento?->diffForHumans() ?? 'nunca',
            ] as $label => $valor)
            <div style="display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--sa-border)">
                <span style="font-size:13px;color:var(--sa-text2)">{{ $label }}</span>
                <span style="font-size:13px;font-weight:700;color:var(--sa-text1)">{{ $valor }}</span>
            </div>
            @endforeach
        </div>

        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <div style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin-bottom:14px">Autenticação (24h)</div>
            <div style="display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--sa-border)">
                <span style="font-size:13px;color:var(--sa-text2)">Logins com sucesso</span>
                <span style="font-size:13px;font-weight:700;color:#059669">{{ $logins24h }}</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--sa-border)">
                <span style="font-size:13px;color:var(--sa-text2)">Tentativas falhas</span>
                <span style="font-size:13px;font-weight:700;color:{{ $loginsFalhos24h > 10 ? '#dc2626' : 'var(--sa-text1)' }}">{{ $loginsFalhos24h }}</span>
            </div>
        </div>

        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <div style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin-bottom:14px">Ambiente</div>
            @foreach([
                'PHP' => $phpVersion,
                'Laravel' => $laravelVersion,
                'Ambiente' => $ambiente,
            ] as $label => $valor)
            <div style="display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--sa-border)">
                <span style="font-size:13px;color:var(--sa-text2)">{{ $label }}</span>
                <span style="font-size:13px;font-weight:700;color:var(--sa-text1)">{{ $valor }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
