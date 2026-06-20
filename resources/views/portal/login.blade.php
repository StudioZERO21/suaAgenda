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
                {{-- fallback: nenhuma integração ativa, o cliente clica para abrir wa.me --}}
                <a href="{{ session('whatsapp_url') }}" target="_blank"
                   style="display:inline-flex;align-items:center;gap:7px;padding:11px 20px;border-radius:8px;background:#25d366;color:#fff;font-size:14px;font-weight:600;text-decoration:none">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Abrir no WhatsApp
                </a>
                <p style="font-size:11px;color:var(--sa-text3);margin:8px 0 0;line-height:1.5">Nenhuma integração ativa — clique para abrir o link no seu WhatsApp.</p>
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
