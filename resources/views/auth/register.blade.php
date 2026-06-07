@extends('layouts.auth')

@section('title', 'Criar Conta')

@section('content')
<h2 class="text-3xl font-black tracking-tighter uppercase mb-1">Criar Conta</h2>
<p class="text-slate-500 dark:text-slate-400 text-sm mb-8">7 dias grátis, sem cartão de crédito.</p>

<form method="POST" action="{{ route('register') }}" class="space-y-6">
    @csrf

    <div class="group">
        <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 group-focus-within:text-emerald-500 transition-colors">Seu Nome</label>
        <input type="text" name="name" value="{{ old('name') }}" required autofocus
               class="w-full mt-2 bg-transparent border-b-2 border-slate-200 dark:border-slate-800 py-2.5 font-mono text-base outline-none focus:border-emerald-500 transition-all @error('name') border-red-500 @enderror">
        @error('name')<p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p>@enderror
    </div>

    <div class="group">
        <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 group-focus-within:text-emerald-500 transition-colors">Nome da Empresa</label>
        <input type="text" name="company_name" value="{{ old('company_name') }}" required
               class="w-full mt-2 bg-transparent border-b-2 border-slate-200 dark:border-slate-800 py-2.5 font-mono text-base outline-none focus:border-emerald-500 transition-all @error('company_name') border-red-500 @enderror">
        @error('company_name')<p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p>@enderror
    </div>

    <div class="group">
        <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 group-focus-within:text-emerald-500 transition-colors">E-mail</label>
        <input type="email" name="email" value="{{ old('email') }}" required
               class="w-full mt-2 bg-transparent border-b-2 border-slate-200 dark:border-slate-800 py-2.5 font-mono text-base outline-none focus:border-emerald-500 transition-all @error('email') border-red-500 @enderror">
        @error('email')<p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p>@enderror
    </div>

    <div class="group">
        <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 group-focus-within:text-emerald-500 transition-colors">Senha</label>
        <input type="password" name="password" required
               class="w-full mt-2 bg-transparent border-b-2 border-slate-200 dark:border-slate-800 py-2.5 font-mono text-base outline-none focus:border-emerald-500 transition-all @error('password') border-red-500 @enderror">
        @error('password')<p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p>@enderror
    </div>

    <div class="group">
        <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 group-focus-within:text-emerald-500 transition-colors">Confirmar Senha</label>
        <input type="password" name="password_confirmation" required
               class="w-full mt-2 bg-transparent border-b-2 border-slate-200 dark:border-slate-800 py-2.5 font-mono text-base outline-none focus:border-emerald-500 transition-all">
    </div>

    <div class="flex items-start gap-3 pt-2">
        <input type="checkbox" name="lgpd_consent" id="lgpd_consent" value="1"
               class="mt-0.5 accent-emerald-600" {{ old('lgpd_consent') ? 'checked' : '' }}>
        <label for="lgpd_consent" class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed cursor-pointer">
            Concordo com os <a href="#" class="text-emerald-500 underline">Termos de Uso</a> e
            <a href="#" class="text-emerald-500 underline">Política de Privacidade</a> (LGPD).
        </label>
    </div>
    @error('lgpd_consent')<p class="text-red-500 text-xs font-bold">{{ $message }}</p>@enderror

    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white py-4 rounded-lg font-black tracking-[0.2em] shadow-xl transition-all uppercase text-sm">
        Criar Conta Grátis
    </button>
</form>

<p class="text-center text-sm text-slate-500 dark:text-slate-400 mt-6">
    Já tem conta?
    <a href="{{ route('login') }}" class="text-emerald-500 font-semibold hover:underline">Entrar</a>
</p>
@endsection
