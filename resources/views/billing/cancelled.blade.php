<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinatura cancelada — suaAgenda.pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f5f5f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { background: #fff; border-radius: 20px; border: 1px solid #e2e2e2; padding: 48px 40px; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 8px 40px rgba(0,0,0,.08); }
        .icon { width: 72px; height: 72px; border-radius: 50%; background: rgba(107,114,128,.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; }
        h1 { font-family: 'Poppins', sans-serif; font-size: 22px; font-weight: 800; color: #1a1a1a; margin-bottom: 12px; }
        p { font-size: 14px; color: #5a5a5a; line-height: 1.7; margin-bottom: 10px; }
        .info-box { background: rgba(107,114,128,.06); border: 1px solid rgba(107,114,128,.2); border-radius: 12px; padding: 16px; margin: 20px 0; text-align: left; font-size: 13px; color: #5a5a5a; line-height: 1.6; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 600; font-family: 'Inter', sans-serif; text-decoration: none; transition: filter 200ms; }
        .btn-primary { background: #d4a574; color: #1a1a1a; border: none; cursor: pointer; }
        .btn-primary:hover { filter: brightness(1.08); }
        .btn-ghost { background: transparent; color: #5a5a5a; border: 1.5px solid #e2e2e2; margin-left: 8px; font-size: 13px; cursor: pointer; }
        .btn-ghost:hover { border-color: #1a1a1a; color: #1a1a1a; }
        .logo { font-family: 'Poppins', sans-serif; font-size: 18px; font-weight: 800; color: #1a1a1a; margin-bottom: 32px; }
        .logo span { color: #d4a574; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">sua<span>Agenda</span>.pro</div>
        <div class="icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
        </div>
        <h1>Assinatura Cancelada</h1>
        <p>Sua assinatura suaAgenda.pro foi cancelada após o período de inadimplência.</p>
        <div class="info-box">
            Seus dados ficam preservados por <strong>90 dias</strong> a partir do cancelamento. Para reativar a conta ou recuperar seus dados, entre em contato com nosso suporte o quanto antes.
        </div>
        <div style="display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:8px;margin-top:8px">
            <a href="mailto:suporte@suaagenda.pro" class="btn btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.5a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.09a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0121.15 15z"/></svg>
                Falar com Suporte
            </a>
            <form method="POST" action="{{ route('logout') }}" style="display:inline">
                @csrf
                <button type="submit" class="btn btn-ghost">Sair</button>
            </form>
        </div>
    </div>
</body>
</html>
