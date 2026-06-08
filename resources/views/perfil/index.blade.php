@extends('layouts.app')
@section('title', 'Meu Perfil')
@section('page-title', 'Meu Perfil')

@section('content')
<x-sa.page>
    <x-sa.app-header title="Meu Perfil" subtitle="Gerencie seus dados pessoais e prefer�ncias" />
    <x-sa.body padding="24px 32px 0">

    <x-sa.card padding="28px" style="margin-bottom:20px;display:flex;align-items:center;gap:20px">
        <div style="width:72px;height:72px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;font-family:var(--sa-font-body);flex-shrink:0">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <div>
            <div style="font-family:var(--sa-font-heading);font-size:20px;font-weight:700;color:var(--sa-text1)">{{ $user->name }}</div>
            <div style="font-size:13px;color:var(--sa-text3);margin-top:2px">{{ $user->email }}</div>
            <div style="margin-top:6px">
                <span style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;background:rgba(107,114,128,.12);color:#6b7280">
                    <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                    {{ $user->getRoleNames()->first() ?? 'Usu�rio' }}
                </span>
            </div>
        </div>
    </x-sa.card>

    <x-sa.card padding="28px">
        <form method="POST" action="{{ route('perfil.update') }}" style="display:flex;flex-direction:column;gap:20px">
            @csrf @method('PUT')

            <h2 style="font-size:14px;font-weight:700;color:var(--sa-text1);margin:0">Dados Pessoais</h2>

            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
                    Nome <span style="color:var(--sa-secondary)">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                       style="width:100%;padding:10px 13px;border:1.5px solid {{ $errors->has('name') ? '#ef4444' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;font-family:var(--sa-font-body);color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                       onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
            </div>

            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
                    E-mail <span style="color:var(--sa-secondary)">*</span>
                </label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                       style="width:100%;padding:10px 13px;border:1.5px solid {{ $errors->has('email') ? '#ef4444' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;font-family:var(--sa-font-body);color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                       onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
            </div>

            <div style="border-top:1px solid var(--sa-border);padding-top:20px">
                <h2 style="font-size:14px;font-weight:700;color:var(--sa-text1);margin:0 0 16px">Alterar Senha</h2>
                <p style="font-size:13px;color:var(--sa-text3);margin:0 0 16px">Deixe em branco para manter a senha atual.</p>

                <div style="display:flex;flex-direction:column;gap:16px">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Senha Atual</label>
                        <input type="password" name="current_password" autocomplete="current-password"
                               style="width:100%;padding:10px 13px;border:1.5px solid {{ $errors->has('current_password') ? '#ef4444' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;font-family:var(--sa-font-body);color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                               onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    </div>

                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Nova Senha</label>
                        <input type="password" name="password" autocomplete="new-password"
                               style="width:100%;padding:10px 13px;border:1.5px solid {{ $errors->has('password') ? '#ef4444' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;font-family:var(--sa-font-body);color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                               onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    </div>

                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Confirmar Nova Senha</label>
                        <input type="password" name="password_confirmation" autocomplete="new-password"
                               style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:var(--sa-font-body);color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                               onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:10px;padding-top:8px">
                <x-sa.btn type="submit">Salvar Altera��es</x-sa.btn>
            </div>
        </form>
    </x-sa.card>

    <x-sa.card padding="22px" style="margin-top:20px;background:rgba(239,68,68,.05);border-color:rgba(239,68,68,.15)">
        <h2 style="font-size:14px;font-weight:700;color:#dc2626;margin:0 0 6px">Zona de Perigo</h2>
        <p style="font-size:13px;color:var(--sa-text3);margin:0 0 14px;line-height:1.6">
            Ao sair voc� precisar� fazer login novamente.
        </p>
        <x-sa.logout-form variant="danger" />
    </x-sa.card>
    </x-sa.body>
</x-sa.page>
@endsection
