<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#d4a574">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/manifest.json">
    <title>@yield('title', 'Dashboard') | suaAgenda</title>

    @php
        use App\Support\SaPalettes;
        $saSettings = $saCompanySettings ?? [];
        $saPaletteId = $saSettings['theme_palette'] ?? 'A';
        $saDarkDefault = ($saSettings['dark_mode'] ?? false) ? '1' : '0';

        $saHeadingFont = $saSettings['heading_font'] ?? 'poppins';
        $saBodyFont = $saSettings['body_font'] ?? 'inter';
        $saFonts = SaPalettes::resolveFonts($saHeadingFont, $saBodyFont);
        $saFontsVersion = md5($saHeadingFont.$saBodyFont);
    @endphp

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link id="sa-google-fonts" href="{{ $saFonts['google_url'] }}&amp;v={{ $saFontsVersion }}" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function () {
            const stored = localStorage.getItem('sa-dark');
            const fallback = '{{ $saDarkDefault }}';
            if (stored === '1' || (stored === null && fallback === '1')) {
                document.documentElement.classList.add('sa-dark');
            }
        })();
    </script>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; font-size: 16px; line-height: 1.5; }
        body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }

        {{-- Fontes vêm de uma whitelist em SaPalettes (chaves validadas via --}}
        {{-- FormRequest "in:"), logo é seguro emitir sem escape. {{ }} --}}
        {{-- transformaria as aspas em &#039;, quebrando o valor CSS. --}}
        :root {
            --sa-font-body:    {!! $saFonts['body_css'] !!};
            --sa-font-heading: {!! $saFonts['heading_css'] !!};
        }

        {!! SaPalettes::cssBlock($saPaletteId) !!}

        html, body {
            font-family: var(--sa-font-body);
        }
        body {
            background: var(--sa-bg);
            color: var(--sa-text1);
        }
        h1, h2, h3, h4, h5, h6 { font-family: var(--sa-font-heading); }
        input, button, select, textarea, label { font-family: var(--sa-font-body); }
        .sa-font-heading { font-family: var(--sa-font-heading) !important; }
        .sa-font-body { font-family: var(--sa-font-body) !important; }

        *:focus-visible { outline: 2px solid var(--sa-secondary); outline-offset: 2px; }
        ::selection { background: var(--sa-secondary); color: #fff; }
        [x-cloak] { display: none !important; }

        @keyframes sa-modal-in {
            from { opacity: 0; transform: scale(.96) translateY(-8px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* ── App Shell ────────────────────────────────────────── */
        .app-shell {
            display: flex;
            height: 100vh;
            overflow: hidden;
            background: var(--sa-bg);
        }

        /* ── Sidebar ──────────────────────────────────────────── */
        .sa-sidebar {
            width: 232px;
            flex-shrink: 0;
            background: var(--sa-side-bg);
            display: flex;
            flex-direction: column;
            height: 100vh;
            border-right: 1px solid rgba(255,255,255,.06);
            transition: width 250ms ease;
            overflow: hidden;
        }
        .sa-sidebar.collapsed { width: 60px; }

        .sa-sidebar-logo {
            padding: 24px 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,.08);
            flex-shrink: 0;
        }
        .sa-sidebar.collapsed .sa-sidebar-logo { padding: 20px 12px; }
        .sa-sidebar.collapsed .sa-sidebar-logo .nav-label { display: none; }
        .sa-sidebar.collapsed .sa-sidebar-logo > div { gap: 0; }

        .sa-sidebar-cta-wrap { padding: 14px 16px 6px; flex-shrink: 0; }
        .sa-sidebar.collapsed .sa-sidebar-cta-wrap { padding: 12px 8px 4px; }
        .sa-sidebar.collapsed .sa-sidebar-cta-wrap .nav-label { display: none; }
        .sa-sidebar.collapsed .sa-sidebar-cta { gap: 0; }

        .sa-sidebar-cta {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            background: var(--sa-side-accent);
            color: #fff;
            border: none;
            border-radius: 9px;
            padding: 10px 0;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            transition: filter 200ms ease;
        }
        .sa-sidebar-cta:hover { filter: brightness(1.1); }

        .sa-sidebar-nav {
            flex: 1;
            padding: 8px 10px;
            display: flex;
            flex-direction: column;
            gap: 2px;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .sa-sidebar.collapsed .sa-sidebar-nav { padding: 6px 6px; }

        .sa-nav-item {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px 12px;
            border-radius: 9px;
            border: none;
            cursor: pointer;
            background: transparent;
            color: var(--sa-side-muted);
            font-weight: 500;
            font-size: 14px;
            transition: all 160ms ease;
            text-align: left;
            width: 100%;
            text-decoration: none;
            white-space: nowrap;
            border-left: 2px solid transparent;
        }
        .sa-nav-item:hover { background: rgba(255,255,255,.06); color: var(--sa-side-text); }
        .sa-nav-item.active {
            background: rgba(255,255,255,.1);
            color: var(--sa-side-accent);
            font-weight: 600;
            border-left-color: var(--sa-side-accent);
        }
        .sa-nav-item .nav-icon { width: 18px; height: 18px; flex-shrink: 0; }
        .sa-nav-item.disabled { opacity: .4; cursor: not-allowed; pointer-events: none; }

        .sa-sidebar.collapsed .sa-nav-item {
            justify-content: center;
            gap: 0;
            padding: 10px;
            border-left-color: transparent !important;
        }
        .sa-sidebar.collapsed .nav-label,
        .sa-sidebar.collapsed .sidebar-footer-full { display: none; }
        .sa-sidebar.collapsed .sidebar-footer-collapsed { display: flex; }

        .sidebar-footer-collapsed {
            display: none;
            padding: 12px 8px;
            border-top: 1px solid rgba(255,255,255,.08);
            flex-shrink: 0;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .sa-side-btn {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 10px;
            border: none;
            border-radius: 8px;
            padding: 9px 12px;
            cursor: pointer;
            transition: all 180ms ease;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            color: var(--sa-side-muted);
            background: transparent;
        }
        .sa-side-btn:hover { background: rgba(255,255,255,.05); }
        .sa-side-btn--muted { background: rgba(255,255,255,.06); }
        .sa-side-btn--logout:hover { background: rgba(239,68,68,.12); color: #fca5a5; }
        .sa-side-btn--logout:hover svg { color: #fca5a5; }
        .sa-side-btn--company {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.07);
            font-size: 12px;
        }

        .sa-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--sa-side-accent);
            color: #1a1a1a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            flex-shrink: 0;
        }

        /* ── Main column ──────────────────────────────────────── */
        .sa-main-col {
            flex: 1;
            min-width: 0;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* ── Top bar ──────────────────────────────────────────── */
        .sa-topbar {
            position: sticky;
            top: 0;
            z-index: 200;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 20px;
            background: var(--sa-surface);
            border-bottom: 1px solid var(--sa-border);
            flex-shrink: 0;
            gap: 8px;
        }

        .sa-topbar-btn {
            background: none;
            border: 1px solid var(--sa-border);
            border-radius: 8px;
            cursor: pointer;
            padding: 6px 9px;
            display: flex;
            align-items: center;
            color: var(--sa-text3);
            transition: all 150ms;
        }
        .sa-topbar-btn:hover { color: var(--sa-text2); }

        .sa-notif-panel {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 360px;
            background: var(--sa-surface);
            border: 1px solid var(--sa-border);
            border-radius: 14px;
            box-shadow: 0 12px 40px rgba(0,0,0,.15);
            z-index: 500;
            animation: sa-modal-in 200ms ease;
            overflow: hidden;
        }

        .sa-notif-badge {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #ef4444;
            color: #fff;
            font-size: 9px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--sa-surface);
        }

        /* ── Content area ─────────────────────────────────────── */
        .sa-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            padding: 0;
        }

        /* ── UI Components (DOCS/Layout) ──────────────────────── */
        .sa-app-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 24px 32px 0;
            flex-shrink: 0;
            gap: 12px;
            flex-wrap: wrap;
        }
        .sa-app-header__title {
            font-family: var(--sa-font-heading);
            font-size: 22px;
            font-weight: 700;
            color: var(--sa-text1);
            margin: 0;
            line-height: 1.2;
        }
        .sa-app-header__subtitle {
            font-size: 14px;
            color: var(--sa-text3);
            margin: 4px 0 0;
        }
        .sa-app-header__actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .sa-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 200ms ease;
            text-decoration: none;
            border: none;
            font-family: var(--sa-font-body);
        }
        .sa-btn:hover { filter: brightness(1.1); }
        .sa-btn--sm { font-size: 13px; padding: 7px 14px; height: 32px; }
        .sa-btn--md { font-size: 14px; padding: 9px 18px; height: 40px; }
        .sa-btn--lg { font-size: 15px; padding: 12px 28px; height: 48px; }
        .sa-btn--primary { background: var(--sa-primary); color: #fff; }
        .sa-btn--secondary { background: transparent; color: var(--sa-primary); border: 1.5px solid var(--sa-primary); }
        .sa-btn--ghost { background: transparent; color: var(--sa-text2); }
        .sa-btn--danger { background: #ef4444; color: #fff; }
        .sa-btn--muted { background: var(--sa-surface2); color: var(--sa-text2); border: 1px solid var(--sa-border); }
        .sa-btn__icon { display: flex; align-items: center; }

        .sa-card {
            background: var(--sa-surface);
            border-radius: 12px;
            border: 1px solid var(--sa-border);
            box-shadow: 0 1px 3px rgba(0,0,0,.05);
        }
        .sa-card--flush { padding: 0 !important; overflow: hidden; }

        .sa-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }
        .sa-badge__dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: currentColor;
            flex-shrink: 0;
        }

        .sa-tint-card {
            background: color-mix(in srgb, var(--tint, var(--sa-primary)) 8%, transparent);
            border: 1px solid color-mix(in srgb, var(--tint, var(--sa-primary)) 14%, transparent);
            border-radius: 16px;
            padding: 22px 22px 0;
            position: relative;
            overflow: hidden;
            min-height: 148px;
            display: flex;
            flex-direction: column;
        }
        .sa-tint-card__label {
            font-size: 11px;
            font-weight: 700;
            color: var(--tint, var(--sa-primary));
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 12px;
            opacity: .75;
        }
        .sa-tint-card__value {
            font-family: var(--sa-font-heading);
            font-size: 32px;
            font-weight: 800;
            color: var(--sa-text1);
            line-height: 1;
            letter-spacing: -1px;
        }
        .sa-tint-card__sub,
        .sa-tint-card__trend {
            font-size: 12px;
            margin-top: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .sa-tint-card__sub { color: var(--sa-text3); }
        .sa-tint-card__icon {
            position: absolute;
            bottom: -32px;
            right: -26px;
            opacity: .08;
            pointer-events: none;
        }

        .sa-icon-btn {
            width: 30px;
            height: 30px;
            border-radius: 7px;
            border: 1px solid var(--sa-border);
            background: transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--sa-text3);
            text-decoration: none;
            cursor: pointer;
            transition: all 150ms;
        }
        .sa-icon-btn:hover { border-color: var(--sa-secondary); color: var(--sa-secondary); }
        .sa-icon-btn--danger:hover { border-color: #e53e3e; color: #e53e3e; }

        .sa-avatar-inline {
            border-radius: 50%;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }

        .sa-th {
            padding: 11px 14px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: var(--sa-text3);
            text-transform: uppercase;
            letter-spacing: .5px;
            white-space: nowrap;
        }
        .sa-td {
            padding: 14px;
            font-size: 14px;
            color: var(--sa-text1);
            border-bottom: 1px solid var(--sa-border);
            vertical-align: middle;
        }
        .sa-tr:hover { background: var(--sa-surface2); }

        .sa-search-input {
            width: 100%;
            padding: 10px 12px 10px 36px;
            border: 1.5px solid var(--sa-border);
            border-radius: 8px;
            font-size: 14px;
            color: var(--sa-text1);
            background: var(--sa-surface);
            outline: none;
            transition: all 180ms ease;
            box-sizing: border-box;
        }
        .sa-search-input:focus {
            border-color: var(--sa-primary);
            box-shadow: 0 0 0 3px rgba(0,0,0,.06);
        }

        .sa-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        .sa-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .sa-grid-2-360 { display: grid; grid-template-columns: 1fr 360px; gap: 20px; align-items: start; }

        /* Card de funcionário/equipe (StaffScreen) */
        .sa-staff-card {
            background: color-mix(in srgb, var(--sa-primary) 6%, transparent);
            border: 1px solid color-mix(in srgb, var(--sa-primary) 12%, transparent);
            border-radius: 16px;
            overflow: hidden;
            position: relative;
            transition: box-shadow 200ms;
        }
        .sa-staff-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.1); }

        @media (max-width: 1080px) {
            .sa-grid-4 { grid-template-columns: repeat(2, 1fr); }
            .sa-grid-3 { grid-template-columns: repeat(2, 1fr); }
            .sa-grid-2-360 { grid-template-columns: 1fr; }
            .sa-app-header { padding: 20px 20px 0; }
            .sa-page-body { padding: 16px 20px 0 !important; }
        }
        @media (max-width: 768px) {
            .sa-grid-4 { grid-template-columns: 1fr; }
            .sa-grid-3 { grid-template-columns: 1fr; }
            .sa-app-header { padding: 16px 16px 0; }
            .sa-page-body { padding: 16px 16px 0 !important; }
            .hide-mobile { display: none; }
        }

        /* ── Mobile overlay & drawer ──────────────────────────── */
        .sa-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.5);
            z-index: 300;
        }
        .sa-overlay.open { display: block; }

        .sa-drawer {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 260px;
            background: var(--sa-side-bg);
            z-index: 301;
            display: flex;
            flex-direction: column;
            transform: translateX(-100%);
            transition: transform 250ms ease;
            overflow: hidden;
        }
        .sa-drawer.open { transform: translateX(0); }

        /* ── Bottom nav (mobile) ──────────────────────────────── */
        .sa-bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 64px;
            background: var(--sa-surface);
            border-top: 1px solid var(--sa-border);
            z-index: 200;
            align-items: stretch;
            padding-bottom: env(safe-area-inset-bottom, 0);
        }
        .sa-bottom-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--sa-text3);
            font-size: 10px;
            font-weight: 500;
            text-decoration: none;
            transition: color 150ms;
        }
        .sa-bottom-nav-item.active { color: var(--sa-primary); font-weight: 700; }
        .sa-bottom-nav-item.disabled { opacity: .4; pointer-events: none; }

        .sa-bottom-fab {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--sa-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(0,0,0,.2);
            text-decoration: none;
        }

        /* ── Scrollbar ────────────────────────────────────────── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--sa-border2); border-radius: 3px; }
        .sa-main-col::-webkit-scrollbar-thumb { background: rgba(0,0,0,.1); }

        /* ── Tablet (≤1080px): icons-only sidebar ─────────────── */
        @media (max-width: 1080px) {
            .sa-sidebar { width: 60px; }
            .sa-sidebar .nav-label,
            .sa-sidebar .sidebar-footer-full { display: none; }
            .sa-sidebar .sidebar-footer-collapsed { display: flex; }
            .sa-sidebar .sa-nav-item {
                justify-content: center;
                gap: 0;
                padding: 10px;
                border-left-color: transparent !important;
            }
            .sa-sidebar .sa-sidebar-logo { padding: 20px 12px; }
            .sa-sidebar .sa-sidebar-logo .nav-label { display: none; }
            .sa-sidebar .sa-sidebar-cta-wrap { padding: 12px 8px 4px; }
            .sa-sidebar .sa-sidebar-cta .nav-label { display: none; }
            .sa-sidebar .sa-sidebar-nav { padding: 6px 6px; }
            .sa-topbar-collapse-btn { display: none !important; }
        }

        /* ── Mobile (≤768px) ──────────────────────────────────── */
        @media (max-width: 768px) {
            .sa-sidebar { display: none; }
            .sa-bottom-nav { display: flex; }
            .sa-main-col { padding-bottom: 64px; }
            .sa-topbar-date { display: none; }
            .sa-topbar-hamburger { display: flex !important; }
        }

        /* SweetAlert2 — botão cancelar muted (fundo transparente + borda) */
        .swal2-cancel.swal-cancel-muted {
            border: 1.5px solid #e2e2e2 !important;
            color: #5a5a5a !important;
            font-weight: 600 !important;
        }
        .swal2-cancel.swal-cancel-muted:hover {
            border-color: #1a1a1a !important;
            color: #1a1a1a !important;
        }
    </style>

    @stack('styles')
</head>
<body x-data="appShell()" x-init="init()">

@php
    $nav = [
        ['route' => 'dashboard',           'label' => 'Dashboard',       'icon' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>', 'match' => 'dashboard'],
        ['route' => 'calendario',          'label' => 'Agenda',          'icon' => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>', 'match' => 'calendario'],
        ['route' => 'clientes.index',      'label' => 'Clientes',        'icon' => '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>', 'match' => 'clientes.*'],
        ['route' => 'profissionais.index', 'label' => 'Funcionários',    'icon' => '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>', 'match' => 'profissionais.*'],
        ['route' => 'servicos.index',      'label' => 'Serviços',        'icon' => '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/>', 'match' => 'servicos.*'],
        ['route' => 'produtos.index',      'label' => 'Produtos',        'icon' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>', 'match' => 'produtos.*'],
        ['route' => 'pdv',                 'label' => 'PDV',             'icon' => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>', 'match' => 'pdv'],
        ['route' => 'financeiro',          'label' => 'Financeiro',      'icon' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>', 'match' => 'financeiro'],
        ['route' => 'relatorios',          'label' => 'Relatórios',      'icon' => '<line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>', 'match' => 'relatorios'],
        ['route' => 'portfolio.index',     'label' => 'Portfólio',       'icon' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>', 'match' => 'portfolio.*'],
        ['route' => 'cargos.index',        'label' => 'Cargos',          'icon' => '<path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5z"/>', 'match' => 'cargos.*'],
        ['route' => 'permissoes.index',    'label' => 'Permissões',      'icon' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>', 'match' => 'permissoes.*'],
        ['route' => 'planos.index',        'label' => 'Planos',          'icon' => '<polyline points="20 6 9 17 4 12"/>', 'match' => 'planos.*'],
        ['route' => 'site.index',          'label' => 'Site Público',    'icon' => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>', 'match' => 'site.*'],
        ['route' => 'configuracoes',       'label' => 'Configurações',   'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51a1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>', 'match' => 'configuracoes*'],
    ];

    $userName = auth()->user()->name;
    $nameParts = explode(' ', trim($userName));
    $initials = strtoupper(substr($nameParts[0] ?? '', 0, 1)) . strtoupper(substr($nameParts[1] ?? '', 0, 1));

    $roleLabel = match (auth()->user()->getRoleNames()->first()) {
        'super_admin'   => 'Super Admin',
        'admin_empresa' => 'Administradora',
        'gestor'        => 'Gestor',
        'analista'      => 'Analista',
        default         => 'Usuário',
    };
@endphp

<div class="app-shell">

    {{-- ── Desktop / Tablet Sidebar ──────────────────────────────── --}}
    <aside class="sa-sidebar" :class="{ collapsed: collapsed }">

        <div class="sa-sidebar-logo">
            <div style="display:flex;align-items:center;gap:10px">
                <div style="width:34px;height:34px;border-radius:9px;background:var(--sa-side-accent);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/>
                        <line x1="20" y1="4" x2="8.12" y2="15.88"/>
                        <line x1="14.47" y1="14.48" x2="20" y2="20"/>
                        <line x1="8.12" y1="8.12" x2="12" y2="12"/>
                    </svg>
                </div>
                <div class="nav-label" style="line-height:1">
                    <div style="font-family:var(--sa-font-heading);font-size:15px;font-weight:700;color:var(--sa-side-text);letter-spacing:-.2px;white-space:nowrap">suaAgenda</div>
                    <div style="font-size:10px;color:var(--sa-side-accent);font-weight:600;letter-spacing:.5px;margin-top:-1px">.pro</div>
                </div>
            </div>
        </div>

        <div class="sa-sidebar-cta-wrap">
            <a href="{{ route('agendamentos.create') }}" class="sa-sidebar-cta">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span class="nav-label">Novo Agendamento</span>
            </a>
        </div>

        <nav class="sa-sidebar-nav">
            @foreach($nav as $item)
                @php
                    $isActive = $item['match'] && request()->routeIs($item['match']);
                    $hasRoute = $item['route'] !== null;
                    $href     = $hasRoute ? route($item['route']) : '#';
                    $classes  = 'sa-nav-item' . ($isActive ? ' active' : '') . (!$hasRoute ? ' disabled' : '');
                @endphp
                <a href="{{ $href }}" class="{{ $classes }}" @if(!$hasRoute) title="{{ $item['label'] }}" @endif>
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        {!! $item['icon'] !!}
                    </svg>
                    <span class="nav-label">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        {{-- Footer expandido --}}
        <div class="sidebar-footer-full" style="flex-shrink:0">
            <div style="padding:0 10px 8px">
                <a href="{{ route('configuracoes.empresa') }}" class="sa-side-btn sa-side-btn--company">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--sa-side-muted);flex-shrink:0">
                        <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                    </svg>
                    <span>Configurações da Empresa</span>
                </a>
            </div>
            <div style="border-top:1px solid rgba(255,255,255,.08);padding:14px 16px">
                <button type="button" @click="toggleDark()" class="sa-side-btn sa-side-btn--muted" style="margin-bottom:10px;width:100%">
                    <svg x-show="!dark" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--sa-side-muted);flex-shrink:0">
                        <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                    </svg>
                    <svg x-show="dark" x-cloak width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--sa-side-muted);flex-shrink:0">
                        <circle cx="12" cy="12" r="5"/>
                        <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                        <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                    <span x-text="dark ? 'Modo Claro' : 'Modo Escuro'"></span>
                </button>
                <a href="{{ route('perfil') }}" class="sa-side-btn" style="padding:0;width:100%">
                    <div class="sa-avatar">{{ $initials }}</div>
                    <div style="flex:1;min-width:0;text-align:left">
                        <div style="font-size:13px;font-weight:600;color:var(--sa-side-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $userName }}</div>
                        <div style="font-size:11px;color:var(--sa-side-muted)">{{ $roleLabel }}</div>
                    </div>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--sa-side-muted);flex-shrink:0">
                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                </a>
                <x-sa.logout-form variant="sidebar" />
            </div>
        </div>

        {{-- Footer colapsado --}}
        <div class="sidebar-footer-collapsed">
            <button type="button" @click="toggleDark()" :title="dark ? 'Modo Claro' : 'Modo Escuro'" style="background:none;border:none;cursor:pointer;color:var(--sa-side-muted);padding:8px;border-radius:8px">
                <svg x-show="!dark" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                </svg>
                <svg x-show="dark" x-cloak width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="5"/>
                    <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                    <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
                </svg>
            </button>
            <a href="{{ route('perfil') }}" class="sa-avatar" style="text-decoration:none">{{ $initials }}</a>
            <x-sa.logout-form variant="icon" />
        </div>
    </aside>

    {{-- ── Main Column ─────────────────────────────────────────────── --}}
    <div class="sa-main-col">

        <div class="sa-topbar">
            <div style="display:flex;align-items:center;gap:8px">
                <button type="button" @click="drawerOpen = true" class="sa-topbar-btn sa-topbar-hamburger" style="display:none;color:var(--sa-text2)">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                </button>
                <button type="button" @click="collapsed = !collapsed" class="sa-topbar-btn sa-topbar-collapse-btn" title="Colapsar menu">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                </button>
                <span class="sa-topbar-date" style="font-size:12px;color:var(--sa-text3)" id="sa-date"></span>
            </div>

            <div style="display:flex;gap:6px;align-items:center">
                <div style="position:relative" @click.away="notifOpen = false">
                    <button type="button" @click="notifOpen = !notifOpen" class="sa-topbar-btn" style="position:relative;color:var(--sa-text2);border-radius:9px;padding:7px 10px">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 01-3.46 0"/>
                        </svg>
                        <span class="sa-notif-badge" x-show="unreadCount > 0" x-text="unreadCount"></span>
                    </button>

                    <div class="sa-notif-panel" x-show="notifOpen" x-cloak @click.stop>
                        <div style="padding:16px 18px 12px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--sa-border)">
                            <div style="display:flex;align-items:center;gap:8px">
                                <span style="font-family:var(--sa-font-heading);font-size:14px;font-weight:700;color:var(--sa-text1)">Notificações</span>
                                <span x-show="unreadCount > 0" style="font-size:10px;font-weight:700;color:#fff;background:#ef4444;border-radius:20px;padding:2px 7px" x-text="unreadCount"></span>
                            </div>
                            <button type="button" @click="markAllRead()" x-show="unreadCount > 0" style="font-size:12px;color:var(--sa-secondary);font-weight:600;background:none;border:none;cursor:pointer">Marcar todas como lidas</button>
                        </div>
                        <div style="max-height:380px;overflow-y:auto">
                            <template x-for="(n, i) in notifications" :key="n.id">
                                <div @click="markRead(n.id)" style="display:flex;gap:12;padding:12px 18px;cursor:pointer;border-bottom:1px solid var(--sa-border);transition:background 150ms"
                                     :style="n.read ? '' : 'background:color-mix(in srgb, var(--sa-primary) 4%, transparent)'"
                                     @mouseenter="$el.style.background='var(--sa-surface2)'"
                                     @mouseleave="$el.style.background = n.read ? 'transparent' : 'color-mix(in srgb, var(--sa-primary) 4%, transparent)'">
                                    <div :style="'width:36px;height:36px;border-radius:50%;background:' + n.color + '18;display:flex;align-items:center;justify-content:center;flex-shrink:0'">
                                        <div :style="'width:8px;height:8px;border-radius:50%;background:' + n.color"></div>
                                    </div>
                                    <div style="flex:1;min-width:0">
                                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px">
                                            <span :style="'font-size:13px;color:var(--sa-text1);line-height:1.3;font-weight:' + (n.read ? '500' : '700')" x-text="n.title"></span>
                                            <span style="font-size:11px;color:var(--sa-text3);white-space:nowrap" x-text="n.time"></span>
                                        </div>
                                        <p style="font-size:12px;color:var(--sa-text3);margin:3px 0 0;line-height:1.5;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" x-text="n.msg"></p>
                                    </div>
                                    <div x-show="!n.read" style="width:7px;height:7px;border-radius:50%;background:var(--sa-secondary);flex-shrink:0;margin-top:6px"></div>
                                </div>
                            </template>
                        </div>
                        <div style="padding:10px 18px;border-top:1px solid var(--sa-border);text-align:center">
                            <span style="font-size:12px;color:var(--sa-secondary);font-weight:600;cursor:pointer">Ver todas as notificações →</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <main class="sa-content">
            @yield('content')
        </main>
    </div>
</div>

{{-- ── Mobile Overlay ───────────────────────────────────────────── --}}
<div class="sa-overlay" :class="{ open: drawerOpen }" @click="drawerOpen = false"></div>

{{-- ── Mobile Drawer ────────────────────────────────────────────── --}}
<div class="sa-drawer" :class="{ open: drawerOpen }">
    <div style="padding:20px 16px 12px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:space-between">
        <div style="font-family:var(--sa-font-heading);font-size:16px;font-weight:700;color:var(--sa-side-text)">
            suaAgenda<span style="color:var(--sa-side-accent)">.pro</span>
        </div>
        <button type="button" @click="drawerOpen = false" style="background:none;border:none;cursor:pointer;color:var(--sa-side-muted);padding:4px">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>
    <nav style="flex:1;padding:8px 10px;overflow-y:auto;display:flex;flex-direction:column;gap:2px">
        @foreach($nav as $item)
            @php
                $isActive = $item['match'] && request()->routeIs($item['match']);
                $hasRoute = $item['route'] !== null;
            @endphp
            @if($hasRoute)
            <a href="{{ route($item['route']) }}" @click="drawerOpen = false"
               class="sa-nav-item {{ $isActive ? 'active' : '' }}"
               style="justify-content:flex-start;gap:11px;padding:11px 12px">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $item['icon'] !!}</svg>
                {{ $item['label'] }}
            </a>
            @else
            <span class="sa-nav-item disabled" style="justify-content:flex-start;gap:11px;padding:11px 12px">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $item['icon'] !!}</svg>
                {{ $item['label'] }}
            </span>
            @endif
        @endforeach
    </nav>
    <div style="border-top:1px solid rgba(255,255,255,.08);padding:12px 16px">
        <button type="button" @click="toggleDark()" class="sa-side-btn sa-side-btn--muted" style="margin-bottom:8px;width:100%">
            <svg x-show="!dark" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--sa-side-muted)">
                <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
            </svg>
            <svg x-show="dark" x-cloak width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--sa-side-muted)">
                <circle cx="12" cy="12" r="5"/>
                <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
            </svg>
            <span x-text="dark ? 'Modo Claro' : 'Modo Escuro'"></span>
        </button>
        <a href="{{ route('perfil') }}" @click="drawerOpen = false" class="sa-side-btn" style="padding:8px 0;width:100%">
            <div class="sa-avatar">{{ $initials }}</div>
            <div style="text-align:left">
                <div style="font-size:13px;font-weight:600;color:var(--sa-side-text)">{{ $userName }}</div>
                <div style="font-size:11px;color:var(--sa-side-muted)">{{ $roleLabel }}</div>
            </div>
        </a>
        <x-sa.logout-form variant="sidebar" />
    </div>
</div>

{{-- ── Mobile Bottom Nav ────────────────────────────────────────── --}}
<nav class="sa-bottom-nav">
    <a href="{{ route('dashboard') }}" class="sa-bottom-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
            <rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
        </svg>
        Início
    </a>
    <a href="{{ route('calendario') }}" class="sa-bottom-nav-item {{ request()->routeIs('calendario') ? 'active' : '' }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        Agenda
    </a>
    <div style="width:56px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <a href="{{ route('agendamentos.create') }}" class="sa-bottom-fab">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
        </a>
    </div>
    <a href="{{ route('clientes.index') }}" class="sa-bottom-nav-item {{ request()->routeIs('clientes.*') ? 'active' : '' }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>
        </svg>
        Clientes
    </a>
    <a href="{{ route('financeiro') }}" class="sa-bottom-nav-item {{ request()->routeIs('financeiro') ? 'active' : '' }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
        </svg>
        Financeiro
    </a>
    <a href="{{ route('configuracoes') }}" class="sa-bottom-nav-item {{ request()->routeIs('configuracoes*') ? 'active' : '' }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="3"/>
            <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51a1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
        </svg>
        Config.
    </a>
</nav>

<script>
    function appShell() {
        return {
            collapsed: false,
            drawerOpen: false,
            notifOpen: false,
            dark: false,
            notifications: [
                { id: 1, type: 'booking', title: 'Novo agendamento', msg: 'Miguel Santos agendou Corte + Barba — 14:00', time: '5min', read: false, color: '#10b981', icon: '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>' },
                { id: 2, type: 'cancel', title: 'Cancelamento', msg: 'Rafael Costa cancelou agendamento das 10:30 de amanhã', time: '1h', read: false, color: '#ef4444', icon: '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>' },
                { id: 3, type: 'pending', title: 'Confirmação pendente', msg: 'Bruno Lima aguarda confirmação para amanhã às 9:00', time: '2h', read: false, color: '#f59e0b', icon: '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>' },
                { id: 4, type: 'system', title: 'Limite de WhatsApp', msg: 'Você usou 71% do limite mensal de mensagens', time: '3h', read: true, color: '#6366f1', icon: '<path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/>' },
                { id: 5, type: 'booking', title: 'Agendamento confirmado', msg: 'Pedro Oliveira confirmou Coloração com Ana Costa', time: '4h', read: true, color: '#10b981', icon: '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>' },
            ],
            get unreadCount() {
                return this.notifications.filter(n => !n.read).length;
            },
            init() {
                const el = document.getElementById('sa-date');
                if (el) {
                    el.textContent = new Date().toLocaleDateString('pt-BR', {
                        weekday: 'long', day: 'numeric', month: 'long'
                    });
                }
                this.dark = localStorage.getItem('sa-dark') === '1';
                document.documentElement.classList.toggle('sa-dark', this.dark);
            },
            toggleDark() {
                this.dark = !this.dark;
                document.documentElement.classList.toggle('sa-dark', this.dark);
                localStorage.setItem('sa-dark', this.dark ? '1' : '0');
            },
            markRead(id) {
                this.notifications = this.notifications.map(n =>
                    n.id === id ? { ...n, read: true } : n
                );
            },
            markAllRead() {
                this.notifications = this.notifications.map(n => ({ ...n, read: true }));
            },
        };
    }

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js');
    }

    /**
     * Confirma logout via SweetAlert2 e submete o formulário POST.
     */
    function saConfirmLogout(trigger) {
        const form = trigger.closest('form');
        if (!form) return;

        Swal.fire({
            title: 'Sair da conta?',
            text: 'Ao sair você precisará fazer login novamente.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sair',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }
</script>

@if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        Swal.fire({
            title: 'Sucesso',
            text: @json(session('success')),
            icon: 'success',
            confirmButtonText: 'OK',
            confirmButtonColor: '#1a1a1a',
        });
    });
</script>
@endif

@if(session('error'))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        Swal.fire({
            title: 'Atenção',
            text: @json(session('error')),
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#1a1a1a',
        });
    });
</script>
@endif

@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', () => {
        Swal.fire({
            title: 'Erro de validação',
            html: @json(implode('<br>', $errors->all())),
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#1a1a1a',
        });
    });
</script>
@endif

@stack('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
