<!DOCTYPE html>
<html lang="pt-BR" x-data="{ darkMode: true }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#059669">
    <link rel="manifest" href="/manifest.json">
    <title>@yield('title', config('app.name')) | {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;900&family=JetBrains+Mono:wght@700&display=swap" rel="stylesheet">
    <script>tailwind.config={darkMode:'class',theme:{extend:{fontFamily:{sans:['Inter','sans-serif'],mono:['JetBrains Mono','monospace']}}}}</script>
    <style>[x-cloak]{display:none!important}.bg-hero{background-image:url('https://images.unsplash.com/photo-1517836357463-d25dfeac3438?q=80&w=2000&auto=format&fit=crop');background-size:cover;background-position:center}.overlay-gradient{background:linear-gradient(to right,rgba(2,6,23,.9) 0%,rgba(2,6,23,.4) 100%)}</style>
</head>
<body class="bg-white dark:bg-slate-950 text-slate-900 dark:text-white font-sans antialiased overflow-hidden">
<div class="flex h-screen w-full">
    <div class="hidden lg:flex lg:w-[65%] relative bg-hero grayscale-[40%]">
        <div class="absolute inset-0 overlay-gradient flex flex-col justify-center p-24">
            <div class="max-w-2xl">
                <div class="mb-8 text-emerald-500 font-black text-3xl tracking-tighter uppercase">{{ config('app.name') }}</div>
                <h1 class="text-white text-7xl font-black leading-tight tracking-tighter uppercase italic">@yield('hero-title', 'Acesso <br><span class="text-emerald-500">Seguro.</span>')</h1>
                <p class="text-slate-400 mt-6 text-xl font-light leading-relaxed max-w-lg">@yield('hero-subtitle', 'Bem-vindo ao sistema de gestão. Insira suas credenciais para acessar o painel administrativo.')</p>
            </div>
        </div>
        <div class="absolute bottom-10 left-24 flex items-center gap-2 text-slate-500 text-xs font-mono">
            <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
            SISTEMA OPERACIONAL // {{ date('Y') }}
        </div>
    </div>
    <div class="w-full lg:w-[35%] flex flex-col bg-white dark:bg-slate-950 p-8 md:p-14 border-l border-slate-200 dark:border-slate-900 overflow-y-auto">
        <div class="my-auto">
            @yield('content')
        </div>
        <p class="text-[9px] text-slate-400 dark:text-slate-600 text-center leading-relaxed uppercase mt-auto pt-6">
            © {{ date('Y') }} {{ config('app.name') }}
        </p>
    </div>
</div>
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js');
    }
</script>
</body>
</html>
