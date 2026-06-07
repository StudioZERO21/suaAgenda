@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<h2 class="text-3xl font-black tracking-tighter uppercase mb-2">Login</h2>
<p class="text-slate-500 dark:text-slate-400 text-sm mb-10">Insira suas credenciais para acessar o painel de controle.</p>

<form method="POST" action="{{ route('login') }}" class="space-y-8">
    @csrf

    <div class="group">
        <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 group-focus-within:text-emerald-500 transition-colors">E-mail</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus
               class="w-full mt-2 bg-transparent border-b-2 border-slate-200 dark:border-slate-800 py-3 font-mono text-lg outline-none focus:border-emerald-500 transition-all">
        @error('email')<p class="text-red-500 text-xs mt-2 font-bold">{{ $message }}</p>@enderror
    </div>

    <div class="group">
        <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 group-focus-within:text-emerald-500 transition-colors">Senha</label>
        <input type="password" name="password" required
               class="w-full mt-2 bg-transparent border-b-2 border-slate-200 dark:border-slate-800 py-3 font-mono text-lg outline-none focus:border-emerald-500 transition-all">
        @error('password')<p class="text-red-500 text-xs mt-2 font-bold">{{ $message }}</p>@enderror
    </div>

    <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 text-xs text-slate-500 cursor-pointer">
            <input type="checkbox" name="remember" class="accent-emerald-600"> Lembrar-me
        </label>
    </div>

    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white py-5 rounded-lg font-black tracking-[0.2em] shadow-xl transition-all uppercase">
        Acessar Sistema
    </button>
</form>

<p class="text-center text-sm text-slate-500 dark:text-slate-400 mt-8">
    Não tem conta?
    <a href="{{ route('register') }}" class="text-emerald-500 font-semibold hover:underline">Criar gratuitamente</a>
</p>
@endsection
