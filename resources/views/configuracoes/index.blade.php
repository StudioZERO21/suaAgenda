@extends('layouts.app')
@section('title', 'Configurações')
@section('page-title', 'Configurações')

@section('content')
<div style="max-width:900px" x-data="{ tab: '{{ session('errors') ? 'empresa' : 'empresa' }}' }">

    {{-- Cabeçalho --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
        <div>
            <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">Configurações</h1>
            <p style="font-size:14px;color:var(--sa-text3);margin:0">{{ $company->name }}</p>
        </div>
    </div>

    @if(session('success'))
    <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:10px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.2);margin-bottom:20px">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        <span style="font-size:14px;font-weight:600;color:#059669">{{ session('success') }}</span>
    </div>
    @endif

    <div style="display:flex;gap:24px;align-items:flex-start">

        {{-- Tab nav vertical --}}
        <div style="width:200px;flex-shrink:0">
            <div style="display:flex;flex-direction:column;gap:2px">

                <button @click="tab='empresa'"
                        :style="tab==='empresa' ? 'background:color-mix(in srgb,var(--sa-primary) 8%,transparent);color:var(--sa-primary);font-weight:600;border-left:2px solid var(--sa-primary)' : 'background:transparent;color:var(--sa-text2);font-weight:500;border-left:2px solid transparent'"
                        style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;border:none;border-right:none;border-top:none;border-bottom:none;cursor:pointer;text-align:left;width:100%;font-size:13px;font-family:'Inter',sans-serif;transition:all 150ms">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Empresa
                </button>

                <button @click="tab='plano'"
                        :style="tab==='plano' ? 'background:color-mix(in srgb,var(--sa-primary) 8%,transparent);color:var(--sa-primary);font-weight:600;border-left:2px solid var(--sa-primary)' : 'background:transparent;color:var(--sa-text2);font-weight:500;border-left:2px solid transparent'"
                        style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;border:none;border-right:none;border-top:none;border-bottom:none;cursor:pointer;text-align:left;width:100%;font-size:13px;font-family:'Inter',sans-serif;transition:all 150ms">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
                    Plano
                </button>

            </div>
        </div>

        {{-- Conteúdo das tabs --}}
        <div style="flex:1;min-width:0">

            {{-- Tab: Empresa --}}
            <div x-show="tab==='empresa'" x-cloak>
                <form action="{{ route('configuracoes.update') }}" method="POST" style="display:flex;flex-direction:column;gap:16px">
                    @csrf @method('PUT')

                    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
                        <h2 style="font-size:15px;font-weight:600;color:var(--sa-text1);margin:0 0 4px">Dados da Empresa</h2>
                        <p style="font-size:13px;color:var(--sa-text3);margin:0 0 20px">Informações exibidas para clientes e no sistema.</p>

                        <div style="display:flex;flex-direction:column;gap:16px">

                            {{-- Nome --}}
                            <div>
                                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
                                    Nome da empresa <span style="color:var(--sa-secondary)">*</span>
                                </label>
                                <input type="text" name="name" value="{{ old('name', $company->name) }}" required
                                       style="width:100%;padding:10px 13px;border:1.5px solid {{ $errors->has('name') ? '#ef4444' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                                       onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='{{ $errors->has('name') ? '#ef4444' : 'var(--sa-border)' }}'">
                                @error('name')
                                <p style="font-size:12px;color:#ef4444;margin-top:4px">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- WhatsApp --}}
                            <div>
                                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">WhatsApp</label>
                                <div style="position:relative">
                                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--sa-text3)">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.5a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.09a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0121.15 15z"/></svg>
                                    </span>
                                    <input type="text" name="whatsapp" value="{{ old('whatsapp', $company->whatsapp) }}" placeholder="(11) 99999-0000"
                                           style="width:100%;padding:10px 13px 10px 36px;border:1.5px solid {{ $errors->has('whatsapp') ? '#ef4444' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                                           onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='{{ $errors->has('whatsapp') ? '#ef4444' : 'var(--sa-border)' }}'">
                                </div>
                                @error('whatsapp')
                                <p style="font-size:12px;color:#ef4444;margin-top:4px">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Slug (read-only) --}}
                            <div>
                                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Identificador (slug)</label>
                                <input type="text" value="{{ $company->slug }}" disabled
                                       style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',monospace;color:var(--sa-text3);background:var(--sa-surface2);outline:none;cursor:not-allowed">
                                <p style="font-size:12px;color:var(--sa-text3);margin-top:4px">O identificador não pode ser alterado após a criação.</p>
                            </div>

                        </div>
                    </div>

                    {{-- LGPD --}}
                    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:20px" x-data="{ lgpd: {{ $company->lgpd_consent ? 'true' : 'false' }} }">
                            <div style="flex:1">
                                <h2 style="font-size:15px;font-weight:600;color:var(--sa-text1);margin:0 0 4px">Consentimento LGPD</h2>
                                <p style="font-size:13px;color:var(--sa-text3);margin:0;line-height:1.6">Indica que a empresa está em conformidade com a Lei Geral de Proteção de Dados e coleta consentimentos dos clientes.</p>
                            </div>
                            <div style="flex-shrink:0">
                                <input type="hidden" name="lgpd_consent" :value="lgpd ? '1' : '0'">
                                <button type="button" @click="lgpd = !lgpd"
                                        :style="lgpd ? 'background:var(--sa-primary)' : 'background:var(--sa-border)'"
                                        style="width:44px;height:26px;border-radius:13px;border:none;cursor:pointer;position:relative;transition:background 200ms;flex-shrink:0;padding:0">
                                    <span :style="lgpd ? 'left:22px' : 'left:4px'"
                                          style="position:absolute;top:4px;width:18px;height:18px;border-radius:50%;background:#fff;transition:left 200ms;box-shadow:0 1px 3px rgba(0,0,0,.2)"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    @can('update', $company)
                    <div style="display:flex;gap:10px">
                        <button type="submit"
                                style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:var(--sa-primary);color:#fff;transition:filter 200ms"
                                onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            Salvar alterações
                        </button>
                    </div>
                    @endcan

                </form>
            </div>

            {{-- Tab: Plano --}}
            <div x-show="tab==='plano'" x-cloak>
                <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
                    <h2 style="font-size:15px;font-weight:600;color:var(--sa-text1);margin:0 0 4px">Seu Plano</h2>
                    <p style="font-size:13px;color:var(--sa-text3);margin:0 0 24px">Informações sobre o plano contratado.</p>

                    {{-- Plano atual --}}
                    <div style="background:color-mix(in srgb,var(--sa-secondary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-secondary) 14%,transparent);border-radius:12px;padding:20px;margin-bottom:20px;position:relative;overflow:hidden">
                        <div style="font-size:11px;font-weight:700;color:var(--sa-secondary);letter-spacing:1px;text-transform:uppercase;opacity:.85;margin-bottom:8px">Plano Atual</div>
                        <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--sa-text1);letter-spacing:-0.5px;text-transform:capitalize">{{ $company->plano }}</div>
                        <div style="position:absolute;bottom:-20px;right:-16px;opacity:.08;pointer-events:none">
                            <svg width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
                        </div>
                    </div>

                    {{-- Detalhes --}}
                    <div style="display:flex;flex-direction:column;gap:0">

                        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--sa-border)">
                            <span style="font-size:13px;font-weight:600;color:var(--sa-text2)">Status da conta</span>
                            @if($company->ativo)
                            <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;padding:3px 10px;border-radius:20px;background:rgba(16,185,129,.12);color:#059669">
                                <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>Ativa
                            </span>
                            @else
                            <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;padding:3px 10px;border-radius:20px;background:rgba(239,68,68,.1);color:#dc2626">
                                <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>Inativa
                            </span>
                            @endif
                        </div>

                        @if($company->trial_ends_at)
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--sa-border)">
                            <span style="font-size:13px;font-weight:600;color:var(--sa-text2)">Trial até</span>
                            <div style="text-align:right">
                                <div style="font-size:13px;font-weight:700;color:var(--sa-text1)">{{ $company->trial_ends_at->format('d/m/Y') }}</div>
                                @if($company->emTrial())
                                <div style="font-size:11px;color:#d97706;margin-top:1px">{{ $company->trial_ends_at->diffForHumans() }}</div>
                                @else
                                <div style="font-size:11px;color:#dc2626;margin-top:1px">Expirado</div>
                                @endif
                            </div>
                        </div>
                        @endif

                        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--sa-border)">
                            <span style="font-size:13px;font-weight:600;color:var(--sa-text2)">Membro desde</span>
                            <span style="font-size:13px;color:var(--sa-text1)">{{ $company->created_at->format('d/m/Y') }}</span>
                        </div>

                        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0">
                            <span style="font-size:13px;font-weight:600;color:var(--sa-text2)">LGPD</span>
                            @if($company->lgpd_consent)
                            <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;padding:3px 10px;border-radius:20px;background:rgba(16,185,129,.12);color:#059669">
                                <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>Em conformidade
                            </span>
                            @else
                            <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;padding:3px 10px;border-radius:20px;background:rgba(107,114,128,.12);color:#6b7280">
                                <span style="width:5px;height:5px;border-radius:50%;background:currentColor;flex-shrink:0"></span>Não configurado
                            </span>
                            @endif
                        </div>

                    </div>
                </div>

                <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:20px;margin-top:16px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
                    <h3 style="font-size:14px;font-weight:600;color:var(--sa-text1);margin:0 0 8px">Precisa de ajuda?</h3>
                    <p style="font-size:13px;color:var(--sa-text3);margin:0 0 14px;line-height:1.6">Nossa equipe está disponível de segunda a sexta, das 8h às 20h.</p>
                    @if($company->whatsapp)
                    <a href="https://wa.me/55{{ preg_replace('/\D/', '', $company->whatsapp) }}" target="_blank"
                       style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:8px;border:none;cursor:pointer;font-size:13px;font-weight:600;font-family:'Inter',sans-serif;background:var(--sa-primary);color:#fff;text-decoration:none;transition:filter 200ms"
                       onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.5a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.09a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0121.15 15z"/></svg>
                        Contato via WhatsApp
                    </a>
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection
