@extends('layouts.app')
@section('title', 'Agenda')

@push('styles')
<style>
    .sa-cal-shell { flex:1; display:flex; flex-direction:column; height:100%; overflow:hidden; min-height:0; }
    .sa-cal-top { padding:20px 32px 12px; flex-shrink:0; }
    .sa-cal-title { font-family:var(--sa-font-heading); font-size:20px; font-weight:700; color:var(--sa-text1); margin:0; text-transform:{{ $viewMode === 'day' ? 'capitalize' : 'none' }}; }
    .sa-cal-controls { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .sa-cal-view-tabs { display:flex; background:var(--sa-surface2); border:1.5px solid var(--sa-border); border-radius:8px; overflow:hidden; }
    .sa-cal-view-tabs a {
        padding:7px 16px; border:none; border-right:1px solid var(--sa-border); text-decoration:none;
        font-size:13px; font-family:var(--sa-font-body); transition:all 160ms; color:var(--sa-text2); font-weight:400;
    }
    .sa-cal-view-tabs a:last-child { border-right:none; }
    .sa-cal-view-tabs a.active { background:var(--sa-primary); color:#fff; font-weight:600; }
    .sa-cal-select {
        padding:7px 11px; font-size:13px; border:1.5px solid var(--sa-border); border-radius:8px;
        background:var(--sa-surface); color:var(--sa-text1); cursor:pointer; font-family:var(--sa-font-body);
    }
    .sa-cal-nav-group { display:flex; border:1.5px solid var(--sa-border); border-radius:8px; overflow:hidden; }
    .sa-cal-nav-group a {
        padding:7px 11px; background:var(--sa-surface); border:none; cursor:pointer; color:var(--sa-text2);
        display:flex; align-items:center; text-decoration:none; transition:background 150ms;
    }
    .sa-cal-nav-group a + a { border-left:1.5px solid var(--sa-border); }
    .sa-cal-nav-group a:hover { background:var(--sa-surface2); }
    .sa-cal-legend { display:flex; gap:14px; align-items:center; flex-wrap:wrap; margin-top:10px; }
    .sa-cal-legend-hint { font-size:11px; color:var(--sa-text3); font-style:italic; margin-left:auto; }
    .sa-cal-head-row { padding:0 32px; flex-shrink:0; }
    .sa-cal-head-grid { display:flex; border-bottom:2px solid var(--sa-border); background:var(--sa-surface); }
    .sa-cal-gutter { width:61px; flex-shrink:0; border-right:1px solid var(--sa-border); background:var(--sa-surface2); padding:8px 0; display:flex; align-items:center; justify-content:center; }
    .sa-cal-day-head { flex:1; border-right:1px solid var(--sa-border); min-width:80px; padding:8px 4px; display:flex; flex-direction:column; align-items:center; justify-content:center; background:var(--sa-surface2); }
    .sa-cal-day-head:last-child { border-right:none; }
    .sa-cal-day-head.is-today { background:color-mix(in srgb,var(--sa-secondary) 8%,transparent); }
    .sa-cal-scroll { flex:1; min-height:0; overflow:auto; padding:0 32px 24px; }
    .sa-cal-grid { display:flex; border:1px solid var(--sa-border); border-radius:12px; overflow:hidden; background:var(--sa-surface); min-width:760px; }
    .sa-cal-time-col { width:60px; flex-shrink:0; border-right:1px solid var(--sa-border); position:relative; }
    .sa-cal-day-col { flex:1; border-right:1px solid var(--sa-border); min-width:80px; }
    .sa-cal-day-col:last-child { border-right:none; }
    .sa-cal-slots { position:relative; cursor:cell; }
    .sa-cal-appt { position:absolute; left:3px; right:3px; border-radius:0 6px 6px 0; padding:3px 6px; text-decoration:none; overflow:hidden; z-index:2; display:block; transition:box-shadow 150ms; user-select:none; }
    .sa-cal-appt:hover { box-shadow:0 2px 8px rgba(0,0,0,.12); z-index:10; }
    .sa-cal-appt--draggable { cursor:grab; }
    .sa-cal-appt--dragging { cursor:grabbing; opacity:.45; pointer-events:none; z-index:1; }
    .sa-cal-appt--placed { box-shadow:0 0 0 2px var(--sa-secondary),0 4px 14px rgba(0,0,0,.12)!important; z-index:12; }
    .sa-cal-ghost {
        position:absolute; left:3px; right:3px; border:2px dashed var(--ghost-col,#6366f1);
        border-radius:6px; background:color-mix(in srgb,var(--ghost-col,#6366f1) 12%,transparent);
        pointer-events:none; z-index:10;
    }
    .sa-cal-month { border:1px solid var(--sa-border); border-radius:12px; overflow:hidden; background:var(--sa-surface); }
    .sa-cal-month-head { display:grid; grid-template-columns:repeat(7,1fr); border-bottom:1px solid var(--sa-border); }
    .sa-cal-month-head div { padding:10px 0; text-align:center; font-size:11px; font-weight:700; color:var(--sa-text3); letter-spacing:.5px; text-transform:uppercase; background:var(--sa-surface2); }
    .sa-cal-month-week { display:grid; grid-template-columns:repeat(7,1fr); border-bottom:1px solid var(--sa-border); }
    .sa-cal-month-week:last-child { border-bottom:none; }
    .sa-cal-month-cell { min-height:100px; padding:6px; border-right:1px solid var(--sa-border); transition:background 120ms; }
    .sa-cal-month-cell:last-child { border-right:none; }
    .sa-cal-month-cell.is-empty { background:var(--sa-surface2); }
    .sa-cal-month-cell.is-today { background:color-mix(in srgb,var(--sa-secondary) 8%,transparent); }
    .sa-cal-month-cell.is-clickable { cursor:pointer; }
    .sa-cal-month-cell.is-clickable:hover { background:var(--sa-surface2); }
    .sa-cal-public-btn {
        display:inline-flex; align-items:center; gap:6px; margin:0 32px 24px; padding:8px 14px;
        background:var(--sa-surface); border:1px solid var(--sa-border); border-radius:20px;
        font-size:12px; font-weight:600; color:var(--sa-text2); text-decoration:none; transition:all 150ms;
    }
    .sa-cal-public-btn:hover { border-color:var(--sa-primary); color:var(--sa-text1); }
    @media (max-width:1080px) {
        .sa-cal-top, .sa-cal-head-row, .sa-cal-scroll { padding-left:20px; padding-right:20px; }
        .sa-cal-public-btn { margin-left:20px; }
    }
</style>
@endpush

@section('content')
@php
    $hoje = \Carbon\Carbon::today();
    $hourH = 56;
    $bizStart = 8;
    $bizEnd = 20;
    $gridH = 24 * $hourH;

    $calParams = fn (array $extra = []) => array_filter(array_merge([
        'view' => $viewMode,
        'ref' => $ref->format('Y-m-d'),
        'profissional_id' => $profissionalId,
    ], $extra));

    $nomesDia = ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];
    $nomesMes = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
@endphp

<div class="sa-cal-shell">
    {{-- Cabeçalho --}}
    <div class="sa-cal-top">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:0">
            <h1 class="sa-cal-title">{{ $headerTitle }}</h1>

            <div class="sa-cal-controls">
                {{-- Dia / Semana / Mês --}}
                <div class="sa-cal-view-tabs">
                    @foreach(['day' => 'Dia', 'week' => 'Semana', 'month' => 'Mês'] as $mode => $label)
                    <a href="{{ route('calendario', $calParams(['view' => $mode])) }}"
                       class="{{ $viewMode === $mode ? 'active' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>

                {{-- Filtro profissional --}}
                <form method="GET" id="form-cal-filtro">
                    <input type="hidden" name="view" value="{{ $viewMode }}">
                    <input type="hidden" name="ref" value="{{ $ref->format('Y-m-d') }}">
                    <select name="profissional_id" class="sa-cal-select" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        @foreach($profissionais as $prof)
                        <option value="{{ $prof->id }}" {{ $profissionalId === $prof->id ? 'selected' : '' }}>
                            {{ explode(' ', $prof->name)[0] }}
                        </option>
                        @endforeach
                    </select>
                </form>

                <a href="{{ route('calendario', $calParams(['ref' => $hoje->format('Y-m-d')])) }}"
                   class="sa-btn sa-btn--muted sa-btn--sm" style="text-decoration:none">Hoje</a>

                <div class="sa-cal-nav-group">
                    <a href="{{ route('calendario', $calParams(['ref' => $navPrev])) }}" title="Anterior">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                    </a>
                    <a href="{{ route('calendario', $calParams(['ref' => $navNext])) }}" title="Próximo">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>

                @can('create', \App\Models\Agendamento::class)
                <x-sa.btn href="{{ route('agendamentos.create') }}" size="sm" :icon="view('components.sa.icons.plus')->render()">Novo</x-sa.btn>
                @endcan
            </div>
        </div>

        {{-- Legenda de profissionais --}}
        <div class="sa-cal-legend">
            @foreach($profissionais as $prof)
            <div style="display:flex;align-items:center;gap:5px">
                <div style="width:9px;height:9px;border-radius:50%;background:{{ $prof->cor }}"></div>
                <span style="font-size:12px;color:var(--sa-text3);font-weight:500">{{ $prof->name }}</span>
            </div>
            @endforeach
            @if($viewMode !== 'month')
            <span class="sa-cal-legend-hint">Arraste para mover · Clique para detalhes</span>
            @else
            <span class="sa-cal-legend-hint">Clique num dia para ver detalhes · Clique num evento para editar</span>
            @endif
        </div>
    </div>

    @if(in_array($viewMode, ['day', 'week']))
    {{-- Cabeçalho dos dias (fixo) --}}
    <div class="sa-cal-head-row">
        <div class="sa-cal-head-grid">
            <div class="sa-cal-gutter">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2" style="opacity:.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            @foreach($dias as $i => $dia)
            @php $isHoje = $dia->isSameDay($hoje); @endphp
            <div class="sa-cal-day-head {{ $isHoje ? 'is-today' : '' }}" style="{{ $viewMode === 'day' ? 'min-width:0' : '' }}">
                <span style="font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:{{ $isHoje ? 'var(--sa-secondary)' : 'var(--sa-text3)' }}">
                    {{ $viewMode === 'day' ? $dia->translatedFormat('l') : $nomesDia[$i] }}
                </span>
                <span style="font-family:var(--sa-font-heading);font-size:20px;font-weight:800;line-height:1.1;color:{{ $isHoje ? 'var(--sa-secondary)' : 'var(--sa-text1)' }}">{{ $dia->format('d') }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Grade horária --}}
    <div class="sa-cal-scroll" id="cal-scroll">
        <div class="sa-cal-grid">
            {{-- Coluna de horas --}}
            <div class="sa-cal-time-col" style="height:{{ $gridH }}px">
                @for($h = 0; $h < 24; $h++)
                <div style="position:absolute;top:{{ $h * $hourH - 9 }}px;left:0;right:0;text-align:right;padding-right:10px;font-size:11px;color:var(--sa-text3);font-weight:500;opacity:{{ ($h >= $bizStart && $h < $bizEnd) ? 1 : 0.5 }}">
                    {{ sprintf('%02d', $h) }}:00
                </div>
                @endfor
            </div>

            {{-- Colunas dos dias --}}
            @foreach($dias as $dia)
            @php
                $chave = $dia->format('Y-m-d');
                $agsDia = $agPorDia[$chave] ?? collect();
                $isHoje = $dia->isSameDay($hoje);
            @endphp
            <div class="sa-cal-day-col">
                <div class="sa-cal-slots" data-day="{{ $chave }}" style="height:{{ $gridH }}px;{{ $isHoje ? 'background:color-mix(in srgb,var(--sa-secondary) 3%,transparent)' : '' }}">
                    {{-- Sombreamento fora do horário comercial --}}
                    <div style="position:absolute;left:0;right:0;top:0;height:{{ $bizStart * $hourH }}px;background:var(--sa-surface2);opacity:.6;pointer-events:none;z-index:0"></div>
                    <div style="position:absolute;left:0;right:0;top:{{ $bizEnd * $hourH }}px;bottom:0;background:var(--sa-surface2);opacity:.6;pointer-events:none;z-index:0"></div>

                    @for($h = 0; $h < 24; $h++)
                    <div style="position:absolute;top:{{ $h * $hourH }}px;left:0;right:0;border-top:1px solid {{ ($h === $bizStart || $h === $bizEnd) ? 'var(--sa-secondary)' : 'var(--sa-border)' }};opacity:{{ ($h === $bizStart || $h === $bizEnd) ? 0.4 : 1 }};pointer-events:none"></div>
                    <div style="position:absolute;top:{{ $h * $hourH + $hourH / 2 }}px;left:0;right:0;border-top:1px dashed var(--sa-border);opacity:.4;pointer-events:none"></div>
                    @endfor

                    @foreach($agsDia as $ag)
                    @php
                        $hora = (int) $ag->data_hora->format('H');
                        $minuto = (int) $ag->data_hora->format('i');
                        $topPx = ($hora + $minuto / 60) * $hourH;
                        $altPx = max(($ag->duracao / 60) * $hourH - 3, 22);
                        $cor = $profCores[$ag->profissional_id] ?? '#1a1a1a';
                        $isLight = in_array($cor, ['#d4a574', '#e6c299'], true);
                        $textCol = $isLight ? '#5a4a2a' : $cor;
                        $isPending = $ag->status === 'pendente';
                        $borderStyle = $isPending ? 'dashed' : 'solid';
                        $opacity = $isPending ? 0.85 : 1;
                    @endphp
                    @php
                        $apptStyle = "top:{$topPx}px;height:{$altPx}px;background:{$cor}22;border-left:3px solid {$cor};
                              border-top:1px {$borderStyle} {$cor}".($isPending ? '' : '40').";
                              border-right:1px {$borderStyle} {$cor}".($isPending ? '' : '40').";
                              border-bottom:1px {$borderStyle} {$cor}".($isPending ? '' : '40').";
                              opacity:{$opacity}";
                    @endphp
                    @can('update', $ag)
                    <div class="sa-cal-appt sa-cal-appt--draggable"
                         data-appt-id="{{ $ag->id }}"
                         data-move-url="{{ route('agendamentos.move', $ag) }}"
                         data-show-url="{{ route('agendamentos.show', $ag) }}"
                         data-day="{{ $chave }}"
                         data-hora="{{ $hora }}"
                         data-minuto="{{ $minuto }}"
                         data-duracao="{{ $ag->duracao }}"
                         data-color="{{ $cor }}"
                         title="{{ $ag->cliente?->name }} — {{ $ag->servico?->nome }}"
                         style="{{ $apptStyle }}">
                        <div style="font-size:11px;font-weight:700;color:{{ $cor }};line-height:1.2;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            {{ $ag->servico?->nome ?? '—' }}
                        </div>
                        @if($altPx > 28)
                        <div style="display:flex;align-items:center;gap:4px;margin-top:1px">
                            <x-sa.avatar :name="$ag->profissional?->name ?? '?'" :size="14" :color="$cor" />
                            <div style="font-size:10px;color:{{ $textCol }};opacity:.85;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600">
                                {{ explode(' ', $ag->profissional?->name ?? '—')[0] }}
                            </div>
                        </div>
                        @endif
                        @if($altPx > 44)
                        <div style="font-size:10px;color:{{ $textCol }};opacity:.7;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:1px">
                            {{ $ag->cliente?->name ?? '—' }}
                        </div>
                        @endif
                        @if($altPx > 60)
                        <div class="sa-cal-appt__time" style="font-size:10px;color:var(--sa-text3);margin-top:1px">{{ $ag->data_hora->format('H:i') }}</div>
                        @endif
                    </div>
                    @else
                    <a href="{{ route('agendamentos.show', $ag) }}"
                       class="sa-cal-appt"
                       title="{{ $ag->cliente?->name }} — {{ $ag->servico?->nome }}"
                       style="{{ $apptStyle }}">
                        <div style="font-size:11px;font-weight:700;color:{{ $cor }};line-height:1.2;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            {{ $ag->servico?->nome ?? '—' }}
                        </div>
                        @if($altPx > 28)
                        <div style="display:flex;align-items:center;gap:4px;margin-top:1px">
                            <x-sa.avatar :name="$ag->profissional?->name ?? '?'" :size="14" :color="$cor" />
                            <div style="font-size:10px;color:{{ $textCol }};opacity:.85;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600">
                                {{ explode(' ', $ag->profissional?->name ?? '—')[0] }}
                            </div>
                        </div>
                        @endif
                        @if($altPx > 44)
                        <div style="font-size:10px;color:{{ $textCol }};opacity:.7;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:1px">
                            {{ $ag->cliente?->name ?? '—' }}
                        </div>
                        @endif
                        @if($altPx > 60)
                        <div style="font-size:10px;color:var(--sa-text3);margin-top:1px">{{ $ag->data_hora->format('H:i') }}</div>
                        @endif
                    </a>
                    @endcan
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>

    @elseif($viewMode === 'month')
    {{-- Visão mensal --}}
    <div class="sa-cal-scroll">
        <div class="sa-cal-month">
            <div class="sa-cal-month-head">
                @foreach(['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'] as $dn)
                <div>{{ $dn }}</div>
                @endforeach
            </div>
            @foreach($monthWeeks as $wi => $week)
            <div class="sa-cal-month-week">
                @foreach($week as $di => $day)
                @php
                    $isEmpty = $day === null;
                    if ($isEmpty) {
                        $dateStr = null;
                        $isHoje = false;
                        $dayAppts = collect();
                    } else {
                        $dateStr = $ref->format('Y-m') . '-' . sprintf('%02d', $day);
                        $isHoje = $dateStr === $hoje->format('Y-m-d');
                        $dayAppts = $agPorDia[$dateStr] ?? collect();
                    }
                @endphp
                <div class="sa-cal-month-cell {{ $isEmpty ? 'is-empty' : 'is-clickable' }} {{ $isHoje ? 'is-today' : '' }}"
                     @if(!$isEmpty) onclick="window.location='{{ route('calendario', $calParams(['view' => 'day', 'ref' => $dateStr])) }}'" @endif>
                    @if($day)
                    <div style="display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:{{ $isHoje ? 'var(--sa-secondary)' : 'transparent' }};margin-bottom:4px">
                        <span style="font-size:13px;font-weight:{{ $isHoje ? 700 : 500 }};color:{{ $isHoje ? '#fff' : 'var(--sa-text2)' }}">{{ $day }}</span>
                    </div>
                    @foreach($dayAppts->take(3) as $ag)
                    @php $col = $profCores[$ag->profissional_id] ?? '#1a1a1a'; @endphp
                    <a href="{{ route('agendamentos.show', $ag) }}"
                       onclick="event.stopPropagation()"
                       style="display:block;font-size:10px;font-weight:600;color:{{ $col }};background:{{ $col }}18;border:1px solid {{ $col }}30;border-radius:4px;padding:2px 5px;margin-bottom:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-decoration:none;transition:background 120ms"
                       onmouseover="this.style.background='{{ $col }}30'"
                       onmouseout="this.style.background='{{ $col }}18'">
                        {{ $ag->data_hora->format('H:i') }} {{ explode(' ', $ag->cliente?->name ?? '—')[0] }}
                    </a>
                    @endforeach
                    @if($dayAppts->count() > 3)
                    <div style="font-size:10px;color:var(--sa-text3);font-weight:600;padding:1px 4px">+{{ $dayAppts->count() - 3 }} mais</div>
                    @endif
                    @endif
                </div>
                @endforeach
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($companySlug)
    <a href="{{ route('agendar.show', $companySlug) }}" target="_blank" rel="noopener" class="sa-cal-public-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
        Página Pública
    </a>
    @endif
</div>

@push('scripts')
<script>
(() => {
    const HOUR_H = {{ $hourH }};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    function pad(n) { return String(n).padStart(2, '0'); }

    function yToSnapped(y) {
        const totalMins = Math.max(0, (y / HOUR_H) * 60);
        const snapped = Math.round(totalMins / 30) * 30;
        const h = Math.min(Math.max(Math.floor(snapped / 60), 0), 23);
        return { h, m: snapped % 60 };
    }

    function saCalToast(title, icon = 'success') {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon,
            title,
            showConfirmButton: false,
            timer: 2800,
            timerProgressBar: true,
        });
    }

    function clearGhosts() {
        document.querySelectorAll('.sa-cal-ghost').forEach(el => el.remove());
    }

    function renderGhost(dayCol, drag) {
        clearGhosts();
        const ghost = document.createElement('div');
        ghost.className = 'sa-cal-ghost';
        ghost.style.setProperty('--ghost-col', drag.color);
        const top = (drag.ghostH + drag.ghostM / 60) * HOUR_H;
        const height = Math.max((drag.duracao / 60) * HOUR_H - 3, 22);
        ghost.style.top = top + 'px';
        ghost.style.height = height + 'px';
        ghost.innerHTML = `<div style="font-size:11px;font-weight:700;color:${drag.color};padding:3px 6px">${pad(drag.ghostH)}:${pad(drag.ghostM)}</div>`;
        dayCol.appendChild(ghost);
    }

    /**
     * Reposiciona o card na grade sem recarregar a página.
     */
    function applyApptMove(el, payload) {
        const targetCol = document.querySelector(`.sa-cal-slots[data-day="${payload.data}"]`);
        if (!targetCol) {
            window.location.reload();
            return;
        }

        const top = (payload.hora + payload.minuto / 60) * HOUR_H;

        el.dataset.day = payload.data;
        el.dataset.hora = String(payload.hora);
        el.dataset.minuto = String(payload.minuto);

        if (el.parentElement !== targetCol) {
            targetCol.appendChild(el);
        }

        el.style.transition = 'top 320ms cubic-bezier(.4,0,.2,1), box-shadow 320ms ease';
        el.style.top = `${top}px`;

        const timeEl = el.querySelector('.sa-cal-appt__time');
        if (timeEl && payload.hora_label) {
            timeEl.textContent = payload.hora_label;
        }

        el.classList.add('sa-cal-appt--placed');
        setTimeout(() => {
            el.classList.remove('sa-cal-appt--placed');
            el.style.transition = 'box-shadow 150ms';
        }, 900);
    }

    let drag = null;

    function onMouseMove(e) {
        if (!drag) return;
        const el = document.elementFromPoint(e.clientX, e.clientY);
        const dayCol = el?.closest('[data-day]');
        if (!dayCol) return;

        const rect = dayCol.getBoundingClientRect();
        const { h, m } = yToSnapped(e.clientY - rect.top - drag.offsetY);
        const day = dayCol.dataset.day;

        if (day !== drag.ghostDay || h !== drag.ghostH || m !== drag.ghostM) {
            drag.moved = true;
        }

        drag.ghostDay = day;
        drag.ghostH = h;
        drag.ghostM = m;
        renderGhost(dayCol, drag);
    }

    async function onMouseUp() {
        if (!drag) return;

        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
        drag.el.classList.remove('sa-cal-appt--dragging');
        clearGhosts();

        const snapshot = drag;
        drag = null;

        if (!snapshot.moved) {
            window.location.href = snapshot.showUrl;
            return;
        }

        try {
            const res = await fetch(snapshot.moveUrl, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    data: snapshot.ghostDay,
                    hora: snapshot.ghostH,
                    minuto: snapshot.ghostM,
                }),
            });

            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                const msg = data.message || data.errors?.data_hora?.[0] || 'Não foi possível mover o agendamento.';
                saCalToast(msg, 'error');
                return;
            }

            applyApptMove(snapshot.el, data);
            saCalToast(data.message || 'Agendamento movido.', 'success');
        } catch {
            saCalToast('Erro de conexão. Tente novamente.', 'error');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const scroll = document.getElementById('cal-scroll');
        if (scroll) scroll.scrollTop = {{ $bizStart * $hourH - 8 }};

        document.querySelectorAll('.sa-cal-appt--draggable').forEach(el => {
            el.addEventListener('mousedown', (e) => {
                if (e.button !== 0) return;
                e.preventDefault();
                e.stopPropagation();

                const rect = el.getBoundingClientRect();
                drag = {
                    el,
                    moveUrl: el.dataset.moveUrl,
                    showUrl: el.dataset.showUrl,
                    offsetY: e.clientY - rect.top,
                    ghostDay: el.dataset.day,
                    ghostH: parseInt(el.dataset.hora, 10),
                    ghostM: parseInt(el.dataset.minuto, 10),
                    duracao: parseInt(el.dataset.duracao, 10),
                    color: el.dataset.color,
                    moved: false,
                };

                el.classList.add('sa-cal-appt--dragging');
                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            });
        });
    });
})();
</script>
@endpush
@endsection
