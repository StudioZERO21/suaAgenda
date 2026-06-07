@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="mb-6">
    <h1 class="text-xl font-bold text-slate-800">Dashboard</h1>
    <p class="text-sm text-slate-500">Bem-vindo, {{ auth()->user()->name }}</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-1">Usuário</p>
        <p class="text-lg font-bold text-slate-800">{{ auth()->user()->name }}</p>
        <p class="text-xs text-slate-500 mt-1">{{ auth()->user()->getRoleNames()->first() ?? 'sem role' }}</p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-1">Status</p>
        <p class="text-lg font-bold text-emerald-600">Online</p>
        <p class="text-xs text-slate-500 mt-1">Sistema operacional</p>
    </div>

    @if(auth()->user()->company)
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-1">Empresa</p>
        <p class="text-lg font-bold text-slate-800">{{ auth()->user()->company->name }}</p>
        <p class="text-xs text-slate-500 mt-1">Plano: {{ auth()->user()->company->plano }}</p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-1">Trial</p>
        @if(auth()->user()->company->emTrial())
            <p class="text-lg font-bold text-amber-600">{{ auth()->user()->company->trial_ends_at->diffForHumans() }}</p>
            <p class="text-xs text-slate-500 mt-1">restantes</p>
        @else
            <p class="text-lg font-bold text-slate-800">Ativo</p>
            <p class="text-xs text-slate-500 mt-1">{{ ucfirst(auth()->user()->company->plano) }}</p>
        @endif
    </div>
    @endif
</div>

<div class="mt-6">
    <a href="{{ route('agendamentos.index') }}"
       class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white px-5 py-2.5 rounded-lg font-semibold text-sm transition shadow">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Novo Agendamento
    </a>
</div>
@endsection
