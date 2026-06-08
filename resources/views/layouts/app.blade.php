<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#d4a574">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/manifest.json">
    <title>@yield('title', 'Dashboard') | suaAgenda</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; font-size: 16px; }
        body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }

        :root {
            --sa-primary:      #1a1a1a;
            --sa-primary-l:    #2d2d2d;
            --sa-secondary:    #d4a574;
            --sa-secondary-l:  #e6c299;
            --sa-bg:           #f5f5f5;
            --sa-surface:      #ffffff;
            --sa-surface2:     #fafafa;
            --sa-text1:        #1a1a1a;
            --sa-text2:        #5a5a5a;
            --sa-text3:        #999999;
            --sa-border:       #e2e2e2;
            --sa-border2:      #d0d0d0;
            --sa-side-bg:      #111111;
            --sa-side-text:    #eeeeee;
            --sa-side-muted:   #888888;
            --sa-side-accent:  #d4a574;
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--sa-bg);
            color: var(--sa-text1);
        }
        h1, h2, h3, h4 { font-family: 'Poppins', sans-serif; }
        input, button, select, textarea { font-family: 'Inter', -apple-system, sans-serif; }

        ::selection { background: var(--sa-secondary); color: #fff; }
        [x-cloak] { display: none !important; }

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
        .sa-sidebar.collapsed { width: 64px; }

        /* ── Main column ──────────────────────────────────────── */
        .sa-main-col {
            flex: 1;
            min-width: 0;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        /* ── Top bar ──────────────────────────────────────────── */
        .sa-topbar {
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            height: 52px;
            background: var(--sa-surface);
            border-bottom: 1px solid var(--sa-border);
            flex-shrink: 0;
        }

        /* ── Content area ─────────────────────────────────────── */
        .sa-content {
            flex: 1;
            padding: 24px 32px 36px;
        }

        /* ── Nav items ────────────────────────────────────────── */
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
            font-family: 'Inter', sans-serif;
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
        .sa-nav-item.disabled { opacity: .4; cursor: not-allowed; }
        .sa-nav-item.disabled:hover { background: transparent; color: var(--sa-side-muted); }

        /* ── Collapsed ────────────────────────────────────────── */
        .sa-sidebar.collapsed .sa-nav-item {
            justify-content: center;
            gap: 0;
            padding: 10px;
            border-left-color: transparent !important;
        }
        .sa-sidebar.collapsed .nav-label { display: none; }
        .sa-sidebar.collapsed .sidebar-section-label { display: none; }
        .sa-sidebar.collapsed .sidebar-footer-label { display: none; }

        .sidebar-section-label {
            font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .08em;
            color: rgba(255,255,255,.2);
            padding: 16px 14px 4px;
            white-space: nowrap;
        }

        /* ── Mobile overlay ───────────────────────────────────── */
        .sa-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,.5);
            z-index: 300;
        }
        .sa-overlay.open { display: block; }

        /* ── Mobile drawer ────────────────────────────────────── */
        .sa-drawer {
            position: fixed;
            left: 0; top: 0; bottom: 0;
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
            bottom: 0; left: 0; right: 0;
            height: 64px;
            background: var(--sa-surface);
            border-top: 1px solid var(--sa-border);
            z-index: 200;
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

        /* ── Scrollbar ────────────────────────────────────────── */
        .sa-main-col::-webkit-scrollbar { width: 5px; }
        .sa-main-col::-webkit-scrollbar-track { background: transparent; }
        .sa-main-col::-webkit-scrollbar-thumb { background: rgba(0,0,0,.1); border-radius: 3px; }

        /* ── Tablet (≤1080px): icons-only sidebar ─────────────── */
        @media (max-width: 1080px) {
            .sa-sidebar { width: 64px; }
            .sa-sidebar .nav-label { display: none; }
            .sa-sidebar .sidebar-section-label { display: none; }
            .sa-sidebar .sidebar-footer-label { display: none; }
            .sa-sidebar .sa-nav-item { justify-content: center; gap: 0; padding: 10px; border-left-color: transparent !important; }
            .sa-topbar-collapse-btn { display: none; }
        }

        /* ── Mobile (≤768px): hide sidebar, show bottom nav ───── */
        @media (max-width: 768px) {
            .sa-sidebar { display: none; }
            .sa-bottom-nav { display: flex; align-items: stretch; }
            .sa-content { padding: 16px 16px 80px; }
            .sa-topbar { padding: 0 14px; }
            .sa-topbar-date { display: none; }
        }
    </style>

    @stack('styles')
</head>
<body x-data="{ collapsed: false, drawerOpen: false }">

{{-- ── App Shell ────────────────────────────────────────────────── --}}
<div class="app-shell">

    {{-- ── Desktop / Tablet Sidebar ──────────────────────────────── --}}
    <aside class="sa-sidebar" :class="{ collapsed: collapsed }">

        {{-- Logo --}}
        <div style="padding:20px 16px 16px;border-bottom:1px solid rgba(255,255,255,.08);flex-shrink:0">
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
                    <div style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-side-text);letter-spacing:-.2px">suaAgenda</div>
                    <div style="font-size:10px;color:var(--sa-side-accent);font-weight:600;letter-spacing:.5px">.pro</div>
                </div>
            </div>
        </div>

        {{-- New Appointment CTA --}}
        <div style="padding:12px 10px 6px;flex-shrink:0">
            <a href="{{ route('agendamentos.create') }}"
               style="width:100%;display:flex;align-items:center;justify-content:center;gap:7px;background:var(--sa-side-accent);color:#fff;border:none;border-radius:9px;padding:10px 0;cursor:pointer;font-weight:600;font-size:13px;text-decoration:none;transition:opacity 200ms"
               onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                <span class="nav-label">Novo Agendamento</span>
            </a>
        </div>

        {{-- Navigation --}}
        <nav style="flex:1;padding:8px 8px;display:flex;flex-direction:column;gap:1px;overflow-y:auto;overflow-x:hidden">

            @php
            $nav = [
                ['section'=>null,            'route'=>'dashboard',          'label'=>'Dashboard',       'icon'=>'<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',                                        'match'=>'dashboard'],
                ['section'=>null,            'route'=>'agendamentos.index', 'label'=>'Agenda',           'icon'=>'<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>','match'=>'agendamentos.*'],
                ['section'=>null,            'route'=>'calendario',          'label'=>'Calendário',       'icon'=>'<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="8" y1="14" x2="8" y2="14"/><line x1="12" y1="14" x2="12" y2="14"/><line x1="16" y1="14" x2="16" y2="14"/>','match'=>'calendario'],
                ['section'=>'Gestão',        'route'=>'clientes.index',     'label'=>'Clientes',         'icon'=>'<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>','match'=>'clientes.*'],
                ['section'=>null,            'route'=>'profissionais.index', 'label'=>'Profissionais',    'icon'=>'<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',                                                   'match'=>'profissionais.*'],
                ['section'=>null,            'route'=>'servicos.index',      'label'=>'Serviços',         'icon'=>'<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/>','match'=>'servicos.*'],
                ['section'=>'Financeiro',    'route'=>null,                  'label'=>'Financeiro',       'icon'=>'<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>',                                    'match'=>null],
                ['section'=>null,            'route'=>null,                  'label'=>'PDV',              'icon'=>'<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 001.98 1.61h9.72a2 2 0 001.98-1.61L23 6H6"/>','match'=>null],
                ['section'=>null,            'route'=>'relatorios',          'label'=>'Relatórios',      'icon'=>'<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',                  'match'=>'relatorios'],
                ['section'=>'Configurações', 'route'=>null,                  'label'=>'Permissões',      'icon'=>'<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>',                                                 'match'=>null],
                ['section'=>null,            'route'=>'planos.index',        'label'=>'Planos',           'icon'=>'<path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>','match'=>'planos*'],
                ['section'=>null,            'route'=>'perfil',              'label'=>'Meu Perfil',       'icon'=>'<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',                                                  'match'=>'perfil*'],
                ['section'=>null,            'route'=>'configuracoes',       'label'=>'Configurações',   'icon'=>'<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>','match'=>'configuracoes*'],
            ];
            @endphp

            @php $lastSection = '__none__'; @endphp
            @foreach($nav as $item)
                @if($item['section'] !== null && $item['section'] !== $lastSection)
                    <div class="sidebar-section-label">{{ $item['section'] }}</div>
                    @php $lastSection = $item['section']; @endphp
                @elseif($item['section'] === null && $lastSection !== '__none__' && $lastSection !== null)
                    @php $lastSection = null; @endphp
                @endif

                @php
                    $isActive = $item['match'] && request()->routeIs($item['match']);
                    $hasRoute = $item['route'] !== null;
                    $href     = $hasRoute ? route($item['route']) : '#';
                    $classes  = 'sa-nav-item'.($isActive?' active':'').(!$hasRoute?' disabled':'');
                @endphp

                <a href="{{ $href }}" class="{{ $classes }}"
                   @if(!$hasRoute) onclick="return false" title="{{ $item['label'] }}" @endif>
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        {!! $item['icon'] !!}
                    </svg>
                    <span class="nav-label">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        {{-- Footer: company + user --}}
        <div style="border-top:1px solid rgba(255,255,255,.08);flex-shrink:0">
            <div class="nav-label sidebar-footer-label" style="padding:12px 16px 0">
                @if(auth()->user()->company)
                <div style="font-size:11px;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Empresa</div>
                <div style="font-size:13px;font-weight:600;color:var(--sa-side-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    {{ auth()->user()->company->name }}
                </div>
                @endif
            </div>
            <div style="padding:10px 10px 14px;display:flex;align-items:center;gap:10px">
                <div style="width:34px;height:34px;border-radius:50%;background:var(--sa-side-accent);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}{{ strtoupper(substr(strstr(auth()->user()->name, ' ') ?: ' ', 1, 1)) }}
                </div>
                <div class="nav-label sidebar-footer-label" style="flex:1;min-width:0">
                    <div style="font-size:13px;font-weight:600;color:var(--sa-side-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ auth()->user()->name }}</div>
                    <div style="font-size:11px;color:var(--sa-side-muted)">{{ auth()->user()->getRoleNames()->first() ?? 'Usuário' }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="nav-label">
                    @csrf
                    <button type="submit" title="Sair" style="background:none;border:none;cursor:pointer;color:var(--sa-side-muted);padding:4px;border-radius:6px;display:flex;align-items:center;transition:color 150ms" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--sa-side-muted)'">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ── Main Column ─────────────────────────────────────────────── --}}
    <div class="sa-main-col">

        {{-- Sticky Top Bar --}}
        <div class="sa-topbar">
            <div style="display:flex;align-items:center;gap:10px">
                {{-- Hamburger (mobile) --}}
                <button @click="drawerOpen=true"
                        style="display:none;width:32px;height:32px;align-items:center;justify-content:center;border:1px solid var(--sa-border);background:transparent;cursor:pointer;color:var(--sa-text2);border-radius:7px"
                        id="sa-hamburger">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                {{-- Collapse toggle (desktop) --}}
                <button @click="collapsed=!collapsed"
                        class="sa-topbar-collapse-btn"
                        style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:none;background:transparent;cursor:pointer;color:var(--sa-text2);border-radius:7px;transition:background 150ms"
                        onmouseover="this.style.background='var(--sa-border)'" onmouseout="this.style.background='transparent'">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <span class="sa-topbar-date" style="font-size:12px;color:var(--sa-text3)" id="sa-date"></span>
            </div>
            <div style="display:flex;align-items:center;gap:10px">
                <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;background:rgba(212,165,116,.12);color:var(--sa-secondary);text-transform:uppercase;letter-spacing:.04em">
                    {{ auth()->user()->getRoleNames()->first() ?? '—' }}
                </span>
                <span style="font-size:13px;font-weight:500;color:var(--sa-text2)">{{ auth()->user()->name }}</span>
            </div>
        </div>

        {{-- Flash messages --}}
        <div style="padding:16px 32px 0" id="sa-flash">
            @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(()=>show=false,4000)"
                 style="margin-bottom:0;padding:12px 16px;background:rgba(5,150,105,.08);border:1px solid rgba(5,150,105,.2);border-radius:10px;color:#065f46;font-size:14px;display:flex;align-items:center;justify-content:space-between">
                <span>{{ session('success') }}</span>
                <button @click="show=false" style="background:none;border:none;cursor:pointer;color:inherit;margin-left:12px;font-size:18px;line-height:1">&times;</button>
            </div>
            @endif
            @if($errors->any())
            <div style="padding:12px 16px;background:rgba(229,62,62,.06);border:1px solid rgba(229,62,62,.2);border-radius:10px;color:#c53030;font-size:14px">
                <ul style="list-style:disc;padding-left:18px;display:flex;flex-direction:column;gap:4px">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            @endif
        </div>

        {{-- Page content --}}
        <main class="sa-content">
            @yield('content')
        </main>
    </div>
</div>

{{-- ── Mobile Overlay ───────────────────────────────────────────── --}}
<div class="sa-overlay" :class="{ open: drawerOpen }" @click="drawerOpen=false"></div>

{{-- ── Mobile Drawer ────────────────────────────────────────────── --}}
<div class="sa-drawer" :class="{ open: drawerOpen }">
    <div style="padding:16px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:space-between">
        <div style="font-family:'Poppins',sans-serif;font-size:16px;font-weight:700;color:var(--sa-side-text)">suaAgenda<span style="color:var(--sa-side-accent)">.pro</span></div>
        <button @click="drawerOpen=false" style="background:none;border:none;cursor:pointer;color:var(--sa-side-muted);padding:4px">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>
    <nav style="flex:1;padding:8px 10px;overflow-y:auto;display:flex;flex-direction:column;gap:2px">
        @foreach($nav as $item)
        @php
            $isActive = $item['match'] && request()->routeIs($item['match']);
            $hasRoute = $item['route'] !== null;
        @endphp
        @if($hasRoute)
        <a href="{{ route($item['route']) }}" @click="drawerOpen=false"
           class="sa-nav-item {{ $isActive ? 'active' : '' }}"
           style="justify-content:flex-start;gap:11px;padding:11px 12px">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $item['icon'] !!}</svg>
            {{ $item['label'] }}
        </a>
        @endif
        @endforeach
    </nav>
    <div style="border-top:1px solid rgba(255,255,255,.08);padding:12px 16px">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" style="width:100%;display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.06);border:none;border-radius:8px;padding:10px 12px;cursor:pointer;color:var(--sa-side-muted);font-size:13px;font-weight:500">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Sair
            </button>
        </form>
    </div>
</div>

{{-- ── Mobile Bottom Nav ────────────────────────────────────────── --}}
<nav class="sa-bottom-nav">
    <a href="{{ route('dashboard') }}" class="sa-bottom-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Início
    </a>
    <a href="{{ route('agendamentos.index') }}" class="sa-bottom-nav-item {{ request()->routeIs('agendamentos.*') ? 'active' : '' }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Agenda
    </a>
    {{-- FAB center --}}
    <div style="width:56px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <a href="{{ route('agendamentos.create') }}"
           style="width:48px;height:48px;border-radius:50%;background:var(--sa-primary);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(0,0,0,.2)">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        </a>
    </div>
    <a href="{{ route('clientes.index') }}" class="sa-bottom-nav-item {{ request()->routeIs('clientes.*') ? 'active' : '' }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        Clientes
    </a>
    <a href="{{ route('configuracoes') }}" class="sa-bottom-nav-item {{ request()->routeIs('configuracoes*') ? 'active' : '' }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
        Config.
    </a>
</nav>

<script>
    // Date display
    (function() {
        const el = document.getElementById('sa-date');
        if (el) {
            el.textContent = new Date().toLocaleDateString('pt-BR', { weekday:'long', day:'numeric', month:'long' });
        }
        // Show hamburger on mobile
        const btn = document.getElementById('sa-hamburger');
        if (btn) {
            function upd() { btn.style.display = window.innerWidth <= 768 ? 'flex' : 'none'; }
            upd(); window.addEventListener('resize', upd);
        }
    })();
    if ('serviceWorker' in navigator) { navigator.serviceWorker.register('/sw.js'); }
</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@stack('scripts')
</body>
</html>
