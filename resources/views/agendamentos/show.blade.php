@extends('layouts.app')

@section('title', 'Agendamento')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('agendamentos.index') }}" class="text-slate-400 hover:text-slate-600 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <h1 class="text-xl font-bold text-slate-800">Detalhes do Agendamento</h1>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-6 max-w-lg">
    <dl class="space-y-4">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-widest text-slate-400">Cliente</dt>
            <dd class="text-sm font-semibold text-slate-800 mt-1">{{ $agendamento->cliente->name ?? '-' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-widest text-slate-400">Profissional</dt>
            <dd class="text-sm font-semibold text-slate-800 mt-1">{{ $agendamento->profissional->name ?? '-' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-widest text-slate-400">Data e Hora</dt>
            <dd class="text-sm font-semibold text-slate-800 mt-1">{{ $agendamento->data_hora->format('d/m/Y \à\s H:i') }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-widest text-slate-400">Duração</dt>
            <dd class="text-sm font-semibold text-slate-800 mt-1">{{ $agendamento->duracao }} minutos</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-widest text-slate-400">Status</dt>
            <dd class="mt-1">
                <span class="text-xs px-2.5 py-1 rounded-full font-semibold
                    @if($agendamento->status === 'confirmado') bg-emerald-100 text-emerald-700
                    @elseif($agendamento->status === 'pendente') bg-amber-100 text-amber-700
                    @elseif($agendamento->status === 'finalizado') bg-slate-100 text-slate-600
                    @else bg-red-100 text-red-600 @endif">
                    {{ ucfirst($agendamento->status) }}
                </span>
            </dd>
        </div>
        @if($agendamento->observacao)
        <div>
            <dt class="text-xs font-semibold uppercase tracking-widest text-slate-400">Observação</dt>
            <dd class="text-sm text-slate-700 mt-1">{{ $agendamento->observacao }}</dd>
        </div>
        @endif
    </dl>

    <div class="flex gap-3 mt-6 pt-5 border-t border-slate-100">
        @can('update', $agendamento)
        <a href="{{ route('agendamentos.edit', $agendamento) }}"
           class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold rounded-lg transition">
            Editar
        </a>
        @endcan
        @can('delete', $agendamento)
        <form method="POST" action="{{ route('agendamentos.destroy', $agendamento) }}"
              x-data @submit.prevent="Swal.fire({title:'Cancelar agendamento?',icon:'warning',showCancelButton:true,confirmButtonText:'Sim, cancelar',cancelButtonText:'Não'}).then(r=>r.isConfirmed&&$el.submit())">
            @csrf @method('DELETE')
            <button type="submit" class="px-4 py-2 border border-red-200 text-red-600 hover:bg-red-50 text-sm font-semibold rounded-lg transition">
                Cancelar
            </button>
        </form>
        @endcan
    </div>
</div>
@endsection
