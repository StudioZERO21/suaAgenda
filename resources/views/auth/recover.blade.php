@extends('layouts.auth')

@section('title', 'Recuperar conta')
@section('authBare', true)

@section('content')
<div x-data="recoverWizard()" x-cloak>
    {{-- Logo --}}
    <div style="text-align:center;margin-bottom:32px">
        <div style="width:52px;height:52px;border-radius:14px;background:var(--sa-primary);display:flex;align-items:center;justify-content:center;margin:0 auto 14px">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
        </div>
        <div style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:800;color:var(--sa-text1)">suaAgenda<span style="color:var(--sa-secondary)">.pro</span></div>
    </div>

    {{-- Step indicator --}}
    <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:28px">
        <template x-for="(s, i) in [1,2,3]" :key="s">
            <div style="display:flex;align-items:center;gap:8px">
                <div :style="dotStyle(s)"
                     style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;transition:all 300ms">
                    <span x-text="step > s ? '✓' : s"></span>
                </div>
                <div x-show="i < 2" :style="'background:' + (step > s + 1 ? 'var(--sa-secondary)' : step === s + 1 ? 'var(--sa-primary)' : 'var(--sa-border)')"
                     style="width:40px;height:2px;border-radius:1px;transition:background 300ms"></div>
            </div>
        </template>
    </div>

    {{-- Card --}}
    <div x-show="!done" style="background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:18px;padding:32px;box-shadow:0 8px 32px rgba(0,0,0,.08)">

        {{-- STEP 1: Email --}}
        <div x-show="step === 1">
            <h2 style="font-family:'Poppins',sans-serif;font-size:20px;font-weight:700;color:var(--sa-text1);margin:0 0 6px">Recuperar conta</h2>
            <p style="font-size:14px;color:var(--sa-text3);margin:0 0 24px;line-height:1.6">Digite o e-mail cadastrado. Enviaremos um código de verificação.</p>

            <label class="sa-label" for="rec-email">E-mail <span style="color:#ef4444;margin-left:2px">*</span></label>
            <div class="sa-field">
                <span class="sa-field-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </span>
                <input type="email" id="rec-email" x-model="email" class="sa-input" placeholder="seu@email.com" required>
            </div>

            <button type="button" class="sa-btn-primary" style="margin-top:20px" :disabled="loading" @click="submitEmail()">
                <span x-show="!loading">Enviar código</span>
                <span x-show="loading">Enviando...</span>
            </button>
            <a href="{{ route('login') }}" style="display:block;text-align:center;width:100%;margin-top:14px;font-size:13px;color:var(--sa-text3);text-decoration:none">&larr; Voltar ao login</a>
        </div>

        {{-- STEP 2: Code --}}
        <div x-show="step === 2">
            <h2 style="font-family:'Poppins',sans-serif;font-size:20px;font-weight:700;color:var(--sa-text1);margin:0 0 6px">Verifique seu e-mail</h2>
            <p style="font-size:14px;color:var(--sa-text3);margin:0 0 8px;line-height:1.6">Enviamos um código de 6 dígitos para:</p>
            <div style="font-size:14px;font-weight:700;color:var(--sa-secondary);margin-bottom:24px" x-text="email"></div>

            <div style="display:flex;gap:10px;justify-content:center;margin-bottom:24px">
                <template x-for="(c, i) in code" :key="i">
                    <input :id="'code-' + i" x-model="code[i]" maxlength="1" type="text" inputmode="numeric"
                           @input="handleCode(i, $event)" @keydown.backspace="handleBackspace(i, $event)"
                           :style="'border:2px solid ' + (code[i] ? 'var(--sa-primary)' : 'var(--sa-border)')"
                           style="width:44px;height:52px;text-align:center;font-size:22px;font-weight:800;border-radius:10px;background:var(--sa-surface2);color:var(--sa-text1);font-family:'Poppins',sans-serif;outline:none;transition:border 150ms">
                </template>
            </div>

            <button type="button" class="sa-btn-primary" :disabled="loading || codeStr.length < 6" @click="submitCode()">
                <span x-show="!loading">Verificar código</span>
                <span x-show="loading">Verificando...</span>
            </button>
            <div style="text-align:center;margin-top:16px;font-size:13px;color:var(--sa-text3)">
                Não recebeu?
                <button type="button" @click="resend()" style="background:none;border:none;cursor:pointer;color:var(--sa-secondary);font-weight:600;font-size:13px;font-family:inherit">Reenviar</button>
            </div>
        </div>

        {{-- STEP 3: New password --}}
        <div x-show="step === 3">
            <h2 style="font-family:'Poppins',sans-serif;font-size:20px;font-weight:700;color:var(--sa-text1);margin:0 0 6px">Nova senha</h2>
            <p style="font-size:14px;color:var(--sa-text3);margin:0 0 24px;line-height:1.6">Crie uma senha forte para proteger sua conta.</p>

            <div style="display:flex;flex-direction:column;gap:14px">
                <div>
                    <label class="sa-label">Nova senha <span style="color:#ef4444;margin-left:2px">*</span></label>
                    <div class="sa-field">
                        <span class="sa-field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></span>
                        <input type="password" x-model="pwdNew" class="sa-input" placeholder="Mínimo 6 caracteres">
                    </div>
                    <div x-show="pwdNew.length > 0" style="margin-top:8px">
                        <div style="height:4px;border-radius:2px;background:var(--sa-surface2);overflow:hidden">
                            <div :style="'width:' + strengthPct + '%;background:' + strengthColor" style="height:100%;border-radius:2px;transition:all 300ms"></div>
                        </div>
                        <div :style="'color:' + strengthColor" style="font-size:11px;margin-top:4px;font-weight:600" x-text="'Senha ' + strength"></div>
                    </div>
                </div>
                <div>
                    <label class="sa-label">Confirmar nova senha <span style="color:#ef4444;margin-left:2px">*</span></label>
                    <div class="sa-field">
                        <span class="sa-field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></span>
                        <input type="password" x-model="pwdConfirm" class="sa-input" placeholder="Repita a senha">
                    </div>
                    <div x-show="pwdConfirm && pwdNew !== pwdConfirm" style="font-size:12px;color:#ef4444;margin-top:6px">Senhas não coincidem</div>
                </div>
            </div>

            <button type="button" class="sa-btn-primary" style="margin-top:20px" :disabled="loading || pwdNew !== pwdConfirm || pwdNew.length < 6" @click="submitPwd()">
                <span x-show="!loading">Redefinir senha</span>
                <span x-show="loading">Salvando...</span>
            </button>
        </div>
    </div>

    {{-- SUCCESS --}}
    <div x-show="done" x-cloak style="background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:18px;padding:40px;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,.08)">
        <div style="width:64px;height:64px;border-radius:50%;background:rgba(16,185,129,.12);display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <h2 style="font-family:'Poppins',sans-serif;font-size:20px;font-weight:700;color:var(--sa-text1);margin:0 0 10px">Senha redefinida!</h2>
        <p style="font-size:14px;color:var(--sa-text3);margin:0 0 28px;line-height:1.7">Sua senha foi atualizada com sucesso. Agora você pode fazer login com a nova senha.</p>
        <a href="{{ route('login') }}" class="sa-btn-primary" style="text-decoration:none">Ir para o login</a>
    </div>
</div>

@push('scripts')
<script>
function recoverWizard() {
    return {
        step: 1,
        email: '',
        code: ['', '', '', '', '', ''],
        pwdNew: '',
        pwdConfirm: '',
        loading: false,
        done: false,

        get codeStr() { return this.code.join(''); },
        get strength() {
            const p = this.pwdNew;
            if (p.length >= 8 && /[A-Z]/.test(p) && /[0-9]/.test(p)) return 'forte';
            if (p.length >= 6) return 'média';
            if (p.length > 0) return 'fraca';
            return '';
        },
        get strengthColor() {
            return { forte: '#10b981', 'média': '#f59e0b', fraca: '#ef4444' }[this.strength] || 'transparent';
        },
        get strengthPct() {
            return { forte: 100, 'média': 60, fraca: 30 }[this.strength] || 0;
        },
        dotStyle(s) {
            const bg = this.step > s ? 'var(--sa-secondary)' : this.step === s ? 'var(--sa-primary)' : 'var(--sa-surface2)';
            const color = this.step >= s ? '#fff' : 'var(--sa-text3)';
            const border = this.step > s ? 'var(--sa-secondary)' : this.step === s ? 'var(--sa-primary)' : 'var(--sa-border)';
            return `background:${bg};color:${color};border:2px solid ${border}`;
        },
        toast(title, icon) {
            Swal.fire({ toast: true, position: 'top-end', icon, title, showConfirmButton: false, timer: 2200, timerProgressBar: true });
        },
        submitEmail() {
            if (!this.email.includes('@')) return this.toast('E-mail inválido', 'error');
            this.loading = true;
            setTimeout(() => { this.loading = false; this.step = 2; this.toast('Código enviado para seu e-mail!', 'success'); }, 900);
        },
        handleCode(i, e) {
            this.code[i] = e.target.value.slice(-1);
            if (e.target.value && i < 5) document.getElementById('code-' + (i + 1))?.focus();
        },
        handleBackspace(i, e) {
            if (!this.code[i] && i > 0) document.getElementById('code-' + (i - 1))?.focus();
        },
        submitCode() {
            if (this.codeStr.length < 6) return this.toast('Digite o código completo', 'error');
            this.loading = true;
            setTimeout(() => { this.loading = false; this.step = 3; }, 700);
        },
        resend() { this.toast('Código reenviado!', 'success'); },
        submitPwd() {
            if (this.pwdNew.length < 6) return this.toast('Senha muito curta (mínimo 6 caracteres)', 'error');
            if (this.pwdNew !== this.pwdConfirm) return this.toast('Senhas não coincidem', 'error');
            this.loading = true;
            setTimeout(() => { this.loading = false; this.done = true; }, 800);
        },
    };
}
</script>
@endpush
@endsection
