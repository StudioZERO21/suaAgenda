<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Minha área') — {{ $company->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --sa-primary: #1a1a1a; --sa-primary-l: #2d2d2d;
            --sa-secondary: #d4a574; --sa-bg: #f5f5f5; --sa-surface: #ffffff; --sa-surface2: #fafafa;
            --sa-text1: #1a1a1a; --sa-text2: #5a5a5a; --sa-text3: #999999;
            --sa-border: #e2e2e2; --sa-border2: #d0d0d0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--sa-bg); color: var(--sa-text1); min-height: 100vh; }
        .portal-wrap { max-width: 720px; margin: 0 auto; padding: 0 16px 96px; }
        .portal-header {
            background: var(--sa-primary); color: #fff;
            padding: calc(env(safe-area-inset-top, 0) + 20px) 16px 22px;
            border-radius: 0 0 20px 20px; margin-bottom: 20px;
        }
        .portal-header-inner { max-width: 720px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        a { color: inherit; }
        @media (max-width: 380px) {
            .portal-wrap { padding: 0 12px 96px; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="portal-header">
        <div class="portal-header-inner">
            <div style="display:flex;align-items:center;gap:10px;min-width:0">
                @if($company->logo_path)
                <div style="width:38px;height:38px;border-radius:10px;overflow:hidden;flex-shrink:0;background:#fff">
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($company->logo_path) }}" alt="" style="width:100%;height:100%;object-fit:cover">
                </div>
                @else
                <div style="width:38px;height:38px;border-radius:10px;background:var(--sa-secondary);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:800;color:#fff;font-family:'Poppins',sans-serif">{{ strtoupper(substr($company->name, 0, 1)) }}</div>
                @endif
                <div style="min-width:0">
                    <div style="font-family:'Poppins',sans-serif;font-weight:700;font-size:15px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $company->name }}</div>
                    <div style="font-size:11px;color:rgba(255,255,255,.6)">Minha área</div>
                </div>
            </div>
            @auth('cliente')
            <form method="POST" action="{{ route('portal.logout', $company->slug) }}">
                @csrf
                <button type="submit" style="background:rgba(255,255,255,.12);border:none;color:#fff;font-size:13px;font-weight:600;padding:8px 14px;border-radius:8px;cursor:pointer">Sair</button>
            </form>
            @endauth
        </div>
    </div>

    <div class="portal-wrap">
        @if(session('sucesso'))
        <div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:14px;color:#059669">{{ session('sucesso') }}</div>
        @endif
        @if(session('erro'))
        <div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:14px;color:#dc2626">{{ session('erro') }}</div>
        @endif

        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
