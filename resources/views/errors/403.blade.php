<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso negado — suaAgenda</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --sa-primary: #1a1a1a;
            --sa-secondary: #d4a574;
            --sa-bg: #f5f5f5;
            --sa-surface: #ffffff;
            --sa-text1: #1a1a1a;
            --sa-text2: #5a5a5a;
            --sa-text3: #999999;
            --sa-border: #e2e2e2;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--sa-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div style="background:var(--sa-surface);border-radius:16px;border:1px solid var(--sa-border);padding:48px 40px;box-shadow:0 1px 3px rgba(0,0,0,.05);max-width:440px;width:100%;text-align:center">
        <div style="width:64px;height:64px;border-radius:50%;background:rgba(239,68,68,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2"/>
                <path d="M7 11V7a5 5 0 0110 0v4"/>
            </svg>
        </div>
        <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 8px">
            Acesso negado
        </h1>
        <p style="font-size:14px;color:var(--sa-text2);margin:0 0 6px;line-height:1.6">
            Você não tem permissão para acessar o que está solicitando.
        </p>
        <p style="font-size:13px;color:var(--sa-text3);margin:0 0 28px;line-height:1.6">
            Se você acredita que deveria ter acesso, fale com o administrador da sua empresa.
        </p>
        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}"
           style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:var(--sa-primary);color:#fff;text-decoration:none;transition:filter 200ms"
           onmouseover="this.style.filter='brightness(1.1)'"
           onmouseout="this.style.filter='none'">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>
            Voltar ao início
        </a>
    </div>
</body>
</html>
