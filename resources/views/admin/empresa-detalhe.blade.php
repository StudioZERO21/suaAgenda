@extends('layouts.app')
@section('title', $empresa->name)
@section('page-title', 'Empresa')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
    <div>
        <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">{{ $empresa->name }}</h1>
        <p style="font-size:14px;color:var(--sa-text3);margin:0">{{ $empresa->slug }} · cadastrada em {{ $empresa->created_at->format('d/m/Y') }}</p>
    </div>
    <a href="{{ route('admin.empresas.index') }}"
       style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;text-decoration:none;transition:border-color 180ms,color 180ms"
       onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
       onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
        ← Voltar
    </a>
</div>

<div style="max-width:1100px">
    <div class="sa-grid-4" style="margin-bottom:20px">
        <div style="background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent);border-radius:16px;padding:22px;min-height:110px">
            <div style="font-size:11px;font-weight:700;color:var(--sa-primary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.75">USUÁRIOS</div>
            <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--sa-text1);line-height:1">{{ $empresa->users_count }}</div>
        </div>
        <div style="background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent);border-radius:16px;padding:22px;min-height:110px">
            <div style="font-size:11px;font-weight:700;color:var(--sa-primary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.75">AGENDAMENTOS (TOTAL)</div>
            <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--sa-text1);line-height:1">{{ $empresa->agendamentos_count }}</div>
        </div>
        <div style="background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent);border-radius:16px;padding:22px;min-height:110px">
            <div style="font-size:11px;font-weight:700;color:var(--sa-primary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.75">AGENDAMENTOS (30D)</div>
            <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--sa-text1);line-height:1">{{ $agendamentos30 }}</div>
        </div>
        <div style="background:color-mix(in srgb,var(--sa-secondary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-secondary) 14%,transparent);border-radius:16px;padding:22px;min-height:110px">
            <div style="font-size:11px;font-weight:700;color:var(--sa-secondary);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;opacity:.9">RECEITA (30D)</div>
            <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--sa-text1);line-height:1">R$ {{ number_format($receita30, 2, ',', '.') }}</div>
        </div>
    </div>

    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <div style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin-bottom:14px">Dados da empresa</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
            @foreach([
                'Plano' => $empresa->plan_slug ?? '—',
                'E-mail' => $empresa->email ?? '—',
                'Telefone' => $empresa->phone ?? '—',
                'WhatsApp' => $empresa->whatsapp ?? '—',
                'Segmento' => $empresa->segment ?? '—',
                'Trial até' => $empresa->trial_ends_at?->format('d/m/Y') ?? '—',
                'LGPD consent' => $empresa->lgpd_consent ? 'Sim' : 'Não',
                'Status' => $empresa->ativo ? 'Ativa' : 'Inativa',
            ] as $label => $valor)
            <div>
                <div style="font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px">{{ $label }}</div>
                <div style="font-size:14px;color:var(--sa-text1)">{{ $valor }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
