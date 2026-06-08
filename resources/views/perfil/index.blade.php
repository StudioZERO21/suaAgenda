@extends('layouts.app')
@section('title', 'Meu Perfil')
@section('page-title', 'Meu Perfil')

@section('content')
<div style="max-width:640px">

    @if(session('success'))
    <div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);border-radius:10px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        <span style="font-size:14px;color:#059669;font-weight:500">{{ session('success') }}</span>
    </div>
    @endif

    {{-- Avatar + nome --}}
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px;box-shadow:0 1px 3px rgba(0,0,0,.05);margin-bottom:20px;display:flex;align-items:center;gap:20px">
        <div style="width:72px;height:72px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;font-family:'Inter',sans-serif;flex-shrink:0">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <div>
            <div style="font-family:'Poppins',sans-serif;font-size:20px;font-weight:700;color:var(--sa-text1)">{{ $user->name }}</div>
            <div style="font-size:13px;color:var(--sa-text3);margin-top:2px">{{ $user->email }}</div>
            <div style="margin-top:6px">
                <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;background:rgba(107,114,128,.12);color:#6b7280">
                    <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                    {{ $user->getRoleNames()->first() ?? 'Usuário' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Formulário --}}
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <form method="POST" action="{{ route('perfil.update') }}" style="display:flex;flex-direction:column;gap:20px">
            @csrf @method('PUT')

            <h2 style="font-size:14px;font-weight:700;color:var(--sa-text1);margin:0">Dados Pessoais</h2>

            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
                    Nome <span style="color:var(--sa-secondary)">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                       style="width:100%;padding:10px 13px;border:1.5px solid {{ $errors->has('name') ? '#ef4444' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                       onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                @error('name')<p style="font-size:12px;color:#ef4444;margin-top:4px">{{ $message }}</p>@enderror
            </div>

            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
                    E-mail <span style="color:var(--sa-secondary)">*</span>
                </label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                       style="width:100%;padding:10px 13px;border:1.5px solid {{ $errors->has('email') ? '#ef4444' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                       onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                @error('email')<p style="font-size:12px;color:#ef4444;margin-top:4px">{{ $message }}</p>@enderror
            </div>

            <div style="border-top:1px solid var(--sa-border);padding-top:20px">
                <h2 style="font-size:14px;font-weight:700;color:var(--sa-text1);margin:0 0 16px">Alterar Senha</h2>
                <p style="font-size:13px;color:var(--sa-text3);margin:0 0 16px">Deixe em branco para manter a senha atual.</p>

                <div style="display:flex;flex-direction:column;gap:16px">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Senha Atual</label>
                        <input type="password" name="current_password" autocomplete="current-password"
                               style="width:100%;padding:10px 13px;border:1.5px solid {{ $errors->has('current_password') ? '#ef4444' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                               onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                        @error('current_password')<p style="font-size:12px;color:#ef4444;margin-top:4px">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Nova Senha</label>
                        <input type="password" name="password" autocomplete="new-password"
                               style="width:100%;padding:10px 13px;border:1.5px solid {{ $errors->has('password') ? '#ef4444' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                               onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                        @error('password')<p style="font-size:12px;color:#ef4444;margin-top:4px">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Confirmar Nova Senha</label>
                        <input type="password" name="password_confirmation" autocomplete="new-password"
                               style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                               onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:10px;padding-top:8px">
                <button type="submit"
                        style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:var(--sa-primary);color:#fff;transition:filter 200ms"
                        onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
