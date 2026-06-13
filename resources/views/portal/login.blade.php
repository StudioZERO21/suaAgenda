@extends('layouts.portal')
@section('title', 'Entrar')

@section('content')
<div style="max-width:420px;margin:20px auto 0">
    <div style="background:var(--sa-surface);border-radius:16px;border:1px solid var(--sa-border);padding:28px 24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        @if(session('enviado'))
            <div style="text-align:center">
                <div style="width:56px;height:56px;border-radius:50%;background:rgba(16,185,129,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                </div>
                <h1 style="font-family:'Poppins',sans-serif;font-size:19px;font-weight:700;margin:0 0 8px">Acesso enviado!</h1>
                <p style="font-size:14px;color:var(--sa-text2);line-height:1.6;margin:0 0 16px">
                    Se houver uma conta com esse contato, você receberá um link de acesso válido por 15 minutos.
                </p>
                @if(session('whatsapp_url'))
                <a href="{{ session('whatsapp_url') }}" target="_blank"
                   style="display:inline-flex;align-items:center;gap:7px;padding:11px 20px;border-radius:8px;background:#25d366;color:#fff;font-size:14px;font-weight:600;text-decoration:none">
                    Abrir no WhatsApp
                </a>
                @endif
                <div style="margin-top:14px">
                    <a href="{{ route('portal.entrar', $company->slug) }}" style="font-size:13px;color:var(--sa-text3);text-decoration:none">Usar outro contato</a>
                </div>
            </div>
        @else
            <h1 style="font-family:'Poppins',sans-serif;font-size:20px;font-weight:700;margin:0 0 6px">Acesse sua área</h1>
            <p style="font-size:13px;color:var(--sa-text3);margin:0 0 20px;line-height:1.6">Informe seu e-mail ou telefone cadastrado. Enviaremos um link de acesso — sem senha.</p>

            <form method="POST" action="{{ route('portal.enviar-link', $company->slug) }}" x-data="{ canal: 'email' }">
                @csrf
                <input type="hidden" name="canal" :value="canal">

                <div style="display:flex;gap:8px;margin-bottom:16px">
                    <button type="button" @click="canal = 'email'"
                            :style="canal === 'email' ? 'border-color:var(--sa-primary);color:var(--sa-text1);font-weight:600' : 'border-color:var(--sa-border);color:var(--sa-text3)'"
                            style="flex:1;padding:9px;border-radius:8px;border:1.5px solid;background:transparent;cursor:pointer;font-size:13px;font-family:'Inter',sans-serif">E-mail</button>
                    <button type="button" @click="canal = 'whatsapp'"
                            :style="canal === 'whatsapp' ? 'border-color:var(--sa-primary);color:var(--sa-text1);font-weight:600' : 'border-color:var(--sa-border);color:var(--sa-text3)'"
                            style="flex:1;padding:9px;border-radius:8px;border:1.5px solid;background:transparent;cursor:pointer;font-size:13px;font-family:'Inter',sans-serif">WhatsApp</button>
                </div>

                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
                    <span x-text="canal === 'email' ? 'E-mail' : 'Telefone'"></span>
                </label>
                <input type="text" name="contato" required
                       :placeholder="canal === 'email' ? 'voce@exemplo.com' : '(11) 99999-9999'"
                       style="width:100%;padding:12px 14px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:15px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms;margin-bottom:16px"
                       onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">

                @error('contato')<p style="font-size:12px;color:#ef4444;margin:-10px 0 12px">{{ $message }}</p>@enderror

                <button type="submit"
                        style="width:100%;padding:13px;border-radius:8px;border:none;cursor:pointer;font-size:15px;font-weight:600;font-family:'Inter',sans-serif;background:var(--sa-primary);color:#fff;transition:filter 200ms"
                        onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                    Receber link de acesso
                </button>
            </form>

            <div style="text-align:center;margin-top:16px">
                <a href="{{ route('agendar.show', $company->slug) }}" style="font-size:13px;color:var(--sa-secondary);font-weight:600;text-decoration:none">Quero fazer um novo agendamento →</a>
            </div>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js" defer></script>
@endsection
