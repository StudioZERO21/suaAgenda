@extends('layouts.app')
@section('title', 'Horários — ' . $profissional->name)
@section('page-title', 'Horários de Trabalho')

@section('content')
<div style="max-width:720px">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:14px">
            <a href="{{ route('profissionais.show', $profissional) }}"
               style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--sa-border);border-radius:8px;text-decoration:none;color:var(--sa-text3);flex-shrink:0;transition:all 150ms"
               onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'"
               onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
            <div>
                <h1 style="font-family:'Poppins',sans-serif;font-size:20px;font-weight:700;color:var(--sa-text1);margin:0">{{ $profissional->name }}</h1>
                <p style="font-size:13px;color:var(--sa-text3);margin:2px 0 0">Configure os dias e horários de atendimento</p>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);border-radius:10px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        <span style="font-size:14px;color:#059669;font-weight:500">{{ session('success') }}</span>
    </div>
    @endif

    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)">
        <form method="POST" action="{{ route('profissionais.horarios.update', $profissional) }}">
            @csrf @method('PUT')

            @php
                $dias = [
                    1 => 'Segunda-feira',
                    2 => 'Terça-feira',
                    3 => 'Quarta-feira',
                    4 => 'Quinta-feira',
                    5 => 'Sexta-feira',
                    6 => 'Sábado',
                    0 => 'Domingo',
                ];
            @endphp

            @foreach($dias as $num => $nome)
            @php $h = $horarios->get($num); $isAtivo = $h && $h->ativo; @endphp
            <div x-data="{ ativo: {{ $isAtivo ? 'true' : 'false' }} }"
                 style="display:flex;align-items:center;gap:16px;padding:16px 20px;border-bottom:1px solid var(--sa-border)">

                {{-- Toggle ativo --}}
                <div style="width:140px;flex-shrink:0;display:flex;align-items:center;gap:10px">
                    <input type="checkbox" name="dias[{{ $num }}][ativo]" id="dia-{{ $num }}"
                           x-model="ativo" value="1" {{ $isAtivo ? 'checked' : '' }}
                           style="width:16px;height:16px;accent-color:var(--sa-primary);cursor:pointer">
                    <label for="dia-{{ $num }}"
                           style="font-size:14px;font-weight:600;color:var(--sa-text1);cursor:pointer"
                           :style="!ativo && { color: 'var(--sa-text3)' }">{{ $nome }}</label>
                </div>

                {{-- Horários --}}
                <div x-show="ativo" style="display:flex;align-items:center;gap:10px;flex:1">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:var(--sa-text3);margin-bottom:3px">Início</label>
                        <input type="time" name="dias[{{ $num }}][hora_inicio]"
                               value="{{ $h?->hora_inicio ?? '08:00' }}"
                               style="padding:8px 10px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                               onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    </div>
                    <span style="font-size:14px;color:var(--sa-text3);margin-top:16px">até</span>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:var(--sa-text3);margin-bottom:3px">Fim</label>
                        <input type="time" name="dias[{{ $num }}][hora_fim]"
                               value="{{ $h?->hora_fim ?? '18:00' }}"
                               style="padding:8px 10px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                               onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    </div>
                </div>

                <div x-show="!ativo" style="flex:1">
                    <span style="font-size:13px;color:var(--sa-text3)">Não atende</span>
                </div>

            </div>
            @endforeach

            <div style="padding:16px 20px">
                <button type="submit"
                        style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:var(--sa-primary);color:#fff;transition:filter 200ms"
                        onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Salvar Horários
                </button>
            </div>
        </form>
    </div>
@endsection
