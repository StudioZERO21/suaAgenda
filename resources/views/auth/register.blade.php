@extends('layouts.auth')

@section('title', 'Criar Conta')

@section('content')

{{-- Tabs --}}
<div class="sa-tabs">
    <a href="{{ route('login') }}" class="sa-tab">Entrar</a>
    <a href="{{ route('register') }}" class="sa-tab active">Criar conta</a>
</div>

<h2 style="font-size:24px;font-weight:700;color:var(--sa-text1);margin:0 0 6px">Crie sua conta</h2>
<p style="font-size:14px;color:var(--sa-text3);margin:0 0 28px">7 dias grátis, sem cartão de crédito</p>

<form method="POST" action="{{ route('register') }}" style="display:flex;flex-direction:column;gap:16px">
    @csrf

    <div>
        <label class="sa-label" for="name">
            Nome completo <span style="color:#ef4444;margin-left:2px">*</span>
        </label>
        <div class="sa-field">
            <span class="sa-field-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </span>
            <input type="text" name="name" id="name" value="{{ old('name') }}"
                   class="sa-input {{ $errors->has('name') ? 'is-error' : '' }}"
                   placeholder="Seu nome completo" required autofocus>
        </div>
    </div>

    <div>
        <label class="sa-label" for="company_name">
            Nome da empresa <span style="color:#ef4444;margin-left:2px">*</span>
        </label>
        <div class="sa-field">
            <span class="sa-field-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 9a3 3 0 100-6 3 3 0 000 6z"/>
                    <path d="M6 9c-1.657 0-3 .895-3 2v1h3.5"/>
                    <line x1="14.47" y1="14.48" x2="20" y2="20"/>
                    <line x1="8.12" y1="8.12" x2="12" y2="12"/>
                    <circle cx="18" cy="18" r="3"/>
                </svg>
            </span>
            <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}"
                   class="sa-input {{ $errors->has('company_name') ? 'is-error' : '' }}"
                   placeholder="Ex: Barbearia Central" required>
        </div>
    </div>

    <div>
        <label class="sa-label" for="email">
            E-mail <span style="color:#ef4444;margin-left:2px">*</span>
        </label>
        <div class="sa-field">
            <span class="sa-field-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
            </span>
            <input type="email" name="email" id="email" value="{{ old('email') }}"
                   class="sa-input {{ $errors->has('email') ? 'is-error' : '' }}"
                   placeholder="seu@email.com" required>
        </div>
    </div>

    <div>
        <label class="sa-label" for="password">
            Senha <span style="color:#ef4444;margin-left:2px">*</span>
        </label>
        <div class="sa-field">
            <span class="sa-field-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0110 0v4"/>
                </svg>
            </span>
            <input type="password" name="password" id="password"
                   class="sa-input {{ $errors->has('password') ? 'is-error' : '' }}"
                   placeholder="Mínimo 6 caracteres" required>
        </div>
    </div>

    <div>
        <label class="sa-label" for="password_confirmation">
            Confirmar senha <span style="color:#ef4444;margin-left:2px">*</span>
        </label>
        <div class="sa-field">
            <span class="sa-field-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0110 0v4"/>
                </svg>
            </span>
            <input type="password" name="password_confirmation" id="password_confirmation"
                   class="sa-input" placeholder="Repita a senha" required>
        </div>
    </div>

    <div style="display:flex;align-items:flex-start;gap:10px;padding-top:4px">
        <input type="checkbox" name="lgpd_consent" id="lgpd_consent" value="1"
               style="width:15px;height:15px;margin-top:2px;accent-color:var(--sa-primary);cursor:pointer;flex-shrink:0"
               {{ old('lgpd_consent') ? 'checked' : '' }}>
        <label for="lgpd_consent" style="font-size:12px;color:var(--sa-text2);line-height:1.6;cursor:pointer">
            Concordo com os
            <a href="#" style="color:var(--sa-secondary);text-decoration:none;font-weight:600">Termos de Uso</a>
            e a
            <a href="#" style="color:var(--sa-secondary);text-decoration:none;font-weight:600">Política de Privacidade</a>
            (LGPD).
        </label>
    </div>

    <button type="submit" class="sa-btn-primary" style="margin-top:4px">
        Criar conta grátis
    </button>
</form>

<p style="font-size:13px;color:var(--sa-text3);text-align:center;margin:16px 0 0">
    Já tem conta?
    <a href="{{ route('login') }}" style="color:var(--sa-secondary);font-weight:600;text-decoration:none">Fazer login</a>
</p>

@endsection
