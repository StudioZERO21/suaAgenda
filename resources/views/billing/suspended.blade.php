<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conta suspensa — suaAgenda.pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f5f5f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { background: #fff; border-radius: 20px; border: 1px solid #e2e2e2; padding: 48px 40px; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 8px 40px rgba(0,0,0,.08); }
        .icon { width: 72px; height: 72px; border-radius: 50%; background: rgba(239,68,68,.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; }
        h1 { font-family: 'Poppins', sans-serif; font-size: 22px; font-weight: 800; color: #1a1a1a; margin-bottom: 12px; }
        p { font-size: 14px; color: #5a5a5a; line-height: 1.7; margin-bottom: 10px; }
        .info-box { background: rgba(239,68,68,.06); border: 1px solid rgba(239,68,68,.2); border-radius: 12px; padding: 16px; margin: 20px 0; text-align: left; font-size: 13px; color: #5a5a5a; line-height: 1.6; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 600; font-family: 'Inter', sans-serif; text-decoration: none; transition: filter 200ms; }
        .btn-primary { background: #1a1a1a; color: #fff; border: none; cursor: pointer; }
        .btn-primary:hover { filter: brightness(1.15); }
        .btn-ghost { background: transparent; color: #5a5a5a; border: 1.5px solid #e2e2e2; margin-left: 8px; font-size: 13px; }
        .btn-ghost:hover { border-color: #1a1a1a; color: #1a1a1a; }
        .logo { font-family: 'Poppins', sans-serif; font-size: 18px; font-weight: 800; color: #1a1a1a; margin-bottom: 32px; }
        .logo span { color: #d4a574; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">sua<span>Agenda</span>.pro</div>
        <div class="icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
        </div>
        <h1>Conta Suspensa</h1>
        <p>Sua conta foi suspensa por inadimplência. Regularize o pagamento para restaurar o acesso imediatamente.</p>
        <div class="info-box">
            <strong style="color:#dc2626">O que isso significa:</strong><br>
            • O painel de agendamentos está bloqueado<br>
            • Agendamentos públicos estão desativados<br>
            • Seus dados estão preservados e seguros<br><br>
            Após confirmar o pagamento, o acesso é restaurado em até 5 minutos.
        </div>
        <div style="display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:8px;margin-top:8px">
            <a href="mailto:financeiro@suaagenda.pro" class="btn btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Contato Financeiro
            </a>
            <form method="POST" action="{{ route('logout') }}" style="display:inline">
                @csrf
                <button type="submit" class="btn btn-ghost">Sair</button>
            </form>
        </div>
    </div>
</body>
</html>
