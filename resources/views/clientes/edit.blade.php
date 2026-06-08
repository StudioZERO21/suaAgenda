@extends('layouts.app')
@section('title', 'Editar Cliente')
@section('page-title', 'Editar Cliente')

@section('content')
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:24px">
        <a href="{{ route('clientes.show', $cliente) }}" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--sa-border);border-radius:8px;text-decoration:none;color:var(--sa-text3);transition:all 150ms" onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div>
            <h1 style="font-family:var(--sa-font-heading);font-size:20px;font-weight:700;color:var(--sa-text1);margin:0 0 2px">Editar Cliente</h1>
            <p style="font-size:13px;color:var(--sa-text3);margin:0">{{ $cliente->name }}</p>
        </div>
    </div>

    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px">
        <form method="POST" action="{{ route('clientes.update', $cliente) }}" style="display:flex;flex-direction:column;gap:18px">
            @csrf
            @method('PUT')

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div style="grid-column:1/-1">
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Nome completo <span style="color:var(--sa-secondary)">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $cliente->name) }}" required placeholder="Nome do cliente"
                           style="width:100%;padding:10px 12px;border:1.5px solid {{ $errors->has('name') ? '#e53e3e' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                           onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    @error('name')<p style="font-size:12px;color:#e53e3e;margin-top:4px">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Telefone / WhatsApp</label>
                    <input type="text" name="phone" value="{{ old('phone', $cliente->phone) }}" placeholder="(00) 00000-0000"
                           style="width:100%;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                           onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                </div>

                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">E-mail</label>
                    <input type="email" name="email" value="{{ old('email', $cliente->email) }}" placeholder="email@exemplo.com"
                           style="width:100%;padding:10px 12px;border:1.5px solid {{ $errors->has('email') ? '#e53e3e' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                           onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    @error('email')<p style="font-size:12px;color:#e53e3e;margin-top:4px">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Data de nascimento</label>
                    <input type="date" name="data_nasc" value="{{ old('data_nasc', $cliente->data_nasc?->format('Y-m-d')) }}"
                           style="width:100%;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                           onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                </div>

                <div style="grid-column:1/-1">
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Observações</label>
                    <textarea name="observacao" rows="3" placeholder="Preferências, alergias, etc."
                              style="width:100%;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;resize:vertical;transition:border-color 180ms,outline 180ms;font-family:var(--sa-font-body)"
                              onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">{{ old('observacao', $cliente->observacao) }}</textarea>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:10px;padding:14px;background:var(--sa-surface2);border-radius:8px;border:1px solid var(--sa-border)">
                <input type="checkbox" name="lgpd_consent" id="lgpd_consent" value="1"
                       style="width:16px;height:16px;accent-color:var(--sa-primary);cursor:pointer;flex-shrink:0"
                       {{ old('lgpd_consent', $cliente->lgpd_consent) ? 'checked' : '' }}>
                <label for="lgpd_consent" style="font-size:13px;color:var(--sa-text2);cursor:pointer;line-height:1.5">
                    Cliente consentiu com o uso de dados (LGPD)
                </label>
            </div>

            <div style="display:flex;gap:10px;padding-top:4px">
                <button type="submit"
                        style="padding:11px 24px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;background:var(--sa-primary);color:#fff;transition:filter 200ms"
                        onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                    Salvar Alterações
                </button>
                <a href="{{ route('clientes.show', $cliente) }}"
                   style="padding:11px 20px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;text-decoration:none;transition:all 180ms"
                   onmouseover="this.style.borderColor='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)'">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
@endsection
