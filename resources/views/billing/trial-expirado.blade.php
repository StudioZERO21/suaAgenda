<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trial encerrado — suaAgenda.pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f5f5f5; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { background: #fff; border-radius: 20px; border: 1px solid #e2e2e2; padding: 48px 40px; max-width: 520px; width: 100%; text-align: center; box-shadow: 0 8px 40px rgba(0,0,0,.08); }
        .icon { width: 72px; height: 72px; border-radius: 50%; background: rgba(212,165,116,.15); display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; }
        h1 { font-family: 'Poppins', sans-serif; font-size: 22px; font-weight: 800; color: #1a1a1a; margin-bottom: 12px; }
        p { font-size: 14px; color: #5a5a5a; line-height: 1.7; margin-bottom: 10px; }
        .plans { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 24px 0; text-align: left; }
        .plan { border: 1.5px solid #e2e2e2; border-radius: 12px; padding: 14px 16px; }
        .plan.destaque { border-color: #d4a574; background: rgba(212,165,116,.06); }
        .plan-name { font-weight: 700; font-size: 13px; color: #1a1a1a; margin-bottom: 2px; }
        .plan-price { font-size: 18px; font-weight: 800; color: #d4a574; font-family: 'Poppins', sans-serif; }
        .plan-price span { font-size: 12px; color: #999; font-weight: 400; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 13px 28px; border-radius: 10px; font-size: 14px; font-weight: 700; font-family: 'Inter', sans-serif; text-decoration: none; transition: filter 200ms; }
        .btn-primary { background: #1a1a1a; color: #fff; border: none; cursor: pointer; width: 100%; margin-bottom: 10px; }
        .btn-primary:hover { filter: brightness(1.2); }
        .btn-ghost { background: transparent; color: #5a5a5a; border: 1.5px solid #e2e2e2; font-size: 13px; cursor: pointer; }
        .btn-ghost:hover { border-color: #1a1a1a; color: #1a1a1a; }
        .logo { font-family: 'Poppins', sans-serif; font-size: 18px; font-weight: 800; color: #1a1a1a; margin-bottom: 32px; }
        .logo span { color: #d4a574; }
        .badge { display: inline-block; background: rgba(212,165,116,.15); color: #c07836; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 20px; margin-bottom: 14px; letter-spacing: .5px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">sua<span>Agenda</span>.pro</div>
        <div class="icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#d4a574" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="badge">Trial encerrado</div>
        <h1>Seu período de teste terminou</h1>
        <p>Você usou todos os 7 dias do trial gratuito. Escolha um plano para continuar gerenciando seus agendamentos.</p>

        <div class="plans">
            <div class="plan">
                <div class="plan-name">Starter</div>
                <div class="plan-price">R$&nbsp;49,90<span>/mês</span></div>
                <div style="font-size:11px;color:#999;margin-top:4px">1 profissional</div>
            </div>
            <div class="plan destaque">
                <div class="plan-name" style="color:#c07836">Crescimento ★</div>
                <div class="plan-price">R$&nbsp;99,90<span>/mês</span></div>
                <div style="font-size:11px;color:#999;margin-top:4px">Até 4 profissionais</div>
            </div>
            <div class="plan">
                <div class="plan-name">Profissional</div>
                <div class="plan-price">R$&nbsp;199,90<span>/mês</span></div>
                <div style="font-size:11px;color:#999;margin-top:4px">Até 15 profissionais</div>
            </div>
            <div class="plan">
                <div class="plan-name">Enterprise</div>
                <div class="plan-price" style="font-size:14px">A negociar</div>
                <div style="font-size:11px;color:#999;margin-top:4px">Ilimitado</div>
            </div>
        </div>

        <a href="{{ route('planos.index') }}" class="btn btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Escolher Plano
        </a>

        <div style="display:flex;align-items:center;justify-content:center;gap:8px;flex-wrap:wrap">
            <a href="mailto:suporte@suaagenda.pro" class="btn btn-ghost">Falar com Suporte</a>
            <form method="POST" action="{{ route('logout') }}" style="display:inline">
                @csrf
                <button type="submit" class="btn btn-ghost">Sair</button>
            </form>
        </div>
    </div>
</body>
</html>
