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
        'integracoes' => ['label' => 'Integrações', 'icon' => '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/><path d="M9 6h6"/>'],
        'icones' => ['label' => 'Catálogo de Ícones', 'icon' => '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/>'],
    ];
    $integrations = $settings['integrations'] ?? [];
    $intWa        = $integrations['whatsapp'] ?? [];
    $intGateway   = $integrations['gateway'] ?? 'nenhum';
    $intMp        = $integrations['mercadopago'] ?? [];
    $intAsaas     = $integrations['asaas'] ?? [];
    $intStripe    = $integrations['stripe'] ?? [];
    $notif = $settings['notifications'];
    $notifV2 = $settings['notifications_v2'];
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
            <x-sa.btn type="submit" x-show="tab !== 'icones'" x-bind:form="tab === 'tipografia' ? 'form-tipografia' : (tab === 'integracoes' ? 'form-integracoes' : (tab === 'notificacoes' ? 'form-notificacoes' : 'form-preferencias'))">
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

                    </form>

                    {{-- NOTIFICAÇÕES — form independente --}}
                    @can('update', $company)
                    <form method="POST" action="{{ route('configuracoes.notificacoes') }}" id="form-notificacoes">
                        @csrf @method('PUT')
                    @endcan

                    <div x-show="tab === 'notificacoes'" x-cloak class="sa-tab-panel">
                        @php
                        $waAtivado = ! empty($settings['integrations']['whatsapp']['ativo']) && ! empty($settings['integrations']['whatsapp']['twilio_sid']);
                        $notifEventos = [
                            'agendamento_confirmado' => ['label' => 'Agendamento confirmado',  'sub' => 'Enviado ao cliente quando o agendamento é confirmado', 'para' => 'cliente', 'cor' => '#059669'],
                            'agendamento_cancelado'  => ['label' => 'Agendamento cancelado',   'sub' => 'Notifica o cliente quando um agendamento é cancelado',  'para' => 'cliente', 'cor' => '#dc2626'],
                            'lembrete_24h'           => ['label' => 'Lembrete 24h antes',      'sub' => 'Aviso enviado ao cliente no dia anterior ao serviço',   'para' => 'cliente', 'cor' => '#d97706'],
                            'lembrete_1h'            => ['label' => 'Lembrete 1h antes',       'sub' => 'Aviso enviado 1 hora antes do horário agendado',        'para' => 'cliente', 'cor' => '#d97706'],
                            'no_show'                => ['label' => 'No-show detectado',        'sub' => 'Alerta interno quando o cliente não compareceu',        'para' => 'empresa', 'cor' => '#6b7280'],
                            'pagamento_confirmado'   => ['label' => 'Pagamento confirmado',    'sub' => 'Confirmação enviada ao cliente após pagamento',         'para' => 'cliente', 'cor' => '#059669'],
                            'novo_cliente'           => ['label' => 'Novo cliente cadastrado', 'sub' => 'Alerta interno quando um novo cliente se cadastra',     'para' => 'empresa', 'cor' => '#6b7280'],
                            'resumo_diario'          => ['label' => 'Resumo diário',           'sub' => 'Estatísticas do dia ao final do expediente',            'para' => 'empresa', 'cor' => '#6b7280'],
                            'relatorio_semanal'      => ['label' => 'Relatório semanal',       'sub' => 'Visão geral toda segunda-feira pela manhã',             'para' => 'empresa', 'cor' => '#6b7280'],
                        ];
                        @endphp

                        {{-- Cabeçalho info --}}
                        <x-sa.card padding="20px">
                            <div style="display:flex;align-items:flex-start;gap:14px">
                                <div style="width:38px;height:38px;border-radius:10px;background:color-mix(in srgb,var(--sa-primary) 9%,transparent);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                                </div>
                                <div>
                                    <div style="font-size:14px;font-weight:600;color:var(--sa-text1);margin-bottom:4px">Notificações por evento</div>
                                    <div style="font-size:13px;color:var(--sa-text3);line-height:1.6">Configure qual canal usar para cada evento. Ative <strong>WhatsApp</strong> na aba <a href="{{ route('configuracoes', ['tab' => 'integracoes']) }}" style="color:var(--sa-secondary);font-weight:600;text-decoration:none">Integrações</a> para usar mensagens automáticas.</div>
                                </div>
                            </div>
                            @if(! $waAtivado)
                            <div style="margin-top:14px;padding:10px 14px;border-radius:9px;border:1px solid rgba(245,158,11,.25);background:rgba(245,158,11,.07);font-size:12px;color:var(--sa-text2);display:flex;align-items:center;gap:8px">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                WhatsApp não configurado — as notificações via WhatsApp não serão enviadas até que as credenciais Twilio sejam salvas em Integrações.
                            </div>
                            @endif
                        </x-sa.card>

                        {{-- Tabela de eventos --}}
                        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)">
                            <table style="width:100%;border-collapse:collapse">
                                <thead>
                                    <tr style="background:var(--sa-surface2);border-bottom:1px solid var(--sa-border)">
                                        <th style="padding:12px 18px;text-align:left;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Evento</th>
                                        <th style="padding:12px 10px;text-align:center;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">Para</th>
                                        <th style="padding:12px 10px;text-align:center;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">
                                            <span style="display:flex;align-items:center;justify-content:center;gap:5px">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                                E-mail
                                            </span>
                                        </th>
                                        <th style="padding:12px 10px;text-align:center;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">
                                            <span style="display:flex;align-items:center;justify-content:center;gap:5px">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="#25D366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M11.988 0C5.373 0 .017 5.34.017 11.937c0 2.104.55 4.082 1.508 5.803L.017 24l6.428-1.677c1.656.898 3.55 1.42 5.566 1.42h.005C18.6 23.743 24 18.404 24 11.806 24 5.341 18.604 0 11.988 0z"/></svg>
                                                WhatsApp
                                            </span>
                                        </th>
                                        <th style="padding:12px 10px;text-align:center;font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">
                                            <span style="display:flex;align-items:center;justify-content:center;gap:5px">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                                                SMS
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($notifEventos as $eventoKey => $evento)
                                    @php $rowChannels = $notifV2[$eventoKey] ?? ['email' => false, 'whatsapp' => false, 'sms' => false]; @endphp
                                    <tr style="border-bottom:1px solid var(--sa-border);transition:background 120ms"
                                        onmouseover="this.style.background='var(--sa-surface2)'"
                                        onmouseout="this.style.background='transparent'">
                                        <td style="padding:14px 18px">
                                            <div style="font-size:14px;font-weight:600;color:var(--sa-text1);margin-bottom:2px">{{ $evento['label'] }}</div>
                                            <div style="font-size:12px;color:var(--sa-text3)">{{ $evento['sub'] }}</div>
                                        </td>
                                        <td style="padding:14px 10px;text-align:center">
                                            @if($evento['para'] === 'cliente')
                                            <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:20px;font-size:11px;font-weight:600;background:rgba(99,102,241,.1);color:#6366f1">
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                                Cliente
                                            </span>
                                            @else
                                            <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:20px;font-size:11px;font-weight:600;background:rgba(107,114,128,.1);color:#6b7280">
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                                                Empresa
                                            </span>
                                            @endif
                                        </td>
                                        @foreach(['email', 'whatsapp', 'sms'] as $canal)
                                        @php
                                            $checked = (bool) ($rowChannels[$canal] ?? false);
                                            $disabled = ($canal === 'whatsapp' && ! $waAtivado) || ($canal === 'sms');
                                        @endphp
                                        <td style="padding:14px 10px;text-align:center">
                                            @can('update', $company)
                                            <label style="display:inline-flex;align-items:center;justify-content:center;cursor:{{ $disabled ? 'not-allowed' : 'pointer' }};position:relative">
                                                <input type="checkbox"
                                                    name="notifications_v2[{{ $eventoKey }}][{{ $canal }}]"
                                                    value="1"
                                                    {{ $checked ? 'checked' : '' }}
                                                    {{ $disabled ? 'disabled' : '' }}
                                                    style="appearance:none;width:18px;height:18px;border-radius:5px;border:2px solid {{ $checked && !$disabled ? 'var(--sa-primary)' : 'var(--sa-border)' }};background:{{ $checked && !$disabled ? 'var(--sa-primary)' : 'var(--sa-surface)' }};cursor:{{ $disabled ? 'not-allowed' : 'pointer' }};transition:all 150ms;flex-shrink:0;opacity:{{ $disabled ? '0.35' : '1' }}"
                                                    onchange="this.style.background=this.checked?'var(--sa-primary)':'var(--sa-surface)';this.style.borderColor=this.checked?'var(--sa-primary)':'var(--sa-border)';this.nextElementSibling.style.display=this.checked?'flex':'none'">
                                                <svg style="position:absolute;pointer-events:none;display:{{ $checked && !$disabled ? 'flex' : 'none' }};color:#fff" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><polyline points="20 6 9 17 4 12"/></svg>
                                            </label>
                                            @else
                                            <span style="opacity:{{ $checked ? '1' : '.3' }};color:{{ $checked ? '#059669' : 'var(--sa-text3)' }}">
                                                @if($checked)
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                                @else
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                @endif
                                            </span>
                                            @endcan
                                        </td>
                                        @endforeach
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Legenda de canais --}}
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
                            <div style="padding:14px 16px;border-radius:10px;border:1px solid var(--sa-border);background:var(--sa-surface)">
                                <div style="display:flex;align-items:center;gap:7px;margin-bottom:6px">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                    <span style="font-size:13px;font-weight:600;color:var(--sa-text1)">E-mail</span>
                                    <span style="display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:20px;font-size:10px;font-weight:700;background:rgba(16,185,129,.1);color:#059669">● Ativo</span>
                                </div>
                                <p style="font-size:12px;color:var(--sa-text3);margin:0;line-height:1.5">Usa as configurações de e-mail do servidor. Sem custo adicional.</p>
                            </div>
                            <div style="padding:14px 16px;border-radius:10px;border:1px solid var(--sa-border);background:var(--sa-surface)">
                                <div style="display:flex;align-items:center;gap:7px;margin-bottom:6px">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="#25D366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M11.988 0C5.373 0 .017 5.34.017 11.937c0 2.104.55 4.082 1.508 5.803L.017 24l6.428-1.677c1.656.898 3.55 1.42 5.566 1.42h.005C18.6 23.743 24 18.404 24 11.806 24 5.341 18.604 0 11.988 0z"/></svg>
                                    <span style="font-size:13px;font-weight:600;color:var(--sa-text1)">WhatsApp</span>
                                    @if($waAtivado)
                                    <span style="display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:20px;font-size:10px;font-weight:700;background:rgba(16,185,129,.1);color:#059669">● Ativo</span>
                                    @else
                                    <span style="display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:20px;font-size:10px;font-weight:700;background:rgba(107,114,128,.1);color:#6b7280">● Inativo</span>
                                    @endif
                                </div>
                                <p style="font-size:12px;color:var(--sa-text3);margin:0;line-height:1.5">Requer credenciais Twilio em <a href="{{ route('configuracoes', ['tab' => 'integracoes']) }}" style="color:var(--sa-secondary);text-decoration:none;font-weight:600">Integrações</a>. Consome cota do plano.</p>
                            </div>
                            <div style="padding:14px 16px;border-radius:10px;border:1px solid var(--sa-border);background:var(--sa-surface);opacity:.6">
                                <div style="display:flex;align-items:center;gap:7px;margin-bottom:6px">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                                    <span style="font-size:13px;font-weight:600;color:var(--sa-text2)">SMS</span>
                                    <span style="display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:20px;font-size:10px;font-weight:700;background:rgba(107,114,128,.1);color:#6b7280">Em breve</span>
                                </div>
                                <p style="font-size:12px;color:var(--sa-text3);margin:0;line-height:1.5">Disponível em uma atualização futura. Configure com Twilio SMS.</p>
                            </div>
                        </div>
                    </div>

                    @can('update', $company)
                    </form>
                    @endcan

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

                    {{-- INTEGRAÇÕES --}}
                    @can('update', $company)
                    <form method="POST" action="{{ route('configuracoes.integracoes') }}" id="form-integracoes" x-data="{
                        gateway: '{{ $intGateway }}',
                        waAtivo: {{ ($intWa['ativo'] ?? false) ? 'true' : 'false' }},
                        twilioAberto: {{ (($intWa['twilio_sid'] ?? '') !== '') ? 'true' : 'false' }},
                        testWa: null, testWaLoading: false,
                        testGw: null, testGwLoading: false,
                        async testarWa() {
                            this.testWaLoading = true; this.testWa = null;
                            const fd = new FormData(document.getElementById('form-integracoes'));
                            try {
                                const r = await fetch('{{ route('configuracoes.integracoes.testar.whatsapp') }}', {
                                    method:'POST', body: fd,
                                    headers:{'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept:'application/json'},
                                });
                                const j = await r.json();
                                this.testWa = j;
                            } catch(e) { this.testWa = {ok:false, erro:'Erro de rede'}; }
                            this.testWaLoading = false;
                        },
                        async testarGateway() {
                            this.testGwLoading = true; this.testGw = null;
                            const fd = new FormData(document.getElementById('form-integracoes'));
                            try {
                                const r = await fetch('{{ route('configuracoes.integracoes.testar.gateway') }}', {
                                    method:'POST', body: fd,
                                    headers:{'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept:'application/json'},
                                });
                                const j = await r.json();
                                this.testGw = j;
                            } catch(e) { this.testGw = {ok:false, erro:'Erro de rede'}; }
                            this.testGwLoading = false;
                        },
                    }">
                    @csrf @method('PUT')
                    @endcan

                    <div x-show="tab === 'integracoes'" x-cloak class="sa-tab-panel">

                        {{-- ── WHATSAPP ─────────────────────────────────── --}}
                        <x-sa.card padding="22px">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;gap:12px">
                                <div>
                                    <h3 style="font-size:15px;font-weight:600;margin:0 0 3px;font-family:var(--sa-font-heading)">WhatsApp</h3>
                                    <p style="font-size:13px;color:var(--sa-text3);margin:0">Notificações automáticas via Twilio. O botão de contato da vitrine usa o número da empresa.</p>
                                </div>
                                @can('update', $company)
                                <button type="button" x-on:click="waAtivo=!waAtivo" :class="waAtivo ? 'is-on' : ''" class="sa-toggle" style="flex-shrink:0">
                                    <div class="sa-toggle__knob"></div>
                                </button>
                                <input type="hidden" name="whatsapp_ativo" :value="waAtivo ? '1' : '0'">
                                @endcan
                            </div>

                            <div x-show="waAtivo" style="margin-top:16px;display:flex;flex-direction:column;gap:14px">

                                {{-- Toggle para expandir credenciais Twilio --}}
                                <button type="button" x-on:click="twilioAberto=!twilioAberto"
                                    style="display:flex;align-items:center;gap:8px;background:var(--sa-surface2);border:1px solid var(--sa-border);border-radius:9px;padding:10px 14px;cursor:pointer;font-size:13px;color:var(--sa-text2);font-weight:600;width:100%;text-align:left;font-family:var(--sa-font-body)">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                    Credenciais Twilio (envio automático)
                                    <svg x-bind:style="twilioAberto ? 'transform:rotate(180deg)' : ''" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left:auto;transition:transform 200ms"><polyline points="6 9 12 15 18 9"/></svg>
                                </button>

                                <div x-show="twilioAberto" style="display:flex;flex-direction:column;gap:12px;padding:14px;border:1px solid var(--sa-border);border-radius:9px;background:var(--sa-surface2)">
                                    <p style="font-size:12px;color:var(--sa-text3);margin:0;line-height:1.6">
                                        Necessário para enviar mensagens automáticas (lembretes, confirmações). Obtenha em
                                        <a href="https://www.twilio.com" target="_blank" rel="noopener" style="color:var(--sa-secondary);font-weight:600;text-decoration:none">twilio.com</a>.
                                    </p>
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                                        <div>
                                            <label class="sa-field-label">Account SID</label>
                                            <input type="text" name="twilio_sid" value="{{ old('twilio_sid', $intWa['twilio_sid'] ?? '') }}" class="sa-inp" placeholder="ACxxxxxxxx" autocomplete="off">
                                        </div>
                                        <div>
                                            <label class="sa-field-label">Auth Token</label>
                                            <input type="password" name="twilio_token" value="{{ old('twilio_token', $intWa['twilio_token'] ?? '') }}" class="sa-inp" placeholder="••••••••••••••••" autocomplete="off">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="sa-field-label">Número Twilio <span style="font-size:11px;color:var(--sa-text3);font-weight:400">(só dígitos, ex: 14155552671)</span></label>
                                        <input type="text" name="twilio_numero" value="{{ old('twilio_numero', $intWa['twilio_numero'] ?? '') }}" class="sa-inp" placeholder="14155552671" autocomplete="off">
                                    </div>

                                    @can('update', $company)
                                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                                        <button type="button" x-on:click="testarWa()" :disabled="testWaLoading"
                                            style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--sa-font-body);transition:border-color 160ms"
                                            onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                                            onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.5a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.09a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0121.15 15z"/></svg>
                                            <span x-text="testWaLoading ? 'Testando…' : 'Testar conexão'"></span>
                                        </button>
                                        <div x-show="testWa !== null" style="font-size:13px;font-weight:600;display:flex;align-items:center;gap:5px">
                                            <span x-show="testWa?.ok" style="color:#059669">✓ <span x-text="testWa?.nome"></span></span>
                                            <span x-show="!testWa?.ok" style="color:#dc2626">✗ <span x-text="testWa?.erro"></span></span>
                                        </div>
                                    </div>
                                    @endcan
                                </div>

                                {{-- Info: botão da vitrine --}}
                                <div style="display:flex;align-items:center;gap:8px;padding:10px 14px;border-radius:9px;border:1px solid rgba(37,211,102,.25);background:rgba(37,211,102,.06)">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="#25D366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M11.988 0C5.373 0 .017 5.34.017 11.937c0 2.104.55 4.082 1.508 5.803L.017 24l6.428-1.677c1.656.898 3.55 1.42 5.566 1.42h.005C18.6 23.743 24 18.404 24 11.806 24 5.341 18.604 0 11.988 0z"/></svg>
                                    <span style="font-size:12px;color:var(--sa-text2)">O botão <strong>WhatsApp</strong> na vitrine pública usa o número cadastrado em <a href="{{ route('configuracoes.empresa') }}" style="color:var(--sa-secondary);font-weight:600;text-decoration:none">Configurações da Empresa</a>.</span>
                                </div>
                            </div>
                        </x-sa.card>

                        {{-- ── CONSUMO DE MENSAGENS ───────────────────────── --}}
                        @php
                            $quota = app(\App\Services\WhatsAppLimitService::class)->quota($company->id);
                        @endphp
                        <x-sa.card padding="22px">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 4px;font-family:var(--sa-font-heading)">Consumo de Mensagens — {{ now()->translatedFormat('F Y') }}</h3>
                            <p style="font-size:13px;color:var(--sa-text3);margin:0 0 16px">Plano <strong style="color:var(--sa-text1)">{{ ucfirst($quota['plano']) }}</strong> · {{ $quota['usado'] }} de {{ $quota['limite'] === PHP_INT_MAX ? '∞' : number_format($quota['limite']) }} mensagens</p>

                            {{-- Barra WhatsApp --}}
                            <div style="margin-bottom:16px">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                                    <span style="font-size:13px;font-weight:600;color:var(--sa-text2);display:flex;align-items:center;gap:6px">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="#25D366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M11.988 0C5.373 0 .017 5.34.017 11.937c0 2.104.55 4.082 1.508 5.803L.017 24l6.428-1.677c1.656.898 3.55 1.42 5.566 1.42h.005C18.6 23.743 24 18.404 24 11.806 24 5.341 18.604 0 11.988 0z"/></svg>
                                        WhatsApp
                                    </span>
                                    <span style="font-size:12px;color:var(--sa-text3)">{{ $quota['usado'] }} / {{ $quota['limite'] === PHP_INT_MAX ? '∞' : $quota['limite'] }}</span>
                                </div>
                                <div style="height:8px;border-radius:4px;background:var(--sa-surface2);border:1px solid var(--sa-border);overflow:hidden">
                                    @php
                                        $barColor = $quota['bloqueado'] ? '#dc2626' : ($quota['alerta'] ? '#d97706' : '#25D366');
                                    @endphp
                                    <div style="height:100%;width:{{ $quota['pct'] }}%;background:{{ $barColor }};border-radius:4px;transition:width 500ms"></div>
                                </div>
                                <div style="display:flex;justify-content:space-between;margin-top:4px">
                                    <span style="font-size:11px;color:var(--sa-text3)">{{ $quota['pct'] }}% utilizado</span>
                                    @if($quota['bloqueado'])
                                        <span style="font-size:11px;color:#dc2626;font-weight:600">Limite atingido — envios bloqueados</span>
                                    @elseif($quota['alerta'])
                                        <span style="font-size:11px;color:#d97706;font-weight:600">⚠ Próximo do limite</span>
                                    @else
                                        <span style="font-size:11px;color:var(--sa-text3)">{{ $quota['restante'] }} restantes este mês</span>
                                    @endif
                                </div>
                            </div>

                            {{-- E-mail (ilimitado) --}}
                            <div>
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                                    <span style="font-size:13px;font-weight:600;color:var(--sa-text2);display:flex;align-items:center;gap:6px">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                        E-mail
                                    </span>
                                    <span style="font-size:12px;color:var(--sa-text3)">Ilimitado</span>
                                </div>
                                <div style="height:8px;border-radius:4px;background:var(--sa-surface2);border:1px solid var(--sa-border);overflow:hidden">
                                    <div style="height:100%;width:60%;background:var(--sa-primary);border-radius:4px;opacity:.35"></div>
                                </div>
                            </div>
                        </x-sa.card>

                        {{-- ── MEIOS DE PAGAMENTO ───────────────────────── --}}
                        <x-sa.card padding="22px" x-data="{}" x-bind:data-gw="gateway">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 4px;font-family:var(--sa-font-heading)">Meios de Pagamento</h3>
                            <p style="font-size:13px;color:var(--sa-text3);margin:0 0 18px;line-height:1.6">Configure um gateway para gerar links de pagamento no PDV. O cliente recebe o link e paga online.</p>

                            {{-- Seleção do gateway --}}
                            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:20px">
                                @foreach([
                                    'nenhum'      => ['Nenhum',       '#999',    '<path d="M18 6L6 18M6 6l12 12"/>'],
                                    'mercadopago' => ['Mercado Pago', '#009ee3', '<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>'],
                                    'asaas'       => ['Asaas',        '#00c2a8', '<path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>'],
                                    'stripe'      => ['Stripe',       '#6772e5', '<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>'],
                                ] as $gw => [$label, $cor, $icon])
                                <button type="button" x-on:click="gateway='{{ $gw }}'"
                                    :style="gateway==='{{ $gw }}' ? 'border-color:{{ $cor }};background:{{ $cor }}14;color:{{ $cor }};font-weight:700' : ''"
                                    style="padding:11px 8px;border-radius:10px;border:2px solid var(--sa-border);background:var(--sa-surface);cursor:pointer;font-size:12px;font-weight:500;color:var(--sa-text2);transition:all 160ms;display:flex;flex-direction:column;align-items:center;gap:6px;font-family:var(--sa-font-body)">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">{!! $icon !!}</svg>
                                    {{ $label }}
                                </button>
                                @endforeach
                            </div>
                            <input type="hidden" name="gateway" :value="gateway">

                            {{-- Mercado Pago --}}
                            <div x-show="gateway === 'mercadopago'" style="display:flex;flex-direction:column;gap:0">
                                @if($mpConnected)
                                {{-- ── CONECTADO ────────────────────────────────────── --}}
                                <div style="border-radius:12px;border:1.5px solid rgba(0,158,227,.35);background:rgba(0,158,227,.05);padding:18px 20px"
                                     x-data="mpMetrics()" x-init="carregar()">
                                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:14px">
                                        <div>
                                            <div style="display:flex;align-items:center;gap:7px;margin-bottom:2px">
                                                <span style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:#059669;flex-shrink:0">
                                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.5"><polyline points="20 6 9 17 4 12"/></svg>
                                                </span>
                                                <span style="font-size:14px;font-weight:700;color:var(--sa-text1)">Conectado</span>
                                            </div>
                                            <div style="font-size:13px;color:var(--sa-text2);padding-left:25px">{{ $mpAccountNome }}</div>
                                        </div>
                                        <form method="POST" action="{{ route('mp.oauth.disconnect') }}" style="flex-shrink:0">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                style="padding:7px 14px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text3);font-size:12px;font-weight:600;cursor:pointer;font-family:var(--sa-font-body);transition:all 150ms"
                                                onmouseover="this.style.borderColor='#ef4444';this.style.color='#ef4444'"
                                                onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'"
                                                onclick="return confirm('Remover token do Mercado Pago? O gateway será desativado.')">
                                                Remover token
                                            </button>
                                        </form>
                                    </div>
                                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                                        <div style="background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:10px;padding:14px 16px">
                                            <div style="font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Saldo disponível</div>
                                            <div style="font-size:20px;font-weight:800;color:var(--sa-text1);font-family:var(--sa-font-heading);letter-spacing:-.5px"
                                                 x-text="loading ? '…' : (balance !== null ? 'R$ ' + balance.toLocaleString('pt-BR',{minimumFractionDigits:2}) : '—')">…</div>
                                        </div>
                                        <div style="background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:10px;padding:14px 16px">
                                            <div style="font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Recebido este mês</div>
                                            <div style="font-size:20px;font-weight:800;color:#059669;font-family:var(--sa-font-heading);letter-spacing:-.5px"
                                                 x-text="loading ? '…' : 'R$ ' + (monthRevenue||0).toLocaleString('pt-BR',{minimumFractionDigits:2})">…</div>
                                        </div>
                                    </div>
                                    <div x-show="erro" style="margin-top:10px;font-size:12px;color:var(--sa-text3)" x-text="erro"></div>
                                    {{-- Campo oculto para atualizar token sem desconectar --}}
                                    <div style="margin-top:16px;padding-top:14px;border-top:1px solid rgba(0,158,227,.2)">
                                        <label style="display:block;font-size:12px;font-weight:600;color:var(--sa-text3);margin-bottom:5px">Atualizar token (opcional)</label>
                                        <input type="password" name="mp_access_token" placeholder="Cole aqui para substituir o token atual"
                                               autocomplete="off"
                                               style="width:100%;padding:9px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:12px;font-family:monospace;color:var(--sa-text1);background:var(--sa-surface);outline:none;box-sizing:border-box;transition:border-color 180ms"
                                               onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'"
                                               onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                                    </div>
                                </div>
                                @else
                                {{-- ── NÃO CONECTADO — OAuth ───────────────────────── --}}
                                <div style="border-radius:12px;border:2px dashed rgba(0,158,227,.25);background:rgba(0,158,227,.03);padding:28px 20px;text-align:center">
                                    <div style="width:52px;height:52px;border-radius:14px;background:rgba(0,158,227,.1);border:1px solid rgba(0,158,227,.2);display:flex;align-items:center;justify-content:center;margin:0 auto 14px">
                                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#009ee3" stroke-width="1.8"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                                    </div>
                                    <div style="font-size:15px;font-weight:700;color:var(--sa-text1);margin-bottom:6px">Conectar Mercado Pago</div>
                                    <div style="font-size:13px;color:var(--sa-text2);margin-bottom:20px;line-height:1.6;max-width:320px;margin-left:auto;margin-right:auto">
                                        Autorize em poucos cliques — sem copiar API keys. Sua conta Mercado Pago fica vinculada com segurança via OAuth 2.0.
                                    </div>
                                    <a href="{{ route('mp.oauth.redirect') }}"
                                       style="display:inline-flex;align-items:center;gap:8px;padding:11px 22px;border-radius:9px;background:#009ee3;color:#fff;font-size:14px;font-weight:700;text-decoration:none;font-family:var(--sa-font-body);transition:filter 180ms"
                                       onmouseover="this.style.filter='brightness(1.1)'"
                                       onmouseout="this.style.filter='none'">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                        Conectar com Mercado Pago
                                    </a>
                                    <div style="margin-top:12px;font-size:11px;color:var(--sa-text3)">
                                        Redirecionamento seguro · Sem acesso à sua senha
                                    </div>
                                </div>
                                @endif
                            </div>

                            {{-- Asaas --}}
                            <div x-show="gateway === 'asaas'" style="display:flex;flex-direction:column;gap:12px">
                                <div style="padding:10px 14px;border-radius:9px;background:rgba(0,194,168,.06);border:1px solid rgba(0,194,168,.2);font-size:12px;color:var(--sa-text2);line-height:1.6">
                                    Obtenha a API Key em <strong>app.asaas.com → Configurações → Integrações → API Key</strong>. Comece em Sandbox e mude para Produção.
                                </div>
                                <div style="display:grid;grid-template-columns:1fr auto;gap:12px;align-items:end">
                                    <div>
                                        <label class="sa-field-label">API Key</label>
                                        <input type="password" name="asaas_api_key" value="{{ old('asaas_api_key', $intAsaas['api_key'] ?? '') }}" class="sa-inp" placeholder="\$aact_xxxxxxxxxxxx" autocomplete="off">
                                    </div>
                                    <div>
                                        <label class="sa-field-label">Ambiente</label>
                                        <select name="asaas_ambiente" style="padding:10px 32px 10px 12px;border:1.5px solid var(--sa-border);border-radius:9px;font-size:14px;background:var(--sa-surface);color:var(--sa-text1);font-family:var(--sa-font-body);appearance:none;background-image:url(&quot;data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'/%3e%3c/svg%3e&quot;);background-repeat:no-repeat;background-position:right 10px center;background-size:14px;cursor:pointer">
                                            <option value="sandbox" {{ ($intAsaas['ambiente'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox (testes)</option>
                                            <option value="producao" {{ ($intAsaas['ambiente'] ?? 'sandbox') === 'producao' ? 'selected' : '' }}>Produção</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- Stripe --}}
                            <div x-show="gateway === 'stripe'" style="display:flex;flex-direction:column;gap:12px">
                                <div style="padding:10px 14px;border-radius:9px;background:rgba(103,114,229,.06);border:1px solid rgba(103,114,229,.2);font-size:12px;color:var(--sa-text2);line-height:1.6">
                                    Obtenha as chaves em <strong>dashboard.stripe.com → Developers → API Keys</strong>. Chaves <em>pk_live_</em> e <em>sk_live_</em> para produção.
                                </div>
                                <div>
                                    <label class="sa-field-label">Publishable Key <span style="color:var(--sa-text3);font-weight:400">(pública)</span></label>
                                    <input type="text" name="stripe_publishable_key" value="{{ old('stripe_publishable_key', $intStripe['publishable_key'] ?? '') }}" class="sa-inp" placeholder="pk_live_xxxxxxxxxxxx" autocomplete="off">
                                </div>
                                <div>
                                    <label class="sa-field-label">Secret Key <span style="color:var(--sa-text3);font-weight:400">(secreta)</span></label>
                                    <input type="password" name="stripe_secret_key" value="{{ old('stripe_secret_key', $intStripe['secret_key'] ?? '') }}" class="sa-inp" placeholder="sk_live_xxxxxxxxxxxx" autocomplete="off">
                                </div>
                            </div>

                            {{-- Botão testar gateway --}}
                            @can('update', $company)
                            <div x-show="gateway !== 'nenhum'" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:16px;padding-top:16px;border-top:1px solid var(--sa-border)">
                                <button type="button" x-on:click="testarGateway()" :disabled="testGwLoading"
                                    style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--sa-font-body);transition:border-color 160ms"
                                    onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                                    onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span x-text="testGwLoading ? 'Verificando…' : 'Verificar conexão'"></span>
                                </button>
                                <div x-show="testGw !== null" style="font-size:13px;font-weight:600;display:flex;align-items:center;gap:5px">
                                    <span x-show="testGw?.ok" style="color:#059669">✓ <span x-text="testGw?.nome"></span></span>
                                    <span x-show="!testGw?.ok" style="color:#dc2626">✗ <span x-text="testGw?.erro"></span></span>
                                </div>
                            </div>
                            @endcan
                        </x-sa.card>

                    </div>
                    @can('update', $company)
                    </form>
                    @endcan

                    {{-- CATÁLOGO DE ÍCONES --}}
                    <div x-show="tab === 'icones'" x-cloak class="sa-tab-panel" x-data="{ iconRefSegment: '' }">
                        <x-sa.card padding="22px">
                            <h3 style="font-size:15px;font-weight:600;margin:0 0 4px">Ícones por segmento profissional</h3>
                            <p style="font-size:13px;color:var(--sa-text3);margin:0 0 8px;line-height:1.6">
                                Referência dos ícones disponíveis. A <strong>seleção</strong> é feita apenas ao
                                <a href="{{ route('servicos.index') }}" style="color:var(--sa-secondary);font-weight:600;text-decoration:none">cadastrar ou editar um serviço</a>.
                            </p>
                            <p style="font-size:12px;color:var(--sa-text3);margin:0 0 16px;line-height:1.6">
                                Escolha um segmento abaixo para ver os ícones correspondentes.
                            </p>

                            <label style="display:block;font-size:12px;font-weight:600;color:var(--sa-text2);margin-bottom:5px">Segmento profissional</label>
                            <select x-model="iconRefSegment"
                                    style="width:100%;max-width:420px;padding:10px 32px 10px 13px;font-size:14px;border:1.5px solid var(--sa-border);border-radius:8px;background:var(--sa-surface);color:var(--sa-text1);cursor:pointer;font-family:'Inter',sans-serif;appearance:none;background-image:url(&quot;data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'/%3e%3c/svg%3e&quot;);background-repeat:no-repeat;background-position:right 10px center;background-size:14px;outline:none;margin-bottom:20px">
                                <option value="">Selecione o segmento...</option>
                                @foreach($iconCategories as $catId => $cat)
                                <option value="{{ $catId }}">{{ $cat['label'] }}</option>
                                @endforeach
                            </select>

                            @foreach($iconCategories as $catId => $cat)
                            <div x-show="iconRefSegment === '{{ $catId }}'" x-cloak>
                                <p style="font-size:12px;color:var(--sa-text3);margin:0 0 12px">{{ $cat['description'] }}</p>
                                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:10px">
                                    @foreach($cat['icons'] as $iconKey)
                                    <div style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:14px 10px;border-radius:12px;border:1px solid var(--sa-border);background:var(--sa-surface2)">
                                        <div style="width:40px;height:40px;border-radius:10px;background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 14%,transparent);display:flex;align-items:center;justify-content:center">
                                            <x-sa.service-icon :name="$iconKey" :size="20" color="var(--sa-primary)" />
                                        </div>
                                        <span style="font-size:12px;font-weight:600;color:var(--sa-text1);text-align:center">{{ \App\Support\SaServiceIcons::label($iconKey) }}</span>
                                        <code style="font-size:10px;color:var(--sa-text3);background:var(--sa-surface);padding:2px 6px;border-radius:4px">{{ $iconKey }}</code>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </x-sa.card>
                    </div>

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

    function mpMetrics() {
        return {
            loading: true,
            balance: null,
            monthRevenue: null,
            erro: null,
            async carregar() {
                try {
                    const r = await fetch('{{ route('mp.oauth.metrics') }}', {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                    });
                    const j = await r.json();
                    if (j.ok) {
                        this.balance      = j.balance;
                        this.monthRevenue = j.month_revenue;
                    } else {
                        this.erro = 'Métricas indisponíveis agora.';
                    }
                } catch(e) {
                    this.erro = 'Não foi possível carregar as métricas.';
                } finally {
                    this.loading = false;
                }
            }
        };
    }
</script>
@endpush
@endsection
