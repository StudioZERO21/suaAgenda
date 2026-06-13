@extends('layouts.portal')
@section('title', 'Meus dados')

@section('content')
<div style="margin-bottom:16px">
    <a href="{{ route('portal.dashboard', $company->slug) }}" style="font-size:13px;color:var(--sa-text3);text-decoration:none">← Voltar</a>
</div>

<h1 style="font-family:'Poppins',sans-serif;font-size:20px;font-weight:700;margin:0 0 18px">Meus dados e privacidade</h1>

<div style="background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:14px;padding:20px;margin-bottom:16px">
    <div style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;margin-bottom:14px">Cadastro</div>
    @foreach(['Nome' => $cliente->name, 'Telefone' => $cliente->phone ?? '—', 'E-mail' => $cliente->email ?? '—', 'Nascimento' => $cliente->data_nasc?->format('d/m/Y') ?? '—'] as $label => $valor)
    <div style="display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--sa-border)">
        <span style="font-size:13px;color:var(--sa-text3)">{{ $label }}</span>
        <span style="font-size:13px;color:var(--sa-text1);font-weight:600">{{ $valor }}</span>
    </div>
    @endforeach
</div>

<div style="background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:14px;padding:20px;margin-bottom:16px">
    <div style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;margin-bottom:8px">Privacidade (LGPD)</div>
    <p style="font-size:13px;color:var(--sa-text3);margin:0 0 16px;line-height:1.6">Você pode baixar todos os seus dados ou ajustar seu consentimento a qualquer momento.</p>

    <a href="{{ route('portal.dados.exportar', $company->slug) }}"
       style="display:flex;align-items:center;gap:8px;padding:12px 16px;border-radius:8px;border:1.5px solid var(--sa-border);text-decoration:none;color:var(--sa-text1);font-size:14px;font-weight:600;margin-bottom:12px">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Baixar meus dados (JSON)
    </a>

    <form method="POST" action="{{ route('portal.dados.consentimento', $company->slug) }}">
        @csrf
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:10px 0">
            <input type="checkbox" name="consent" value="1" {{ $cliente->lgpd_consent ? 'checked' : '' }} onchange="this.form.submit()">
            <span style="font-size:13px;color:var(--sa-text2);line-height:1.5">Autorizo o uso dos meus dados para comunicações e histórico de atendimento.</span>
        </label>
    </form>
</div>
@endsection
