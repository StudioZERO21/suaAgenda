<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        use App\Support\SaPalettes;
        $pubSettings = isset($company) ? $company->resolvedSettings() : [];
        $pubSite     = $siteCfg ?? ($pubSettings['site'] ?? []);
        $pubHeading  = $pubSettings['heading_font'] ?? 'poppins';
        $pubBody     = $pubSettings['body_font'] ?? 'inter';
        $pubFonts    = SaPalettes::resolveFonts($pubHeading, $pubBody);
        $pubPalette  = $pubSettings['theme_palette'] ?? 'A';
        $pubDark     = (bool) ($pubSettings['dark_mode'] ?? false);
        $pubThemeVars = SaPalettes::cssVariables($pubPalette, $pubDark);
        $pubMetaTitle = !empty($pubSite['meta_title']) ? $pubSite['meta_title'] : null;
        $pubMetaDesc  = !empty($pubSite['meta_desc'])  ? $pubSite['meta_desc']  : null;
        $pubKeywords  = !empty($pubSite['keywords'])   ? $pubSite['keywords']   : null;
        // og:image precisa ser absoluta para os crawlers; url() usa o host atual.
        $pubOgImage   = !empty($pubSite['og_image'])   ? url(\Illuminate\Support\Facades\Storage::url($pubSite['og_image'])) : null;
        $pubGa        = !empty($pubSite['google_analytics']) ? $pubSite['google_analytics'] : null;
    @endphp
    <title>{{ $pubMetaTitle ?? (isset($company) ? $company->name.' — Agendamento Online' : 'Agendamento') }}</title>
    @if($pubMetaDesc)
    <meta name="description" content="{{ $pubMetaDesc }}">
    @endif
    @if($pubKeywords)
    <meta name="keywords" content="{{ $pubKeywords }}">
    @endif
    @if($pubMetaTitle)
    <meta property="og:title" content="{{ $pubMetaTitle }}">
    @endif
    @if($pubMetaDesc)
    <meta property="og:description" content="{{ $pubMetaDesc }}">
    @endif
    @if($pubOgImage)
    <meta property="og:image" content="{{ $pubOgImage }}">
    @endif
    @if($pubGa)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $pubGa }}"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{{ $pubGa }}');</script>
    @endif
    @stack('meta')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ $pubFonts['google_url'] }}" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        [x-cloak] { display: none !important; }
        :root {
            {{-- Whitelist em SaPalettes: seguro emitir sem escape. --}}
            --sa-font-body:    {!! $pubFonts['body_css'] !!};
            --sa-font-heading: {!! $pubFonts['heading_css'] !!};
            {{-- Tema (paleta + dark) selecionado pela empresa no painel. --}}
            {!! $pubThemeVars !!};
        }
        body {
            font-family: var(--sa-font-body);
            background: var(--sa-bg);
            color: var(--sa-text1);
            min-height: 100vh;
        }
        h1, h2, h3, h4 { font-family: var(--sa-font-heading); }
        input, button, select, textarea { font-family: var(--sa-font-body); }
        .pub-header {
            background: var(--sa-primary);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .pub-main {
            max-width: 680px;
            margin: 0 auto;
            padding: 32px 20px 60px;
        }
        @media (max-width: 640px) { .pub-main { padding: 20px 14px 48px; } }

        /* SweetAlert2 — botão cancelar sempre visível (páginas públicas) */
        .swal2-cancel.swal-cancel-muted {
            border: 1.5px solid var(--sa-border) !important;
            background: var(--sa-surface) !important;
            color: var(--sa-text2) !important;
            font-weight: 600 !important;
            box-shadow: none !important;
        }
        .swal2-cancel.swal-cancel-muted:hover {
            border-color: var(--sa-primary) !important;
            color: var(--sa-text1) !important;
            background: var(--sa-surface2) !important;
        }
    </style>
    @stack('styles')
</head>
<body>
    @hasSection('fullBleed')
        {{-- Landing pública (vitrine): cabeçalho e largura próprios da view --}}
        @yield('content')
    @else
        <header class="pub-header">
            <div style="width:30px;height:30px;border-radius:8px;background:var(--sa-secondary);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/>
                    <line x1="20" y1="4" x2="8.12" y2="15.88"/>
                    <line x1="14.47" y1="14.48" x2="20" y2="20"/>
                    <line x1="8.12" y1="8.12" x2="12" y2="12"/>
                </svg>
            </div>
            <div>
                <span style="font-family:var(--sa-font-heading);font-size:15px;font-weight:700;color:#fff;letter-spacing:-.2px">suaAgenda</span><span style="font-size:11px;color:var(--sa-secondary);font-weight:600;letter-spacing:.4px">.pro</span>
            </div>
            @yield('header-right')
        </header>

        <main class="pub-main">
            @yield('content')
        </main>
    @endif

    @stack('scripts')
</body>
</html>
