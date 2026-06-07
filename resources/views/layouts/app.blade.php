<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#059669">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/manifest.json">
    <title>@yield('title', 'Dashboard') | {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://unpkg.com/lucide@latest" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Inter','sans-serif']}}}}</script>
    <style>[x-cloak]{display:none!important}</style>
    @stack('styles')
</head>
<body class="bg-slate-100 text-slate-800 font-sans antialiased" x-data="{ sidebarOpen: true }">

{{-- Header --}}
<header class="fixed top-0 left-0 right-0 h-14 bg-white border-b border-slate-200 flex items-center justify-between px-4 z-50">
    <div class="flex items-center gap-3">
        <button @click="sidebarOpen = !sidebarOpen" class="p-1.5 rounded-md hover:bg-slate-100 text-slate-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <span class="text-sm font-bold text-emerald-700 tracking-tight">{{ config('app.name') }}</span>
    </div>
    <div class="flex items-center gap-3">
        <span class="text-xs text-slate-500 hidden sm:block">{{ auth()->user()->name }}</span>
        <span class="text-[10px] bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-semibold uppercase">{{ auth()->user()->getRoleNames()->first() ?? '-' }}</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-xs px-3 py-1.5 border border-slate-200 rounded-md text-slate-500 hover:bg-slate-50 transition">Sair</button>
        </form>
    </div>
</header>

{{-- Sidebar --}}
<aside class="fixed top-14 left-0 bottom-0 w-52 bg-slate-900 text-white flex flex-col transition-transform duration-200 z-40"
       :class="{ '-translate-x-full': !sidebarOpen }" x-cloak>
    <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto">
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-2.5 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('dashboard') ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Dashboard
        </a>
        <a href="{{ route('agendamentos.index') }}"
           class="flex items-center gap-2.5 px-3 py-2 text-sm rounded-lg transition {{ request()->routeIs('agendamentos.*') ? 'bg-emerald-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Agendamentos
        </a>
    </nav>
    <div class="p-3 border-t border-slate-700 text-[10px] text-slate-500 font-mono">
        v1.1.0-beta
    </div>
</aside>

{{-- Main Content --}}
<main class="transition-all duration-200" :class="{ 'ml-52': sidebarOpen, 'ml-0': !sidebarOpen }" style="padding-top:56px">
    <div class="p-6">
        @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="mb-4 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-800 text-sm flex items-center justify-between">
            <span>{{ session('success') }}</span>
            <button @click="show = false" class="ml-3 text-emerald-500 hover:text-emerald-700">&times;</button>
        </div>
        @endif

        @if($errors->any())
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-red-800 text-sm">
            <ul class="list-disc list-inside space-y-1">
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
</script>
@stack('scripts')
</body>
</html>
