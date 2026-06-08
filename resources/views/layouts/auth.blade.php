<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#d4a574">
    <link rel="manifest" href="/manifest.json">
    <title>@yield('title', 'Login') | suaAgenda</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; font-size: 16px; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }

        :root {
            --sa-primary:     #1a1a1a;
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

        body { font-family: 'Inter', -apple-system, sans-serif; background: var(--sa-bg); color: var(--sa-text1); }
        h1, h2, h3, h4 { font-family: 'Poppins', sans-serif; }
        input, button, select, textarea { font-family: 'Inter', -apple-system, sans-serif; }

        *:focus-visible { outline: 2px solid var(--sa-secondary); outline-offset: 2px; }
        ::selection { background: var(--sa-secondary); color: #fff; }
        [x-cloak] { display: none !important; }

        /* ── Auth layout ──────────────────────────────── */
        .auth-wrap { display: flex; min-height: 100vh; }

        .auth-hero {
            flex: 1;
            background: linear-gradient(145deg, #111 0%, #1a1a1a 40%, #0f0f0f 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px 56px;
            position: relative;
            overflow: hidden;
        }

        .auth-form-col {
            width: 480px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 48px;
            background: var(--sa-surface);
            overflow-y: auto;
        }

        @media (max-width: 900px) {
            .auth-hero { display: none; }
            .auth-form-col { width: 100%; padding: 32px 24px; }
        }

        /* ── Form primitives ──────────────────────────── */
        .sa-label {
            display: block; font-size: 13px; font-weight: 600;
            color: var(--sa-text1); margin-bottom: 5px; letter-spacing: .2px;
        }

        .sa-field { position: relative; display: flex; align-items: center; }

        .sa-field-icon {
            position: absolute; left: 11px; color: var(--sa-text3);
            display: flex; align-items: center; pointer-events: none;
        }

        .sa-input {
            width: 100%; padding: 10px 13px 10px 36px;
            border: 1.5px solid var(--sa-border); border-radius: 8px;
            font-size: 14px; background: var(--sa-surface); color: var(--sa-text1);
            outline: none; transition: border-color 180ms, box-shadow 180ms;
            box-sizing: border-box;
        }
        .sa-input:focus { border-color: var(--sa-primary); box-shadow: 0 0 0 3px rgba(0,0,0,.06); }
        .sa-input.is-error { border-color: #e53e3e; }

        .sa-error { font-size: 12px; color: #e53e3e; margin-top: 4px; }

        .sa-btn-primary {
            width: 100%; padding: 12px 28px; border-radius: 8px; border: none;
            cursor: pointer; font-size: 15px; font-weight: 600; height: 48px;
            background: var(--sa-primary); color: #fff;
            transition: filter 200ms ease;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .sa-btn-primary:hover { filter: brightness(1.1); }

        .sa-btn-outline {
            flex: 1; padding: 10px 0; border: 1.5px solid var(--sa-border);
            border-radius: 9px; background: var(--sa-surface); cursor: pointer;
            font-size: 13px; font-weight: 600; color: var(--sa-text2); transition: all 180ms;
        }
        .sa-btn-outline:hover { border-color: var(--sa-secondary); background: rgba(212,165,116,.04); }

        .sa-divider {
            display: flex; align-items: center; gap: 12px;
            color: var(--sa-text3); font-size: 12px; margin: 4px 0;
        }
        .sa-divider::before, .sa-divider::after { content: ''; flex: 1; height: 1px; background: var(--sa-border); }

        .sa-tabs {
            display: flex; background: var(--sa-surface2);
            border-radius: 10px; padding: 4px; margin-bottom: 36px;
        }
        .sa-tab {
            flex: 1; padding: 9px 0; border-radius: 8px; border: none; cursor: pointer;
            font-size: 14px; font-weight: 600; background: transparent; color: var(--sa-text3);
            transition: all 200ms; text-decoration: none; text-align: center; display: block;
        }
        .sa-tab.active {
            background: var(--sa-surface); color: var(--sa-text1);
            box-shadow: 0 1px 4px rgba(0,0,0,.1);
        }

        /* ── Dev quick-login panel ────────────────────── */
        .dev-panel {
            margin-top: 24px; border: 1.5px dashed var(--sa-border);
            border-radius: 12px; padding: 16px; background: var(--sa-surface2);
        }
        .dev-panel-title {
            font-size: 10px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .06em; color: var(--sa-text3); margin-bottom: 10px;
        }
        .dev-user-btn {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 10px; border-radius: 8px;
            border: 1px solid var(--sa-border); background: var(--sa-surface);
            cursor: pointer; text-align: left; transition: all 150ms;
            width: 100%; font-family: 'Inter', sans-serif;
        }
        .dev-user-btn:hover { border-color: var(--sa-secondary); background: rgba(212,165,116,.05); }

        .dev-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            color: #fff; display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700; flex-shrink: 0; letter-spacing: -.5px;
        }
        .dev-user-name { font-size: 13px; font-weight: 600; color: var(--sa-text1); line-height: 1.3; }
        .dev-user-email { font-size: 11px; color: var(--sa-text3); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .dev-badge {
            font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 20px;
            text-transform: uppercase; letter-spacing: .04em;
            background: rgba(212,165,116,.15); color: var(--sa-secondary); flex-shrink: 0;
        }

        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--sa-border2); border-radius: 3px; }
    </style>
</head>
<body>
    @hasSection('authBare')
    {{-- ── Modo centralizado (sem hero): recuperação de senha ─── --}}
    <div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px">
        <div style="width:100%;max-width:440px">
            @yield('content')
        </div>
    </div>
    @else
    <div class="auth-wrap">

        {{-- ── Hero Left ────────────────────────────────────────── --}}
        <div class="auth-hero">
            <div style="position:absolute;top:-80px;right:-80px;width:320px;height:320px;border-radius:50%;background:var(--sa-secondary);opacity:.08"></div>
            <div style="position:absolute;bottom:-60px;left:-60px;width:240px;height:240px;border-radius:50%;background:var(--sa-secondary);opacity:.06"></div>

            {{-- Logo --}}
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:56px;position:relative;z-index:1">
                <div style="width:44px;height:44px;border-radius:12px;background:var(--sa-secondary);display:flex;align-items:center;justify-content:center">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/>
                        <line x1="20" y1="4" x2="8.12" y2="15.88"/>
                        <line x1="14.47" y1="14.48" x2="20" y2="20"/>
                        <line x1="8.12" y1="8.12" x2="12" y2="12"/>
                    </svg>
                </div>
                <div style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:#fff">
                    suaAgenda<span style="color:var(--sa-secondary)">.pro</span>
                </div>
            </div>

            {{-- Headline --}}
            <h2 style="position:relative;z-index:1;font-size:clamp(28px,3vw,36px);font-weight:700;color:#fff;line-height:1.2;margin:0 0 16px;max-width:420px">
                O sistema de agendamento que<br>
                <span style="color:var(--sa-secondary)">transforma seu negócio</span>
            </h2>
            <p style="position:relative;z-index:1;font-size:16px;color:rgba(255,255,255,.6);margin:0 0 40px;line-height:1.7;max-width:380px">
                Gestão completa para barbearias, salões e estúdios de beleza.
            </p>

            {{-- Features --}}
            @php
            $features = [
                ['d' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',                                                                          'text' => 'Agenda inteligente com notificações automáticas'],
                ['d' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'text' => 'Gestão de clientes e histórico completo'],
                ['d' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'text' => 'Relatórios financeiros em tempo real'],
                ['d' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',                                                           'text' => 'Página de agendamento pública personalizada'],
            ];
            @endphp
            <div style="position:relative;z-index:1;display:flex;flex-direction:column;gap:16px">
                @foreach($features as $f)
                <div style="display:flex;align-items:center;gap:14px">
                    <div style="width:36px;height:36px;border-radius:9px;background:rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="{{ $f['d'] }}"/>
                        </svg>
                    </div>
                    <span style="font-size:14px;color:rgba(255,255,255,.75);line-height:1.5">{{ $f['text'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ── Form Right ───────────────────────────────────────── --}}
        <div class="auth-form-col">
            <div style="width:100%;max-width:380px">
                @yield('content')
            </div>
        </div>

    </div>
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

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
    @stack('scripts')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
