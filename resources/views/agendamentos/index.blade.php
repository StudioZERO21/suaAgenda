@extends('layouts.app')

@section('title', 'Agendamentos')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-slate-800">Agendamentos</h1>
    @can('create', App\Models\Agendamento::class)
    <a href="{{ route('agendamentos.create') }}"
       class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-lg font-semibold text-sm transition shadow">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Novo Agendamento
    </a>
    @endcan
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    @forelse($agendamentos as $agendamento)
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 last:border-0 hover:bg-slate-50 transition">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 font-bold text-sm">
                {{ substr($agendamento->cliente->name ?? '?', 0, 2) }}
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-800">{{ $agendamento->cliente->name ?? '-' }}</p>
                <p class="text-xs text-slate-500">{{ $agendamento->profissional->name ?? '-' }} • {{ $agendamento->data_hora->format('d/m/Y H:i') }} • {{ $agendamento->duracao }}min</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs px-2 py-0.5 rounded-full font-semibold
                @if($agendamento->status === 'confirmado') bg-emerald-100 text-emerald-700
                @elseif($agendamento->status === 'pendente') bg-amber-100 text-amber-700
                @elseif($agendamento->status === 'finalizado') bg-slate-100 text-slate-600
                @else bg-red-100 text-red-600 @endif">
                {{ ucfirst($agendamento->status) }}
            </span>
            <a href="{{ route('agendamentos.show', $agendamento) }}" class="text-xs text-slate-400 hover:text-emerald-600 transition">Ver</a>
            @can('update', $agendamento)
            <a href="{{ route('agendamentos.edit', $agendamento) }}" class="text-xs text-slate-400 hover:text-emerald-600 transition">Editar</a>
            @endcan
        </div>
    </div>
    @empty
    <div class="text-center py-16">
        <p class="text-slate-400 text-sm">Nenhum agendamento encontrado.</p>
        @can('create', App\Models\Agendamento::class)
        <a href="{{ route('agendamentos.create') }}" class="mt-3 inline-block text-emerald-600 text-sm font-semibold hover:underline">Criar primeiro agendamento</a>
        @endcan
    </div>
    @endforelse
</div>

@if($agendamentos->hasPages())
<div class="mt-4">{{ $agendamentos->links() }}</div>
@endif
@endsection
