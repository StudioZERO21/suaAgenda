<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Agendamento') — suaAgenda</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --sa-primary:     #1a1a1a;
            --sa-primary-l:   #2d2d2d;
            --sa-secondary:   #d4a574;
            --sa-secondary-l: #e6c299;
            --sa-bg:          #f5f5f5;
            --sa-surface:     #ffffff;
            --sa-surface2:    #fafafa;
            --sa-text1:       #1a1a1a;
            --sa-text2:       #5a5a5a;
            --sa-text3:       #999999;
            --sa-border:      #e2e2e2;
            --sa-border2:     #d0d0d0;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--sa-bg);
            color: var(--sa-text1);
            min-height: 100vh;
        }
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
    </style>
    @stack('styles')
</head>
<body>
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
            <span style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:#fff;letter-spacing:-.2px">suaAgenda</span><span style="font-size:11px;color:var(--sa-secondary);font-weight:600;letter-spacing:.4px">.pro</span>
        </div>
        @yield('header-right')
    </header>

    <main class="pub-main">
        @yield('content')
    </main>
</body>
</html>
