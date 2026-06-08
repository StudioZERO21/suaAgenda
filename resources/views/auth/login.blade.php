@extends('layouts.auth')

@section('title', 'Login')

@section('content')

{{-- Tabs --}}
<div class="sa-tabs">
    <a href="{{ route('login') }}" class="sa-tab active">Entrar</a>
    <a href="{{ route('register') }}" class="sa-tab">Criar conta</a>
</div>

<h2 style="font-size:24px;font-weight:700;color:var(--sa-text1);margin:0 0 6px">Bem-vindo de volta</h2>
<p style="font-size:14px;color:var(--sa-text3);margin:0 0 28px">Acesse sua conta para continuar</p>

<form method="POST" action="{{ route('login') }}" style="display:flex;flex-direction:column;gap:16px">
    @csrf

    <div>
        <label class="sa-label" for="email">
            E-mail <span style="color:#ef4444;margin-left:2px">*</span>
        </label>
        <div class="sa-field">
            <span class="sa-field-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
            </span>
            <input type="email" name="email" id="email" value="{{ old('email') }}"
                   class="sa-input {{ $errors->has('email') ? 'is-error' : '' }}"
                   placeholder="seu@email.com" required autofocus>
        </div>
    </div>

    <div>
        <label class="sa-label" for="password">
            Senha <span style="color:#ef4444;margin-left:2px">*</span>
        </label>
        <div class="sa-field">
            <span class="sa-field-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                </svg>
            </span>
            <input type="password" name="password" id="password"
                   class="sa-input {{ $errors->has('password') ? 'is-error' : '' }}"
                   placeholder="Sua senha" required>
        </div>
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--sa-text2)">
            <input type="checkbox" name="remember" style="width:14px;height:14px;accent-color:var(--sa-primary);cursor:pointer">
            Lembrar-me
        </label>
        <a href="{{ route('password.request') }}" style="font-size:13px;color:var(--sa-secondary);font-weight:600;text-decoration:none">Esqueci a senha</a>
    </div>

    <button type="submit" class="sa-btn-primary" style="margin-top:4px">
        Entrar
    </button>

    <div class="sa-divider">ou continue com</div>

    <div style="display:flex;gap:10px">
        <button type="button" class="sa-btn-outline">Google</button>
        <button type="button" class="sa-btn-outline">Apple</button>
    </div>
</form>

<p style="font-size:13px;color:var(--sa-text3);text-align:center;margin:16px 0 0">
    Não tem conta?
    <a href="{{ route('register') }}" style="color:var(--sa-secondary);font-weight:600;text-decoration:none">Cadastre-se grátis</a>
</p>

{{-- ── Dev Quick Login (apenas ambiente local) ──────────────── --}}
@if(app()->isLocal() && isset($devUsers) && $devUsers->count())
<div class="dev-panel">
    <div class="dev-panel-title">⚡ Login Rápido — Desenvolvimento</div>
    <div style="display:flex;flex-direction:column;gap:6px">
        @foreach($devUsers as $user)
        @php
            $role     = $user->roles->first();
            $roleName = $role?->name ?? '';
            $bgColor  = match($roleName) {
                'super_admin'   => '#7c3aed',
                'admin_empresa' => '#1a1a1a',
                'gestor'        => '#0369a1',
                'analista'      => '#059669',
                default         => '#64748b',
            };
            $words    = explode(' ', trim($user->name));
            $initials = strtoupper(substr($words[0] ?? '', 0, 1)) . strtoupper(substr($words[1] ?? '', 0, 1));
        @endphp
        <form method="POST" action="{{ route('dev.login') }}" style="margin:0">
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <button type="submit" class="dev-user-btn">
                <div class="dev-avatar" style="background:{{ $bgColor }}">{{ $initials }}</div>
                <div style="flex:1;min-width:0">
                    <div class="dev-user-name">{{ $user->name }}</div>
                    <div class="dev-user-email">{{ $user->email }}</div>
                </div>
                @if($role)
                <div class="dev-badge">{{ $role->name }}</div>
                @endif
            </button>
        </form>
        @endforeach
    </div>
</div>
@endif

@endsection
