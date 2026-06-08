@extends('layouts.app')
@section('title', 'Configurações')

@push('styles')
<style>
    /* ── Layout geral (fluido, preenche a tela) ─────────────── */
    .sa-settings-layout { display:grid; grid-template-columns:220px minmax(0,1fr); gap:28px; align-items:start; width:100%; }
    .sa-settings-content { min-width:0; display:flex; flex-direction:column; gap:24px; }
    /* Sombra p/ separação visual dos cards empilhados em cada aba */
    .sa-settings-content .sa-card { box-shadow:0 3px 16px rgba(0,0,0,.08); }
    .sa-tab-panel { display:flex; flex-direction:column; gap:24px; }

    /* ── Navegação lateral (card sticky) ────────────────────── */
    .sa-settings-nav {
        position:sticky; top:16px;
        background:var(--sa-surface); border:1px solid var(--sa-border); border-radius:14px;
        padding:8px; display:flex; flex-direction:column; gap:2px;
        box-shadow:0 1px 3px rgba(0,0,0,.04);
    }
    .sa-settings-nav__title {
        font-size:11px; font-weight:700; letter-spacing:.6px; text-transform:uppercase;
        color:var(--sa-text3); padding:10px 10px 6px;
    }
    .sa-settings-nav button {
        display:flex; align-items:center; gap:11px; padding:10px 12px; border-radius:10px; border:none; cursor:pointer;
        text-align:left; width:100%; font-size:13.5px; font-family:var(--sa-font-body); transition:all 150ms;
        background:transparent; color:var(--sa-text2); font-weight:500; white-space:nowrap;
    }
    .sa-settings-nav button svg { color:var(--sa-text3); flex-shrink:0; transition:color 150ms; }
    .sa-settings-nav button:hover { background:var(--sa-surface2); color:var(--sa-text1); }
    .sa-settings-nav button.active { background:color-mix(in srgb,var(--sa-primary) 10%,transparent); color:var(--sa-primary); font-weight:600; }
    .sa-settings-nav button.active svg { color:var(--sa-primary); }

    /* Títulos de seção dentro dos cards usam a fonte de títulos */
    .sa-settings-content h3, .sa-settings-content h4 { font-family:var(--sa-font-heading); }

    /* ── Tema ───────────────────────────────────────────────── */
    .sa-tema-grid { display:grid; grid-template-columns:minmax(0,1fr) 300px; gap:24px; align-items:start; }
    .sa-palette-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
    .sa-palette-card {
        border:2px solid var(--sa-border); border-radius:14px; padding:14px; cursor:pointer;
        background:var(--sa-surface); position:relative; overflow:hidden;
        transition:transform 160ms, border-color 160ms, box-shadow 160ms, background 160ms;
    }
    .sa-palette-card:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(0,0,0,.08); border-color:var(--sa-border2); }
    .sa-palette-card.is-selected { border-color:var(--sa-primary); background:color-mix(in srgb,var(--sa-primary) 6%,transparent); }
    .sa-palette-card input { position:absolute; opacity:0; pointer-events:none; }
    .sa-palette-check { display:none; box-shadow:0 2px 6px rgba(0,0,0,.2); }
    .sa-palette-card.is-selected .sa-palette-check { display:flex; }
    .sa-mini-preview { border-radius:12px; overflow:hidden; border:1px solid var(--sa-border); box-shadow:0 4px 16px rgba(0,0,0,.08); user-select:none; pointer-events:none; }

    /* ── Linhas de configuração / toggles ───────────────────── */
    .sa-setting-row { display:flex; justify-content:space-between; align-items:center; padding:16px 0; border-bottom:1px solid var(--sa-border); gap:16px; }
    .sa-setting-row:last-child { border-bottom:none; padding-bottom:0; }
    .sa-setting-row__label { font-size:13.5px; font-weight:600; color:var(--sa-text1); }
    .sa-setting-row__sub { font-size:12px; color:var(--sa-text3); margin-top:2px; line-height:1.5; }
    .sa-setting-row__text { flex:1; padding-right:20px; }
    .sa-toggle { width:42px; height:24px; border-radius:12px; border:none; cursor:pointer; background:var(--sa-border); position:relative; flex-shrink:0; padding:0; transition:background 200ms; }
    .sa-toggle.is-on { background:var(--sa-primary); }
    .sa-toggle__knob { position:absolute; top:3px; left:3px; width:18px; height:18px; border-radius:50%; background:#fff; transition:left 200ms; box-shadow:0 1px 4px rgba(0,0,0,.2); }
    .sa-toggle.is-on .sa-toggle__knob { left:20px; }
    .sa-channel-btn { flex:1; padding:11px; border-radius:10px; border:2px solid var(--sa-border); background:var(--sa-surface); cursor:pointer; font-size:13px; font-weight:500; color:var(--sa-text2); transition:all 180ms; }
    .sa-channel-btn:hover { border-color:var(--sa-border2); }
    .sa-channel-btn.is-active { font-weight:700; }

    /* ── Tipografia ─────────────────────────────────────────── */
    .sa-font-select { width:100%; padding:11px 12px; border:1.5px solid var(--sa-border); border-radius:10px; background:var(--sa-surface); color:var(--sa-text1); font-size:14px; font-family:var(--sa-font-body); cursor:pointer; transition:border-color 160ms; }
    .sa-font-select:focus { border-color:var(--sa-primary); outline:none; }
    .sa-type-preview { padding:22px; background:var(--sa-surface2); border-radius:14px; border:1px solid var(--sa-border); display:flex; flex-direction:column; gap:10px; }

    /* Escala tipográfica */
    .sa-type-row { display:flex; align-items:center; gap:14px; padding:9px 0; border-bottom:1px solid var(--sa-border); }
    .sa-type-row:last-child { border-bottom:none; }
    .sa-type-row .meta { font-size:11px; color:var(--sa-text3); flex-shrink:0; }

    /* ── Inputs com ícone ───────────────────────────────────── */
    .sa-field-label { display:block; font-size:13px; font-weight:600; margin-bottom:6px; color:var(--sa-text1); }
    .sa-inp { width:100%; padding:11px 13px; border:1.5px solid var(--sa-border); border-radius:9px; font-size:14px; color:var(--sa-text1); background:var(--sa-surface); outline:none; font-family:var(--sa-font-body); transition:border-color 160ms; }
    .sa-inp:focus { border-color:var(--sa-primary); }
    .sa-inp-wrap { position:relative; }
    .sa-inp-wrap .sa-inp { padding-left:38px; }
    .sa-inp-icon { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:var(--sa-text3); display:flex; pointer-events:none; }

    /* Chips (força de senha / tags) */
    .sa-chip { font-size:11px; padding:4px 9px; border-radius:20px; border:1px solid var(--sa-border); background:var(--sa-surface2); color:var(--sa-text3); font-weight:600; }
    .sa-chip--ok { background:rgba(16,185,129,.1); color:#059669; border-color:rgba(16,185,129,.25); }

    /* Linhas (sessões / webhooks) */
    .sa-list-row { display:flex; justify-content:space-between; align-items:center; padding:14px 0; border-bottom:1px solid var(--sa-border); gap:12px; }
    .sa-list-row:last-child { border-bottom:none; }

    /* Eventos disponíveis */
    .sa-ev-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:12px; }
    .sa-ev-card { padding:11px 14px; border-radius:9px; background:var(--sa-surface2); border:1px solid var(--sa-border); }

    /* ── Responsivo ─────────────────────────────────────────── */
    @media (max-width:1280px) { .sa-palette-grid { grid-template-columns:repeat(3,1fr); } }
    @media (max-width:1080px) {
        .sa-settings-layout { grid-template-columns:1fr; gap:20px; }
        .sa-tema-grid { grid-template-columns:1fr; }
        .sa-settings-nav { position:static; flex-direction:row; overflow-x:auto; gap:4px; padding:6px; }
        .sa-settings-nav__title { display:none; }
        .sa-settings-nav button { width:auto; }
    }
    @media (max-width:720px) {
        .sa-palette-grid { grid-template-columns:repeat(2,1fr); }
        .sa-ev-grid { grid-template-columns:1fr; }
    }
    @media (max-width:520px) {
        .sa-pwd-grid { grid-template-columns:1fr !important; }
    }
</style>
@endpush

@section('content')
@php
    $tab = request('tab', 'tema');
    $mode = ($settings['dark_mode'] ?? false) ? 'dark' : 'light';
    $activeColors = $activePalette[$mode] ?? $activePalette['light'];
    $setTabs = [
        'tema' => ['label' => 'Tema', 'icon' => '<path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5z"/>'],
        'tipografia' => ['label' => 'Tipografia', 'icon' => '<path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>'],
        'seguranca' => ['label' => 'Segurança', 'icon' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>'],
        'contatos' => ['label' => 'Contatos', 'icon' => '<path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.5a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.09a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0121.15 15z"/>'],
        'api' => ['label' => 'API & Webhooks', 'icon' => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>'],
        'notificacoes' => ['label' => 'Notificações', 'icon' => '<path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/>'],
    ];
    $notif = $settings['notifications'];
    $sec = $settings['security'];
    $contacts = $settings['contacts'];
    $rawSettings = $company->settings ?? [];
    $headingFont = old('heading_font', $rawSettings['heading_font'] ?? $settings['heading_font'] ?? 'poppins');
    $bodyFont = old('body_font', $rawSettings['body_font'] ?? $settings['body_font'] ?? 'inter');

    // Mapa de variáveis CSS por paleta (claro/escuro) para preview em tempo real.
    $paletteCssMap = [];
    foreach ($palettes as $p) {
        $paletteCssMap[$p['id']] = [
            'light' => \App\Support\SaPalettes::cssVariables($p['id'], false),
            'dark' => \App\Support\SaPalettes::cssVariables($p['id'], true),
        ];
    }
@endphp

<x-sa.page x-data="{ tab: '{{ $tab }}' }">
    <x-sa.app-header title="Configurações" subtitle="Personalize seu sistema suaAgenda.pro">
        @can('update', $company)
        <x-slot:actions>
            <x-sa.btn type="submit" x-bind:form="tab === 'tipografia' ? 'form-tipografia' : 'form-preferencias'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:4px"><polyline points="20 6 9 17 4 12"/></svg>
                Salvar
            </x-sa.btn>
        </x-slot:actions>
        @endcan
    </x-sa.app-header>

    <x-sa.body padding="20px 32px 0">
            <div class="sa-settings-layout">
                <nav class="sa-settings-nav">
                    <div class="sa-settings-nav__title">Preferências</div>
                    @foreach($setTabs as $id => $t)
                    <button type="button" @click="tab = '{{ $id }}'; document.getElementById('sa-sync-tab').value = '{{ $id }}'" :class="{ active: tab === '{{ $id }}' }">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $t['icon'] !!}</svg>
                        {{ $t['label'] }}
                    </button>
                    @endforeach
                </nav>

                <div class="sa-settings-content">

                    <form method="POST" action="{{ route('configuracoes.preferencias') }}" id="form-preferencias">
                        @csrf @method('PUT')
                        <input type="hidden" name="tab" id="sa-sync-tab" value="{{ $tab }}">
                        <input type="hidden" name="theme_palette" id="sa-sync-theme-palette" value="{{ $settings['theme_palette'] ?? 'A' }}">

                    {{-- TEMA --}}
                    <div x-show="tab === 'tema'" x-cloak>
                        <div class="sa-tema-grid">
                            <div>
                                <x-sa.card padding="20px" style="margin-bottom:24px">
                                    <div style="display:flex;justify-content:space-between;align-items:center">
                                        <div>
                                            <div style="font-size:15px;font-weight:600;font-family:var(--sa-font-heading)">Modo Escuro</div>
                                            <div style="font-size:13px;color:var(--sa-text3);margin-top:3px">Aplica em todas as telas do sistema</div>
                                        </div>
                                        <x-sa.toggle name="dark_mode" :checked="$settings['dark_mode']" />
                                    </div>
                                </x-sa.card>

                                <x-sa.card padding="20px">
                                    <h3 style="font-size:15px;font-weight:600;margin:0 0 16px">Paleta de Cores</h3>
                                    <div class="sa-palette-grid">
                                        @foreach($palettes as $palette)
                                        @php $selected = ($settings['theme_palette'] ?? 'A') === $palette['id']; @endphp
                                        <label class="sa-palette-card {{ $selected ? 'is-selected' : '' }}" onclick="this.closest('.sa-palette-grid').querySelectorAll('.sa-palette-card').forEach(c=>c.classList.remove('is-selected')); this.classList.add('is-selected'); document.getElementById('sa-sync-theme-palette').value='{{ $palette['id'] }}';">
                                            <input type="radio" name="theme_palette_choice" value="{{ $palette['id'] }}" {{ $selected ? 'checked' : '' }} onchange="document.getElementById('sa-sync-theme-palette').value=this.value">
                                            <div class="sa-palette-check" style="position:absolute;top:10px;right:10px;width:18px;height:18px;border-radius:50%;background:var(--sa-primary);align-items:center;justify-content:center">
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                            </div>
                                            <div style="display:flex;gap:5px;margin-bottom:10px">
                                                @foreach($palette['swatches'] as $sw)
                                                <div style="width:24px;height:24px;border-radius:6px;background:{{ $sw }};border:1px solid rgba(0,0,0,.08)"></div>
                                                @endforeach
                                                <div style="flex:1;height:24px;border-radius:6px;background:{{ $palette['light']['bg'] }};border:1px solid rgba(0,0,0,.06)"></div>
                                            </div>
                                            <div style="font-size:12px;font-weight:600">{{ $palette['name'] }}</div>
                                            <div style="font-size:11px;color:var(--sa-text3);margin-top:2px">{{ $palette['description'] }}</div>
                                        </label>
                                        @endforeach
                                    </div>
                                </x-sa.card>
                            </div>

                            <x-sa.card padding="20px" style="position:sticky;top:16px">
                                <h3 style="font-size:14px;font-weight:600;margin:0 0 14px">Pré-visualização</h3>
                                <div class="sa-mini-preview">
                                    <div style="background:#e0e0e0;padding:6px 10px;display:flex;gap:5px">
                                        @foreach(['#ff5f57','#febc2e','#28c840'] as $c)
                                        <div style="width:9px;height:9px;border-radius:50%;background:{{ $c }}"></div>
                                        @endforeach
                                    </div>
                                    <div style="display:flex;height:180px">
                                        <div style="width:60px;background:{{ $mode === 'dark' ? $activeColors['surface'] : '#111' }};padding:8px 5px">
                                            <div style="width:14px;height:14px;border-radius:3px;background:{{ $activeColors['secondary'] }};margin-bottom:6px"></div>
                                            <div style="height:3px;background:rgba(255,255,255,.3);border-radius:1px;margin-bottom:4px"></div>
                                            <div style="height:3px;background:{{ $activeColors['secondary'] }};border-radius:1px;width:70%"></div>
                                        </div>
                                        <div style="flex:1;background:{{ $activeColors['bg'] }};padding:10px">
                                            <div style="height:7px;width:70%;background:{{ $activeColors['text1'] }};border-radius:3px;margin-bottom:4px"></div>
                                            <div style="height:4px;width:44%;background:{{ $activeColors['text3'] }};border-radius:2px;margin-bottom:10px"></div>
                                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:5px">
                                                <div style="background:{{ $activeColors['surface'] }};border:1px solid {{ $activeColors['border'] }};border-radius:5px;height:40px"></div>
                                                <div style="background:{{ $activeColors['surface'] }};border:1px solid {{ $activeColors['border'] }};border-radius:5px;height:40px"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @foreach([['Primária',$activeColors['primary']],['Secundária',$activeColors['secondary']],['Fundo',$activeColors['bg']]] as [$lbl,$col])
                                <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid var(--sa-border)">
                                    <span style="font-size:12px;color:var(--sa-text2)">{{ $lbl }}</span>
                                    <div style="display:flex;align-items:center;gap:8px">
                                        <div style="width:20px;height:20px;border-radius:5px;background:{{ $col }};border:1px solid rgba(0,0,0,.1)"></div>
                                        <span style="font-size:11px;font-family:monospace;color:var(--sa-text3)">{{ $col }}</span>
                                    </div>
                                </div>
                                @endforeach
                            </x-sa.card>
                        </div>
                    </div>

                    {{-- SEGURANÇA --}}
                    <div x-show="tab === 'seguranca'" x-cloak class="sa-tab-panel">
                        <x-sa.card padding="22px">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 4px">Autenticação</h3>
                            <p style="font-size:13px;color:var(--sa-text3);margin:0 0 16px">Configurações de acesso à conta</p>
                            <x-sa.setting-row label="Autenticação em dois fatores (2FA)" sub="Adiciona uma camada extra de segurança">
                                <x-sa.toggle name="security[twofa]" :checked="$sec['twofa']" />
                            </x-sa.setting-row>
                            <x-sa.setting-row label="Notificar por e-mail em novo login" sub="Receba um aviso quando sua conta for acessada">
                                <x-sa.toggle name="security[logins_email]" :checked="$sec['logins_email']" />
                            </x-sa.setting-row>
                            <x-sa.setting-row label="Tempo de sessão (minutos)" sub="Desconectar após inatividade">
                                <select name="security[session_timeout]" style="font-size:13px;padding:6px 10px;border:1px solid var(--sa-border);border-radius:8px;background:var(--sa-surface)">
                                    @foreach([15,30,60,120,480] as $v)
                                    <option value="{{ $v }}" {{ ($sec['session_timeout'] ?? 30) == $v ? 'selected' : '' }}>{{ $v === 480 ? '8 horas' : $v.' min' }}</option>
                                    @endforeach
                                </select>
                            </x-sa.setting-row>
                        </x-sa.card>
                        <x-sa.card padding="22px">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 4px">Senha</h3>
                            <p style="font-size:13px;color:var(--sa-text3);margin:0 0 16px">Para alterar sua senha com segurança, use a página de perfil.</p>
                            <div style="display:flex;flex-direction:column;gap:12px">
                                <div>
                                    <label class="sa-field-label">Senha atual</label>
                                    <input type="password" class="sa-inp" placeholder="••••••••" autocomplete="off">
                                </div>
                                <div class="sa-pwd-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                                    <div>
                                        <label class="sa-field-label">Nova senha</label>
                                        <input type="password" class="sa-inp" placeholder="Mínimo 8 caracteres" autocomplete="off">
                                    </div>
                                    <div>
                                        <label class="sa-field-label">Confirmar nova senha</label>
                                        <input type="password" class="sa-inp" placeholder="Repita a senha" autocomplete="off">
                                    </div>
                                </div>
                                <div style="display:flex;gap:6px;flex-wrap:wrap">
                                    <span class="sa-chip">8+ caracteres</span>
                                    <span class="sa-chip">Letra maiúscula</span>
                                    <span class="sa-chip">Número</span>
                                    <span class="sa-chip">Símbolo</span>
                                </div>
                                <div>
                                    <x-sa.btn href="{{ route('perfil') }}" variant="secondary" size="sm">Alterar no perfil</x-sa.btn>
                                </div>
                            </div>
                        </x-sa.card>

                        <x-sa.card padding="22px">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 4px">Sessões Ativas</h3>
                            <p style="font-size:13px;color:var(--sa-text3);margin:0 0 8px">Dispositivos com acesso à sua conta</p>
                            @foreach([
                                ['Chrome — Windows 11','São Paulo, SP','Agora',true],
                                ['Safari — iPhone 15','São Paulo, SP','2h atrás',false],
                            ] as [$device,$loc,$time,$current])
                            <div class="sa-list-row">
                                <div style="display:flex;gap:10px;align-items:center">
                                    <div style="width:36px;height:36px;border-radius:9px;background:color-mix(in srgb,var(--sa-primary) 8%,transparent);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
                                    </div>
                                    <div>
                                        <div style="font-size:13px;font-weight:600;color:var(--sa-text1)">{{ $device }} @if($current)<span style="font-size:11px;color:#10b981;margin-left:6px">● Atual</span>@endif</div>
                                        <div style="font-size:12px;color:var(--sa-text3)">{{ $loc }} · {{ $time }}</div>
                                    </div>
                                </div>
                                @unless($current)
                                <x-sa.btn type="button" variant="ghost" size="sm" onclick="saToast('Sessão encerrada','success')">Encerrar</x-sa.btn>
                                @endunless
                            </div>
                            @endforeach
                        </x-sa.card>
                    </div>

                    {{-- CONTATOS --}}
                    <div x-show="tab === 'contatos'" x-cloak class="sa-tab-panel">
                        <x-sa.card padding="22px">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 16px">Canais de Contato</h3>
                            <div style="display:flex;flex-direction:column;gap:14px">
                                <div>
                                    <label class="sa-field-label">WhatsApp de Atendimento</label>
                                    <div class="sa-inp-wrap">
                                        <span class="sa-inp-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.5a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.09a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0121.15 15z"/></svg></span>
                                        <input type="text" name="contacts[support]" value="{{ old('contacts.support', $contacts['support'] ?? $company->whatsapp) }}" class="sa-inp" placeholder="(11) 99999-0000">
                                    </div>
                                </div>
                                <div>
                                    <label class="sa-field-label">E-mail Financeiro / NF</label>
                                    <div class="sa-inp-wrap">
                                        <span class="sa-inp-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
                                        <input type="email" name="contacts[billing]" value="{{ old('contacts.billing', $contacts['billing'] ?? $company->email) }}" class="sa-inp" placeholder="financeiro@empresa.com">
                                    </div>
                                </div>
                            </div>
                        </x-sa.card>
                        <x-sa.card padding="22px">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 16px">Redes Sociais</h3>
                            <div style="display:flex;flex-direction:column;gap:14px">
                                @foreach([
                                    'instagram'=>['Instagram','@usuario','<rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><line x1="17.5" y1="6.5" x2="17.5" y2="6.5"/>'],
                                    'facebook'=>['Facebook','Nome da página','<path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/>'],
                                    'youtube'=>['YouTube','@canal','<path d="M22.54 6.42a2.78 2.78 0 00-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 00-1.94 2A29 29 0 001 11.75a29 29 0 00.46 5.33A2.78 2.78 0 003.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 001.94-2 29 29 0 00.46-5.25 29 29 0 00-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/>'],
                                ] as $key => [$label,$ph,$icon])
                                <div>
                                    <label class="sa-field-label">{{ $label }}</label>
                                    <div class="sa-inp-wrap">
                                        <span class="sa-inp-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $icon !!}</svg></span>
                                        <input type="text" name="contacts[{{ $key }}]" value="{{ old("contacts.$key", $contacts[$key] ?? $company->$key) }}" class="sa-inp" placeholder="{{ $ph }}">
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </x-sa.card>
                        <x-sa.card padding="22px">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 10px">Suporte suaAgenda.pro</h3>
                            <p style="font-size:13px;color:var(--sa-text3);margin:0 0 14px;line-height:1.6">Precisa de ajuda? Nossa equipe está disponível de segunda a sexta, das 8h às 20h.</p>
                            <div style="display:flex;gap:8px;flex-wrap:wrap">
                                <x-sa.btn type="button" variant="secondary" size="sm" onclick="saToast('Abrindo WhatsApp…','info')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.5a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.09a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0121.15 15z"/></svg>
                                    WhatsApp
                                </x-sa.btn>
                                <x-sa.btn type="button" variant="muted" size="sm" onclick="saToast('Abrindo chat…','info')">Chat Online</x-sa.btn>
                                <x-sa.btn type="button" variant="ghost" size="sm" onclick="saToast('Abrindo Central de Ajuda…','info')">Central de Ajuda</x-sa.btn>
                            </div>
                        </x-sa.card>
                    </div>

                    {{-- API --}}
                    <div x-show="tab === 'api'" x-cloak class="sa-tab-panel">
                        <x-sa.card padding="22px">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;gap:12px">
                                <div>
                                    <h3 style="font-size:15px;font-weight:600;margin:0 0 4px">Chave de API</h3>
                                    <p style="font-size:13px;color:var(--sa-text3);margin:0">Use para integrar suaAgenda.pro com sistemas externos</p>
                                </div>
                                <x-sa.toggle name="security[api_access]" :checked="$sec['api_access']" />
                            </div>
                            <div style="display:flex;gap:8px;align-items:center;padding:10px 14px;background:var(--sa-surface2);border:1px solid var(--sa-border);border-radius:9px;margin-bottom:12px">
                                <span style="flex:1;font-family:monospace;font-size:12px;color:var(--sa-text2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">sk_live_saagenda_•••••••••••••</span>
                                <x-sa.btn type="button" variant="muted" size="sm" onclick="saToast('Chave copiada!','success')">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                    Copiar
                                </x-sa.btn>
                            </div>
                            <div style="display:flex;gap:8px;flex-wrap:wrap">
                                <x-sa.btn type="button" variant="secondary" size="sm" onclick="saToast('Nova chave gerada!','success')">Regenerar chave</x-sa.btn>
                                <x-sa.btn type="button" variant="ghost" size="sm" onclick="saToast('Abrindo documentação…','info')">Documentação API →</x-sa.btn>
                            </div>
                        </x-sa.card>

                        <x-sa.card padding="22px">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px">
                                <h3 style="font-size:15px;font-weight:600;margin:0">Webhooks</h3>
                                <x-sa.btn type="button" variant="muted" size="sm" onclick="saToast('Em breve!','info')">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:4px"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    Adicionar
                                </x-sa.btn>
                            </div>
                            @foreach([
                                ['https://minha-api.com/webhook/booking','new_booking',true],
                                ['https://minha-api.com/webhook/cancelled','cancelled',false],
                            ] as [$url,$event,$active])
                            <div class="sa-list-row">
                                <div style="flex:1;min-width:0">
                                    <div style="font-family:monospace;font-size:12px;color:var(--sa-text1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-bottom:4px">{{ $url }}</div>
                                    <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;background:var(--sa-surface2);color:var(--sa-text2);border:1px solid var(--sa-border)">{{ $event }}</span>
                                </div>
                                <div style="display:flex;gap:8px;align-items:center;margin-left:14px">
                                    <x-sa.toggle :checked="$active" />
                                    <x-sa.btn type="button" variant="ghost" size="sm" onclick="saToast('Webhook removido','error')">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                    </x-sa.btn>
                                </div>
                            </div>
                            @endforeach
                        </x-sa.card>

                        <x-sa.card padding="22px">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 12px">Eventos Disponíveis</h3>
                            <div class="sa-ev-grid">
                                @foreach([
                                    'new_booking'=>'Novo agendamento criado',
                                    'cancelled'=>'Agendamento cancelado',
                                    'confirmed'=>'Agendamento confirmado',
                                    'no_show'=>'Cliente não compareceu',
                                    'new_client'=>'Novo cliente cadastrado',
                                    'payment'=>'Pagamento processado',
                                ] as $ev => $desc)
                                <div class="sa-ev-card">
                                    <div style="font-family:monospace;font-size:11px;color:var(--sa-secondary)">{{ $ev }}</div>
                                    <div style="font-size:12px;color:var(--sa-text3)">{{ $desc }}</div>
                                </div>
                                @endforeach
                            </div>
                        </x-sa.card>
                    </div>

                    {{-- NOTIFICAÇÕES --}}
                    <div x-show="tab === 'notificacoes'" x-cloak class="sa-tab-panel" x-data="{ channel: '{{ $notif['channel'] }}' }">
                        <x-sa.card padding="22px">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 4px">Canal Principal</h3>
                            <p style="font-size:13px;color:var(--sa-text3);margin:0 0 14px">Como você quer receber notificações do sistema</p>
                            <div style="display:flex;gap:8px;flex-wrap:wrap">
                                @foreach(['whatsapp' => ['WhatsApp','#25D366'], 'sms' => ['SMS','#6366f1'], 'email' => ['E-mail','var(--sa-secondary)']] as $ch => [$lbl, $col])
                                <button type="button" class="sa-channel-btn" :class="{ 'is-active': channel === '{{ $ch }}' }"
                                        :style="channel === '{{ $ch }}' ? 'border-color:{{ $col }};background:{{ $col }}12;color:{{ $col }}' : ''"
                                        @click="channel = '{{ $ch }}'">
                                    {{ $lbl }}
                                </button>
                                @endforeach
                                <input type="hidden" name="notifications[channel]" :value="channel">
                            </div>
                        </x-sa.card>
                        <x-sa.card padding="22px">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 4px">Notificações de Agendamento</h3>
                            @foreach([
                                'new_booking' => ['Novo agendamento', 'Aviso quando um cliente cria um agendamento'],
                                'cancelled' => ['Cancelamentos', 'Aviso quando um agendamento é cancelado'],
                                'reminder' => ['Lembretes antes', 'Lembrete antes do próximo atendimento'],
                                'no_show' => ['No-show detectado', 'Cliente não compareceu'],
                            ] as $key => [$lbl, $sub])
                            <x-sa.setting-row :label="$lbl" :sub="$sub">
                                <x-sa.toggle :name="'notifications['.$key.']'" :checked="$notif[$key] ?? false" />
                            </x-sa.setting-row>
                            @endforeach
                        </x-sa.card>
                        <x-sa.card padding="22px">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 4px">Relatórios Automáticos</h3>
                            <p style="font-size:13px;color:var(--sa-text3);margin:0 0 4px">Resumos periódicos do seu negócio</p>
                            @foreach(['daily_summary' => ['Resumo diário','Receber ao final de cada dia de trabalho'], 'weekly_report' => ['Relatório semanal','Visão geral toda segunda-feira de manhã']] as $key => [$lbl, $sub])
                            <x-sa.setting-row :label="$lbl" :sub="$sub">
                                <x-sa.toggle :name="'notifications['.$key.']'" :checked="$notif[$key] ?? false" />
                            </x-sa.setting-row>
                            @endforeach
                        </x-sa.card>
                        <x-sa.card padding="22px">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 8px">Testar Notificações</h3>
                            <p style="font-size:13px;color:var(--sa-text3);margin:0 0 14px;line-height:1.6">Envie uma notificação de teste para confirmar que tudo está configurado corretamente.</p>
                            <x-sa.btn type="button" size="sm" x-on:click="saToast('Notificação de teste enviada via ' + channel.toUpperCase() + '!','success')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                                Enviar notificação de teste
                            </x-sa.btn>
                        </x-sa.card>
                    </div>

                    </form>

                    {{-- TIPOGRAFIA — selects com name direto no form (sem hidden/sync JS) --}}
                    @can('update', $company)
                    <form method="POST" action="{{ route('configuracoes.tipografia') }}" id="form-tipografia">
                        @csrf
                    @endcan
                    <div x-show="tab === 'tipografia'" x-cloak class="sa-tab-panel">
                        <x-sa.card padding="22px">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px;flex-wrap:wrap">
                                <h3 style="font-size:15px;font-weight:600;margin:0">Seleção de Fontes</h3>
                                @can('update', $company)
                                <div style="display:flex;gap:8px;flex-wrap:wrap">
                                    <x-sa.btn type="submit" form="form-tipografia" size="sm">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:4px"><polyline points="20 6 9 17 4 12"/></svg>
                                        Salvar fontes
                                    </x-sa.btn>
                                    <x-sa.btn type="button" variant="secondary" size="sm" onclick="saResetTipografia()">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
                                        Restaurar padrão
                                    </x-sa.btn>
                                </div>
                                @endcan
                            </div>
                            @if($errors->has('heading_font') || $errors->has('body_font'))
                            <div style="padding:10px 14px;margin-bottom:14px;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-size:13px">
                                {{ $errors->first('heading_font') }} {{ $errors->first('body_font') }}
                            </div>
                            @endif
                            <p style="font-size:13px;color:var(--sa-text3);margin:0 0 16px">As mudanças são aplicadas em tempo real em todo o sistema. Clique em <strong>Salvar fontes</strong> para manter.</p>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:22px">
                                <div>
                                    <label for="sa-heading-font" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px">Fonte para Títulos (H1–H3)</label>
                                    <select name="heading_font" id="sa-heading-font" class="sa-font-select">
                                        @foreach(['poppins'=>'Poppins — moderno','montserrat'=>'Montserrat — forte','jakarta'=>'Plus Jakarta Sans — limpo','dm-serif'=>'DM Serif Display — elegante'] as $v => $l)
                                        <option value="{{ $v }}" {{ $headingFont === $v ? 'selected' : '' }}>{{ $l }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="sa-body-font" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px">Fonte para Corpo & UI</label>
                                    <select name="body_font" id="sa-body-font" class="sa-font-select">
                                        @foreach(['inter'=>'Inter — padrão','dm-sans'=>'DM Sans — geométrico','nunito'=>'Nunito — amigável','lato'=>'Lato — clássico'] as $v => $l)
                                        <option value="{{ $v }}" {{ $bodyFont === $v ? 'selected' : '' }}>{{ $l }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="sa-type-preview">
                                <div style="font-size:11px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:var(--sa-text3)">Pré-visualização</div>
                                <div id="sa-preview-h1" style="font-size:28px;font-weight:800;line-height:1.15;letter-spacing:-.5px;color:var(--sa-text1)">Bem-vindo ao suaAgenda.pro</div>
                                <div id="sa-preview-h2" style="font-size:18px;font-weight:600;color:var(--sa-text1)">Gestão completa de agendamentos</div>
                                <div style="height:1px;background:var(--sa-border)"></div>
                                <div id="sa-preview-body" style="font-size:14px;color:var(--sa-text2);line-height:1.7">Texto de corpo — legível, confortável e profissional para leitura de descrições e informações extensas do sistema.</div>
                                <div style="font-size:12px;color:var(--sa-text3)">Caption · Metadados · {{ now()->format('d/m/Y') }} · {{ auth()->user()->name }}</div>
                                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:2px">
                                    <span style="font-size:12px;font-weight:600;padding:6px 14px;border-radius:8px;background:var(--sa-primary);color:#fff">Primário</span>
                                    <span style="font-size:12px;font-weight:600;padding:6px 14px;border-radius:8px;background:transparent;color:var(--sa-primary);border:1.5px solid var(--sa-primary)">Secundário</span>
                                    <span style="font-size:12px;font-weight:600;padding:6px 14px;border-radius:8px;background:var(--sa-surface);color:var(--sa-text2);border:1px solid var(--sa-border)">Ghost</span>
                                </div>
                            </div>
                            <div style="margin-top:14px">
                                <span class="sa-type-badge">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                    Salvas: {{ $headingFont }} + {{ $bodyFont }}
                                </span>
                            </div>
                        </x-sa.card>

                        {{-- Escala tipográfica --}}
                        <x-sa.card padding="22px">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 14px">Escala Tipográfica</h3>
                            @foreach([
                                ['H1','48px','w800','lh1.1','heading',20,800,'-1px',false],
                                ['H2','32px','w700','lh1.2','heading',19,700,'-.5px',false],
                                ['H3','24px','w600','lh1.3','heading',18,600,'0',false],
                                ['H4','18px','w600','lh1.4','heading',16,600,'0',false],
                                ['Body','16px','w400','lh1.6','body',15,400,'0',false],
                                ['Small','13px','w400','lh1.5','body',13,400,'.1px',false],
                                ['Caption','11px','w500','lh1.4','body',12,500,'.3px',false],
                                ['Overline','10px','w700','lh1.4','body',11,700,'1px',true],
                            ] as [$lbl,$px,$w,$lh,$font,$size,$weight,$ls,$upper])
                            <div class="sa-type-row">
                                <span style="font-size:10px;font-weight:700;color:var(--sa-secondary);width:52px;flex-shrink:0">{{ $lbl }}</span>
                                <span class="meta" style="width:40px">{{ $px }}</span>
                                <span class="meta" style="width:40px">{{ $w }}</span>
                                <span class="meta" style="width:36px">{{ $lh }}</span>
                                <div style="flex:1;font-family:var(--sa-font-{{ $font }});font-size:{{ $size }}px;font-weight:{{ $weight }};color:var(--sa-text1);letter-spacing:{{ $ls }};text-transform:{{ $upper ? 'uppercase' : 'none' }}">
                                    Barbearia Style{{ $upper ? ' PRO' : '' }}
                                </div>
                            </div>
                            @endforeach
                        </x-sa.card>
                    </div>
                    @can('update', $company)
                    </form>
                    @endcan

                </div>
            </div>
    </x-sa.body>

    @can('update', $company)
    <form method="POST" action="{{ route('configuracoes.tipografia.reset') }}" id="form-reset-tipografia" style="display:none">
        @csrf
    </form>
    @endcan
</x-sa.page>

@push('scripts')
<script>
    const SA_FONT_CSS = @json(\App\Support\SaPalettes::fontCssMap());
    const SA_FONT_GOOGLE = {
        poppins: 'Poppins:wght@400;500;600;700;800',
        montserrat: 'Montserrat:wght@400;500;600;700;800',
        jakarta: 'Plus+Jakarta+Sans:wght@400;500;600;700;800',
        'dm-serif': 'DM+Serif+Display:ital@0;1',
        inter: 'Inter:wght@400;500;600;700',
        'dm-sans': 'DM+Sans:wght@400;500;600;700',
        nunito: 'Nunito:wght@400;500;600;700',
        lato: 'Lato:wght@400;700',
    };

    function saBuildGoogleFontsUrl(headingKey, bodyKey) {
        const keys = [...new Set([headingKey, bodyKey])];
        const query = keys.map((k) => 'family=' + (SA_FONT_GOOGLE[k] || SA_FONT_GOOGLE.inter)).join('&');
        return 'https://fonts.googleapis.com/css2?' + query + '&display=swap';
    }

    function saLoadGoogleFonts(url) {
        let link = document.getElementById('sa-google-fonts');
        if (!link) {
            link = document.createElement('link');
            link.id = 'sa-google-fonts';
            link.rel = 'stylesheet';
            document.head.appendChild(link);
        }
        link.href = url;
    }

    function saApplyFontPreview(applyGlobal = true) {
        const headingKey = document.getElementById('sa-heading-font')?.value || 'poppins';
        const bodyKey = document.getElementById('sa-body-font')?.value || 'inter';
        const headingCss = SA_FONT_CSS[headingKey] || SA_FONT_CSS.poppins;
        const bodyCss = SA_FONT_CSS[bodyKey] || SA_FONT_CSS.inter;

        document.getElementById('sa-preview-h1')?.style.setProperty('font-family', headingCss);
        document.getElementById('sa-preview-h2')?.style.setProperty('font-family', headingCss);
        document.getElementById('sa-preview-body')?.style.setProperty('font-family', bodyCss);

        saLoadGoogleFonts(saBuildGoogleFontsUrl(headingKey, bodyKey));

        // Aplica em todo o sistema (sidebar, topo, conteúdo) em tempo real.
        if (applyGlobal) {
            document.documentElement.style.setProperty('--sa-font-heading', headingCss);
            document.documentElement.style.setProperty('--sa-font-body', bodyCss);
        }
    }

    // ── Paleta de cores em tempo real ─────────────────────────────────
    const SA_PALETTE_CSS = @json($paletteCssMap);

    function saLiveStyle(id) {
        let el = document.getElementById(id);
        if (!el) {
            el = document.createElement('style');
            el.id = id;
            document.head.appendChild(el);
        }
        return el;
    }

    function saApplyPaletteLive(paletteId) {
        const css = SA_PALETTE_CSS[paletteId];
        if (!css) return;
        saLiveStyle('sa-live-theme').textContent =
            ':root{' + css.light + '}html.sa-dark{' + css.dark + '}';
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Fontes
        ['sa-heading-font', 'sa-body-font'].forEach((id) => {
            const el = document.getElementById(id);
            el?.addEventListener('change', () => saApplyFontPreview(true));
            el?.addEventListener('input', () => saApplyFontPreview(true));
        });
        if (document.getElementById('sa-heading-font')) {
            saApplyFontPreview(false);
        }

        // Paleta: clique no card aplica imediatamente
        document.querySelectorAll('.sa-palette-card input[type=radio]').forEach((radio) => {
            radio.addEventListener('change', () => saApplyPaletteLive(radio.value));
        });

        // Modo escuro: o toggle dispara "sa-toggle"; aplicamos na hora
        document.addEventListener('sa-toggle', (e) => {
            if (e.detail?.name === 'dark_mode') {
                document.documentElement.classList.toggle('sa-dark', e.detail.on);
                localStorage.setItem('sa-dark', e.detail.on ? '1' : '0');
            }
        });
    });

    document.getElementById('form-preferencias')?.addEventListener('submit', () => {
        const checkedPalette = document.querySelector('.sa-palette-grid input[type=radio]:checked');
        if (checkedPalette) {
            document.getElementById('sa-sync-theme-palette').value = checkedPalette.value;
        }
        const dark = document.querySelector('input[name="dark_mode"]')?.value === '1';
        localStorage.setItem('sa-dark', dark ? '1' : '0');
        document.documentElement.classList.toggle('sa-dark', dark);
    });

    function saResetTipografia() {
        Swal.fire({
            title: 'Restaurar tipografia?',
            text: 'Voltar para Poppins (títulos) e Inter (corpo) — padrão do suaAgenda.pro.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Restaurar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--sa-primary').trim() || '#1a1a1a',
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('form-reset-tipografia')?.submit();
            }
        });
    }

    // Toast leve para ações de demonstração (suporte, webhooks, testes…)
    function saToast(message, icon = 'success') {
        if (typeof Swal === 'undefined') { return; }
        Swal.fire({
            toast: true,
            position: 'top-end',
            timer: 2600,
            timerProgressBar: true,
            showConfirmButton: false,
            icon: icon,
            title: message,
        });
    }
</script>
@endpush
@endsection
