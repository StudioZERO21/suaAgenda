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

    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={ theme:{ extend:{ fontFamily:{ sans:['Inter','-apple-system','sans-serif'] } } } }</script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 16px; }
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

        *:focus-visible { outline: 2px solid var(--sa-secondary); outline-offset: 2px; }
        ::selection { background: var(--sa-secondary); color: #fff; }
        [x-cloak] { display: none !important; }

        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 3px; }

        /* ── Sidebar ──────────────────────────────────────────── */
        .sa-sidebar {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            background: var(--sa-side-bg);
            display: flex;
            flex-direction: column;
            z-index: 200;
            transition: width 250ms ease, transform 250ms ease;
            overflow: hidden;
            border-right: 1px solid rgba(255,255,255,.05);
        }

        .sa-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,.55);
            z-index: 199;
            backdrop-filter: blur(2px);
        }

        /* ── Header ───────────────────────────────────────────── */
        .sa-header {
            position: fixed;
            top: 0; right: 0;
            height: 56px;
            background: var(--sa-surface);
            border-bottom: 1px solid var(--sa-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 100;
            transition: left 250ms ease;
        }

        /* ── Main content ─────────────────────────────────────── */
        .sa-main {
            padding-top: 56px;
            transition: margin-left 250ms ease;
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
        .sa-nav-item:hover {
            background: rgba(255,255,255,.06);
            color: var(--sa-side-text);
        }
        .sa-nav-item.active {
            background: rgba(255,255,255,.1);
            color: var(--sa-side-accent);
            font-weight: 600;
            border-left-color: var(--sa-side-accent);
        }
        .sa-nav-item .nav-icon {
            width: 18px; height: 18px; flex-shrink: 0;
        }
        .sa-nav-item.disabled {
            opacity: .4; cursor: not-allowed;
        }
        .sa-nav-item.disabled:hover {
            background: transparent;
            color: var(--sa-side-muted);
        }

        /* ── Collapsed sidebar: center icons ──────────────────── */
        .sidebar-collapsed .sa-nav-item {
            justify-content: center;
            gap: 0;
            padding: 10px;
            border-left-color: transparent !important;
        }
        .sidebar-collapsed .sa-nav-item .nav-label { display: none; }
        .sidebar-collapsed .sidebar-section-label { display: none; }

        /* ── Section label ────────────────────────────────────── */
        .sidebar-section-label {
            font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .08em;
            color: rgba(255,255,255,.2);
            padding: 16px 14px 4px;
            white-space: nowrap;
        }

        /* ── Mobile: hide sidebar by default ─────────────────── */
        @media (max-width: 767px) {
            .sa-sidebar { transform: translateX(-100%); }
            .sa-sidebar.mobile-open { transform: translateX(0); width: 260px !important; }
            .sa-header { left: 0 !important; }
            .sa-main { margin-left: 0 !important; }
            .hide-mobile { display: none !important; }
        }
    </style>

    @stack('styles')
</head>
<body x-data="{
    collapsed: false,
    mobileOpen: false,
    get sidebarW() { return this.collapsed ? '64px' : '232px' }
}">

{{-- ── Overlay (mobile) ─────────────────────────────────────────── --}}
<div class="sa-overlay" x-show="mobileOpen" @click="mobileOpen=false" x-cloak></div>

{{-- ── Sidebar ───────────────────────────────────────────────────── --}}
<aside class="sa-sidebar"
       :class="{ 'sidebar-collapsed': collapsed, 'mobile-open': mobileOpen }"
       :style="{ width: sidebarW }">

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
           style="width:100%;display:flex;align-items:center;justify-content:center;gap:7px;background:var(--sa-side-accent);color:#fff;border:none;border-radius:9px;padding:10px 0;cursor:pointer;font-weight:600;font-size:13px;text-decoration:none;transition:opacity 200ms">
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
            ['section' => null,           'route' => 'dashboard',         'label' => 'Dashboard',      'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',                                        'match' => 'dashboard'],
            ['section' => null,           'route' => 'agendamentos.index', 'label' => 'Agenda',          'icon' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>', 'match' => 'agendamentos.*'],
            ['section' => null,           'route' => 'calendario',         'label' => 'Calendário',      'icon' => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="8" y1="14" x2="8" y2="14"/><line x1="12" y1="14" x2="12" y2="14"/><line x1="16" y1="14" x2="16" y2="14"/>',                                    'match' => 'calendario'],
            ['section' => 'Gestão',       'route' => 'clientes.index',      'label' => 'Clientes',        'icon' => '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>',             'match' => 'clientes.*'],
            ['section' => null,           'route' => 'profissionais.index', 'label' => 'Profissionais',   'icon' => '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',                                                        'match' => 'profissionais.*'],
            ['section' => null,           'route' => 'servicos.index',      'label' => 'Serviços',        'icon' => '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/>',   'match' => 'servicos.*'],
            ['section' => 'Financeiro',   'route' => null,                 'label' => 'Financeiro',      'icon' => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>',                                          'match' => null],
            ['section' => null,           'route' => null,                 'label' => 'PDV',             'icon' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 001.98 1.61h9.72a2 2 0 001.98-1.61L23 6H6"/>',    'match' => null],
            ['section' => null,           'route' => 'relatorios',         'label' => 'Relatórios',     'icon' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',                       'match' => 'relatorios'],
            ['section' => 'Configurações','route' => null,                 'label' => 'Permissões',     'icon' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>',                                              'match' => null],
            ['section' => null,           'route' => null,                 'label' => 'Planos',          'icon' => '<path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>', 'match' => null],
            ['section' => null,           'route' => 'perfil',             'label' => 'Meu Perfil',      'icon' => '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>',                                                    'match' => 'perfil*'],
            ['section' => null,           'route' => 'configuracoes',      'label' => 'Configurações',  'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>',  'match' => 'configuracoes*'],
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
                $isActive  = $item['match'] && request()->routeIs($item['match']);
                $hasRoute  = $item['route'] !== null;
                $href      = $hasRoute ? route($item['route']) : '#';
                $classes   = 'sa-nav-item' . ($isActive ? ' active' : '') . (!$hasRoute ? ' disabled' : '');
            @endphp

            <a href="{{ $href }}"
               class="{{ $classes }}"
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
        <div class="nav-label" style="padding:12px 16px 0">
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
            <div class="nav-label" style="flex:1;min-width:0">
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

{{-- ── Header ─────────────────────────────────────────────────────── --}}
<header class="sa-header" :style="{ left: sidebarW }">
    <div style="display:flex;align-items:center;gap:12px">
        {{-- Collapse toggle (desktop) --}}
        <button @click="collapsed = !collapsed"
                class="hide-mobile"
                style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:none;background:transparent;cursor:pointer;color:var(--sa-text2);border-radius:7px;transition:background 150ms"
                onmouseover="this.style.background='var(--sa-border)'" onmouseout="this.style.background='transparent'">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
        {{-- Mobile menu toggle --}}
        <button @click="mobileOpen = !mobileOpen"
                style="width:32px;height:32px;display:none;align-items:center;justify-content:center;border:none;background:transparent;cursor:pointer;color:var(--sa-text2);border-radius:7px"
                class="mobile-menu-btn"
                x-bind:style="window.innerWidth < 768 ? 'display:flex' : 'display:none'">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
        <span style="font-size:15px;font-weight:600;color:var(--sa-text1)">@yield('page-title', 'Dashboard')</span>
    </div>

    <div style="display:flex;align-items:center;gap:10px">
        {{-- Role badge --}}
        <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;background:rgba(212,165,116,.12);color:var(--sa-secondary);text-transform:uppercase;letter-spacing:.04em">
            {{ auth()->user()->getRoleNames()->first() ?? '—' }}
        </span>
        {{-- User name --}}
        <span style="font-size:13px;font-weight:500;color:var(--sa-text2)">{{ auth()->user()->name }}</span>
    </div>
</header>

{{-- ── Main content ───────────────────────────────────────────────── --}}
<main class="sa-main" :style="{ marginLeft: sidebarW }">
    <div style="padding:24px">

        @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             style="margin-bottom:16px;padding:12px 16px;background:rgba(5,150,105,.08);border:1px solid rgba(5,150,105,.2);border-radius:10px;color:#065f46;font-size:14px;display:flex;align-items:center;justify-content:space-between">
            <span>{{ session('success') }}</span>
            <button @click="show=false" style="background:none;border:none;cursor:pointer;color:inherit;margin-left:12px;font-size:18px;line-height:1">&times;</button>
        </div>
        @endif

        @if($errors->any())
        <div style="margin-bottom:16px;padding:12px 16px;background:rgba(229,62,62,.06);border:1px solid rgba(229,62,62,.2);border-radius:10px;color:#c53030;font-size:14px">
            <ul style="list-style:disc;padding-left:18px;display:flex;flex-direction:column;gap:4px">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @yield('content')
    </div>
</main>

<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js');
    }
    // Fix mobile menu button visibility via JS (CSS media query cannot bind to Alpine)
    (function() {
        const btn = document.querySelector('.mobile-menu-btn');
        if (btn) {
            function update() { btn.style.display = window.innerWidth < 768 ? 'flex' : 'none'; }
            update();
            window.addEventListener('resize', update);
        }
    })();
</script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@stack('scripts')
</body>
</html>
