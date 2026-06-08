@extends('layouts.app')
@section('title', 'Calendário')
@section('page-title', 'Calendário')

@section('content')
@php
    $hoje        = \Carbon\Carbon::today();
    $semanaAnterior = $semana->copy()->subWeek()->format('Y-m-d');
    $proximaSemana  = $semana->copy()->addWeek()->format('Y-m-d');
    $hora_inicio = 8;
    $hora_fim    = 20;
    $px_hora     = 64; // pixels por hora
    $altura_grid = ($hora_fim - $hora_inicio) * $px_hora;

    // Indexa agendamentos por dia → para renderizar em cada coluna
    $agPorDia = $agendamentos->groupBy(fn ($ag) => $ag->data_hora->format('Y-m-d'));
@endphp

<div style="max-width:1200px">

    {{-- Cabeçalho --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
        <div>
            <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">Calendário</h1>
            <p style="font-size:14px;color:var(--sa-text3);margin:0">
                {{ $dias->first()->translatedFormat('d \d\e F') }} — {{ $dias->last()->translatedFormat('d \d\e F \d\e Y') }}
            </p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            {{-- Filtro por profissional --}}
            <form method="GET" id="form-filtro" style="display:flex;gap:8px;align-items:center">
                <input type="hidden" name="semana" value="{{ $semana->format('Y-m-d') }}">
                <select name="profissional_id"
                        onchange="document.getElementById('form-filtro').submit()"
                        style="padding:8px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;color:var(--sa-text1);background:var(--sa-surface);outline:none;cursor:pointer;transition:border-color 180ms"
                        onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                    <option value="">Todos os profissionais</option>
                    @foreach($profissionais as $prof)
                    <option value="{{ $prof->id }}" {{ $profissionalId === $prof->id ? 'selected' : '' }}>{{ $prof->name }}</option>
                    @endforeach
                </select>
            </form>

            {{-- Navegação de semana --}}
            <div style="display:flex;align-items:center;gap:4px">
                <a href="{{ route('calendario', array_filter(['semana' => $semanaAnterior, 'profissional_id' => $profissionalId])) }}"
                   style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--sa-border);border-radius:8px;color:var(--sa-text3);text-decoration:none;transition:all 150ms"
                   onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                   onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                </a>
                <a href="{{ route('calendario', array_filter(['profissional_id' => $profissionalId])) }}"
                   style="padding:6px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:12px;font-weight:600;color:var(--sa-text2);text-decoration:none;transition:all 150ms"
                   onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                   onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">Hoje</a>
                <a href="{{ route('calendario', array_filter(['semana' => $proximaSemana, 'profissional_id' => $profissionalId])) }}"
                   style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--sa-border);border-radius:8px;color:var(--sa-text3);text-decoration:none;transition:all 150ms"
                   onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                   onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </a>
            </div>

            @can('create', \App\Models\Agendamento::class)
            <a href="{{ route('agendamentos.create') }}"
               style="display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:8px;background:var(--sa-primary);color:#fff;text-decoration:none;font-size:13px;font-weight:600;transition:filter 200ms"
               onmouseover="this.style.filter='brightness(1.15)'" onmouseout="this.style.filter='none'">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Novo
            </a>
            @endcan
        </div>
    </div>

    {{-- Grade do calendário --}}
    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.05)">

        {{-- Header dos dias --}}
        <div style="display:grid;grid-template-columns:52px repeat(6,1fr);border-bottom:2px solid var(--sa-border);background:var(--sa-surface2)">
            <div style="border-right:1px solid var(--sa-border)"></div>
            @foreach($dias as $dia)
            @php
                $isHoje   = $dia->isSameDay($hoje);
                $nomesDia = ['Seg','Ter','Qua','Qui','Sex','Sáb'];
                $nomeDia  = $nomesDia[$dia->dayOfWeek === 0 ? 6 : $dia->dayOfWeek - 1];
                $countDia = ($agPorDia[$dia->format('Y-m-d')] ?? collect())->count();
            @endphp
            <div style="padding:10px 8px;text-align:center;border-right:1px solid var(--sa-border);{{ $isHoje ? 'background:color-mix(in srgb,var(--sa-secondary) 8%,transparent)' : '' }}">
                <div style="font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:{{ $isHoje ? 'var(--sa-secondary)' : 'var(--sa-text3)' }};margin-bottom:2px">{{ $nomeDia }}</div>
                <div style="font-family:'Poppins',sans-serif;font-size:20px;font-weight:800;line-height:1;color:{{ $isHoje ? 'var(--sa-secondary)' : 'var(--sa-text1)' }}">{{ $dia->format('d') }}</div>
                @if($countDia > 0)
                <div style="font-size:10px;color:var(--sa-text3);margin-top:2px">{{ $countDia }} ag.</div>
                @endif
            </div>
            @endforeach
        </div>

        {{-- Corpo com horas --}}
        <div style="display:flex;overflow-y:auto;max-height:600px" id="cal-scroll">
            {{-- Coluna de horas --}}
            <div style="width:52px;flex-shrink:0;border-right:1px solid var(--sa-border);position:sticky;left:0;background:var(--sa-surface2);z-index:5">
                @for($h = $hora_inicio; $h <= $hora_fim; $h++)
                <div style="height:{{ $px_hora }}px;display:flex;align-items:flex-start;justify-content:flex-end;padding:4px 8px 0 0;border-bottom:1px solid var(--sa-border)">
                    <span style="font-size:11px;color:var(--sa-text3);font-weight:500">{{ sprintf('%02d', $h) }}h</span>
                </div>
                @endfor
            </div>

            {{-- Colunas dos dias --}}
            @foreach($dias as $dia)
            @php
                $chave    = $dia->format('Y-m-d');
                $agsDia   = $agPorDia[$chave] ?? collect();
                $isHoje   = $dia->isSameDay($hoje);
            @endphp
            <div style="flex:1;border-right:1px solid var(--sa-border);position:relative;min-width:0;{{ $isHoje ? 'background:color-mix(in srgb,var(--sa-secondary) 3%,transparent)' : '' }}">
                {{-- Linhas de hora --}}
                @for($h = $hora_inicio; $h <= $hora_fim; $h++)
                <div style="height:{{ $px_hora }}px;border-bottom:1px solid var(--sa-border);{{ $h % 2 === 0 ? '' : 'background:rgba(0,0,0,.012)' }}">
                    {{-- Linha de meia hora --}}
                    <div style="height:50%;border-bottom:1px dashed var(--sa-border);opacity:.5"></div>
                </div>
                @endfor

                {{-- Blocos de agendamentos --}}
                @foreach($agsDia as $ag)
                @php
                    $hora     = (int)$ag->data_hora->format('H');
                    $minuto   = (int)$ag->data_hora->format('i');
                    $topPx    = (($hora - $hora_inicio) + $minuto / 60) * $px_hora;
                    $altPx    = max(($ag->duracao / 60) * $px_hora - 2, 20);
                    $cor      = $ag->servico?->cor ?? 'var(--sa-primary)';
                    $showFull = $altPx >= 44;
                    $statusOp = $ag->status === 'pendente' ? '.75' : '1';
                @endphp
                <a href="{{ route('agendamentos.show', $ag) }}"
                   title="{{ $ag->cliente?->name }} — {{ $ag->servico?->nome }} ({{ $ag->data_hora->format('H:i') }})"
                   style="position:absolute;left:3px;right:3px;top:{{ $topPx }}px;height:{{ $altPx }}px;
                          background:{{ $cor }}22;border-left:3px solid {{ $cor }};border-radius:0 6px 6px 0;
                          padding:3px 6px;text-decoration:none;overflow:hidden;opacity:{{ $statusOp }};
                          transition:box-shadow 150ms;z-index:2;display:block;
                          {{ $ag->status === 'pendente' ? 'border-top:1px dashed '.$cor.';border-right:1px dashed '.$cor.';border-bottom:1px dashed '.$cor : 'border-top:1px solid '.$cor.'40;border-right:1px solid '.$cor.'40;border-bottom:1px solid '.$cor.'40' }}"
                   onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,.15)';this.style.zIndex=10"
                   onmouseout="this.style.boxShadow='none';this.style.zIndex=2">
                    <div style="font-size:11px;font-weight:700;color:{{ $cor }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.3">
                        {{ $ag->data_hora->format('H:i') }} {{ $ag->servico?->nome ?? '—' }}
                    </div>
                    @if($showFull)
                    <div style="font-size:10px;color:{{ $cor }};opacity:.85;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px">
                        {{ $ag->cliente?->name ?? '—' }}
                    </div>
                    @if($altPx >= 60)
                    <div style="font-size:10px;color:{{ $cor }};opacity:.7;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px">
                        {{ $ag->profissional?->name ?? '' }}
                    </div>
                    @endif
                    @endif
                </a>
                @endforeach
            </div>
            @endforeach
        </div>
    </div>

    {{-- Legenda de status --}}
    <div style="display:flex;gap:16px;margin-top:12px;align-items:center;flex-wrap:wrap">
        <span style="font-size:12px;color:var(--sa-text3)">Legenda:</span>
        <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--sa-text2)">
            <span style="width:16px;height:8px;border-radius:2px;background:var(--sa-primary);opacity:.7"></span>Confirmado
        </span>
        <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--sa-text2)">
            <span style="width:16px;height:8px;border-radius:2px;border:1.5px dashed var(--sa-text3)"></span>Pendente
        </span>
        @if($agendamentos->isEmpty())
        <span style="font-size:12px;color:var(--sa-text3);margin-left:8px">Nenhum agendamento nesta semana.</span>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Scroll para hora comercial ao carregar
    document.addEventListener('DOMContentLoaded', () => {
        const scroll = document.getElementById('cal-scroll');
        if (scroll) scroll.scrollTop = {{ ($hora_inicio > 8 ? 0 : 1) * 8 * $px_hora / 2 }};
    });
</script>
@endpush
@endsection
