@extends('layouts.app')
@section('title', 'Notificações')
@section('page-title', 'Notificações')

@section('content')
<x-sa.page>
    <x-sa.app-header title="Notificações" subtitle="Histórico completo com data e hora exatas">
        <x-slot:actions>
            <button type="button" id="btn-marcar-todas"
                    onclick="marcarTodasLidas()"
                    style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;transition:border-color 180ms,color 180ms"
                    onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                    onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                Marcar todas como lidas
            </button>
        </x-slot:actions>
    </x-sa.app-header>

    <x-sa.body>
        {{-- Filtros --}}
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;align-items:center">
            <form method="GET" action="{{ route('notificacoes.listar') }}"
                  style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                {{-- Filtro lida --}}
                <select name="lida"
                        onchange="this.form.submit()"
                        style="padding:9px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;cursor:pointer;appearance:none;background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 10px center;padding-right:32px"
                        onfocus="this.style.borderColor='var(--sa-primary)'"
                        onblur="this.style.borderColor='var(--sa-border)'">
                    <option value="" {{ $lida === '' ? 'selected' : '' }}>Todas</option>
                    <option value="0" {{ $lida === '0' ? 'selected' : '' }}>Não lidas</option>
                    <option value="1" {{ $lida === '1' ? 'selected' : '' }}>Lidas</option>
                </select>

                {{-- Filtro tipo --}}
                @if($tipos->isNotEmpty())
                <select name="tipo"
                        onchange="this.form.submit()"
                        style="padding:9px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;cursor:pointer;appearance:none;background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 10px center;padding-right:32px"
                        onfocus="this.style.borderColor='var(--sa-primary)'"
                        onblur="this.style.borderColor='var(--sa-border)'">
                    <option value="" {{ $tipo === '' ? 'selected' : '' }}>Todos os tipos</option>
                    @foreach($tipos as $t)
                    <option value="{{ $t }}" {{ $tipo === $t ? 'selected' : '' }}>
                        {{ __('notif.' . $t) ?? str_replace('_', ' ', ucfirst($t)) }}
                    </option>
                    @endforeach
                </select>
                @endif

                @if($tipo !== '' || $lida !== '')
                <a href="{{ route('notificacoes.listar') }}"
                   style="display:inline-flex;align-items:center;gap:5px;padding:9px 14px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text3);font-size:13px;font-weight:600;text-decoration:none;transition:all 150ms"
                   onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                   onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Limpar
                </a>
                @endif
            </form>
        </div>

        @if($notifs->isEmpty())
        <x-sa.card>
            <div style="padding:72px 0;text-align:center;color:var(--sa-text3)">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 16px;display:block;opacity:.3"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <div style="font-size:16px;font-weight:600;margin-bottom:4px">Nenhuma notificação</div>
                <div style="font-size:14px">Quando houver novidades, elas aparecerão aqui.</div>
            </div>
        </x-sa.card>
        @else

        {{-- Lista agrupada por data --}}
        @php
            $grouped = $notifs->getCollection()->groupBy(fn($n) => $n->created_at->format('Y-m-d'));
        @endphp

        @foreach($grouped as $dateKey => $items)
        @php
            $dateLabel = \Carbon\Carbon::parse($dateKey);
            if ($dateLabel->isToday()) $displayDate = 'Hoje — ' . $dateLabel->format('d/m/Y');
            elseif ($dateLabel->isYesterday()) $displayDate = 'Ontem — ' . $dateLabel->format('d/m/Y');
            else $displayDate = $dateLabel->isoFormat('dddd, D [de] MMMM [de] YYYY');
        @endphp

        <div style="margin-bottom:24px">
            {{-- Separador de data --}}
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
                <div style="flex:1;height:1px;background:var(--sa-border)"></div>
                <span style="font-size:12px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;white-space:nowrap">
                    {{ $displayDate }}
                </span>
                <div style="flex:1;height:1px;background:var(--sa-border)"></div>
            </div>

            {{-- Cards das notificações --}}
            <div style="display:flex;flex-direction:column;gap:6px">
                @foreach($items as $notif)
                @php
                    $color = \App\Models\Notificacao::colorFor($notif->tipo);
                    $unread = !$notif->isRead();
                @endphp
                <div id="notif-{{ $notif->id }}"
                     style="background:var(--sa-surface);border-radius:10px;border:1px solid {{ $unread ? 'rgba(26,26,26,.15)' : 'var(--sa-border)' }};padding:14px 16px;display:flex;align-items:flex-start;gap:14px;transition:border-color 150ms;position:relative;{{ $unread ? 'box-shadow:0 1px 4px rgba(0,0,0,.06)' : 'opacity:.75' }}">
                    {{-- Dot indicador não-lida --}}
                    @if($unread)
                    <div style="position:absolute;top:14px;right:14px;width:7px;height:7px;border-radius:50%;background:{{ $color }}"></div>
                    @endif

                    {{-- Ícone colorido --}}
                    <div style="width:36px;height:36px;border-radius:10px;background:{{ $color }}1a;color:{{ $color }};flex-shrink:0;display:flex;align-items:center;justify-content:center">
                        @if($notif->tipo === 'novo_agendamento')
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        @elseif($notif->tipo === 'cancelamento' || $notif->tipo === 'cancelamento_automatico')
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        @elseif($notif->tipo === 'confirmado')
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        @elseif($notif->tipo === 'aniversario')
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        @else
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        @endif
                    </div>

                    {{-- Conteúdo --}}
                    <div style="flex:1;min-width:0">
                        <div style="display:flex;align-items:baseline;gap:10px;flex-wrap:wrap;margin-bottom:2px">
                            <div style="font-size:14px;font-weight:{{ $unread ? '700' : '600' }};color:var(--sa-text1)">
                                {{ $notif->titulo }}
                            </div>
                            <span style="font-size:11px;font-weight:500;padding:2px 7px;border-radius:20px;background:{{ $color }}1a;color:{{ $color }};white-space:nowrap">
                                {{ str_replace('_', ' ', ucfirst($notif->tipo)) }}
                            </span>
                        </div>
                        <div style="font-size:13px;color:var(--sa-text2);margin-bottom:6px;line-height:1.5">
                            {{ $notif->mensagem }}
                        </div>
                        <div style="font-size:11px;color:var(--sa-text3);font-weight:500">
                            {{ $notif->created_at->format('d/m/Y') }} às {{ $notif->created_at->format('H:i:s') }}
                        </div>
                    </div>

                    {{-- Ações --}}
                    <div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0">
                        @if($unread)
                        <button type="button" title="Marcar como lida" onclick="marcarLida('{{ $notif->id }}')"
                                style="width:28px;height:28px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--sa-text3);transition:all 150ms"
                                onmouseover="this.style.borderColor='#059669';this.style.color='#059669'"
                                onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        </button>
                        @endif
                        <button type="button" title="Excluir" onclick="excluirNotif('{{ $notif->id }}')"
                                style="width:28px;height:28px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--sa-text3);transition:all 150ms"
                                onmouseover="this.style.borderColor='#ef4444';this.style.color='#ef4444'"
                                onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach

        {{-- Paginação --}}
        @if($notifs->hasPages())
        <div style="display:flex;justify-content:center;padding:8px 0">
            {{ $notifs->links() }}
        </div>
        @endif
        @endif
    </x-sa.body>
</x-sa.page>

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

function marcarLida(id) {
    fetch(`/notificacoes/${id}/read`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    }).then(r => r.json()).then(data => {
        if (data.ok) {
            const el = document.getElementById('notif-' + id);
            if (el) {
                // remove dot and reduce opacity
                const dot = el.querySelector('[style*="position:absolute"]');
                if (dot) dot.remove();
                el.style.opacity = '.75';
                el.style.boxShadow = 'none';
                el.style.border = '1px solid var(--sa-border)';
                // remove "marcar lida" button
                const btn = el.querySelector('button[title="Marcar como lida"]');
                if (btn) btn.remove();
            }
        }
    });
}

function excluirNotif(id) {
    Swal.fire({
        title: 'Excluir notificação?',
        text: 'Esta ação não pode ser desfeita.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    }).then(r => {
        if (!r.isConfirmed) return;
        fetch(`/notificacoes/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        }).then(res => res.json()).then(data => {
            if (data.ok) {
                const el = document.getElementById('notif-' + id);
                if (el) {
                    el.style.transition = 'opacity 200ms,max-height 200ms';
                    el.style.opacity = '0';
                    el.style.maxHeight = '0';
                    el.style.overflow = 'hidden';
                    setTimeout(() => el.remove(), 220);
                }
            }
        });
    });
}

function marcarTodasLidas() {
    fetch('/notificacoes/read-all', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    }).then(r => r.json()).then(data => {
        if (data.ok) {
            Swal.fire({ icon: 'success', title: 'Pronto!', text: 'Todas as notificações foram marcadas como lidas.', timer: 1500, showConfirmButton: false })
                .then(() => location.reload());
        }
    });
}
</script>
@endpush
@endsection
