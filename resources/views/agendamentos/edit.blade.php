@extends('layouts.app')

@section('title', 'Editar Agendamento')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('agendamentos.show', $agendamento) }}" class="text-slate-400 hover:text-slate-600 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <h1 class="text-xl font-bold text-slate-800">Editar Agendamento</h1>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-6 max-w-lg">
    <form method="POST" action="{{ route('agendamentos.update', $agendamento) }}" class="space-y-5">
        @csrf @method('PUT')

        <div>
            <label class="block text-xs font-semibold uppercase tracking-widest text-slate-500 mb-1.5">Profissional *</label>
            <select name="profissional_id" required class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none @error('profissional_id') border-red-400 @enderror">
                @foreach($profissionais as $p)
                <option value="{{ $p->id }}" {{ (old('profissional_id', $agendamento->profissional_id) === $p->id) ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
            @error('profissional_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-widest text-slate-500 mb-1.5">Cliente *</label>
            <select name="cliente_id" required class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none @error('cliente_id') border-red-400 @enderror">
                @foreach($clientes as $c)
                <option value="{{ $c->id }}" {{ (old('cliente_id', $agendamento->cliente_id) === $c->id) ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            @error('cliente_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-widest text-slate-500 mb-1.5">Data e Hora *</label>
            <input type="datetime-local" name="data_hora" required
                   value="{{ old('data_hora', $agendamento->data_hora->format('Y-m-d\TH:i')) }}"
                   class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none @error('data_hora') border-red-400 @enderror">
            @error('data_hora')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-widest text-slate-500 mb-1.5">Duração (minutos) *</label>
            <input type="number" name="duracao" min="15" max="480" required
                   value="{{ old('duracao', $agendamento->duracao) }}"
                   class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none @error('duracao') border-red-400 @enderror">
            @error('duracao')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-widest text-slate-500 mb-1.5">Status</label>
            <select name="status" class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none">
                @foreach(['pendente','confirmado','finalizado','cancelado'] as $s)
                <option value="{{ $s }}" {{ old('status', $agendamento->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-widest text-slate-500 mb-1.5">Observação</label>
            <textarea name="observacao" rows="3" maxlength="1000"
                      class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent outline-none resize-none">{{ old('observacao', $agendamento->observacao) }}</textarea>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold rounded-lg transition shadow">
                Salvar Alterações
            </button>
            <a href="{{ route('agendamentos.show', $agendamento) }}" class="px-5 py-2.5 border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-semibold rounded-lg transition">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
