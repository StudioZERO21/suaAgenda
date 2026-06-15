@extends('layouts.app')
@section('title', 'Funcionários')

@php
    use App\Support\PhoneFormatter;
    $palette = ['#1a1a1a', '#d4a574', '#6366f1', '#10b981', '#f59e0b', '#ec4899', '#ef4444', '#0ea5e9', '#8b5cf6', '#14b8a6'];
    $colorFor = fn (string $key) => $palette[crc32($key) % count($palette)];
    $servicosJson = $servicos->map(fn ($s) => ['id' => $s->id, 'nome' => $s->nome, 'cor' => $s->cor])->values();
@endphp

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<style>
    .sa-staff-colors { display: flex; gap: 6px; flex-wrap: wrap; }
    .sa-staff-servicos-tags > template { display: contents; }
    .sa-staff-modal {
        background: var(--sa-surface); border-radius: 16px; width: 100%; max-width: 820px;
        max-height: 92vh; display: flex; flex-direction: column;
        box-shadow: 0 24px 64px rgba(0,0,0,.2); animation: sa-modal-in 250ms ease;
    }
    .sa-export-menu {
        position: absolute; top: calc(100% + 6px); right: 0; min-width: 160px; z-index: 50;
        background: var(--sa-surface); border: 1px solid var(--sa-border); border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,.1); padding: 6px; overflow: hidden;
    }
    .sa-export-menu a {
        display: flex; align-items: center; gap: 8px; padding: 9px 12px; border-radius: 7px;
        font-size: 13px; font-weight: 600; color: var(--sa-text2); text-decoration: none;
        transition: background 120ms, color 120ms;
    }
    .sa-export-menu a:hover { background: var(--sa-surface2); color: var(--sa-text1); }
    .sa-crop-modal {
        background: var(--sa-surface); border-radius: 16px; width: 100%; max-width: 520px;
        max-height: 90vh; display: flex; flex-direction: column; overflow: hidden;
        box-shadow: 0 24px 64px rgba(0,0,0,.2); animation: sa-modal-in 250ms ease;
    }
    .sa-crop-area { max-height: 360px; background: #111; }
    .sa-crop-area img { display: block; max-width: 100%; }
    .sa-staff-inp-wrap { position: relative; }
    .sa-staff-inp-icon { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--sa-text3); pointer-events: none; display: flex; }
    .sa-staff-inp {
        width: 100%; padding: 10px 12px 10px 34px; border: 1.5px solid var(--sa-border); border-radius: 8px;
        font-size: 14px; font-family: var(--sa-font-body); color: var(--sa-text1); background: var(--sa-surface);
        outline: none; transition: border-color 180ms; box-sizing: border-box;
    }
    .sa-staff-inp:focus { border-color: var(--sa-primary); outline: 3px solid rgba(0,0,0,.06); }
    .sa-staff-inp--plain { padding-left: 12px; }
    .sa-staff-modal-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 20px; min-width: 0;
    }
    .sa-staff-modal-grid > div { min-width: 0; }
    .sa-staff-avatar-wrap {
        position: relative; flex-shrink: 0; width: 72px; height: 72px; cursor: pointer;
    }
    .sa-staff-avatar-circle {
        width: 72px; height: 72px; border-radius: 50%; overflow: hidden;
        position: relative; isolation: isolate;
    }
    .sa-staff-avatar-circle img {
        position: absolute; top: 0; left: 0; width: 72px; height: 72px;
        max-width: 72px; max-height: 72px; object-fit: cover; border-radius: 50%;
    }
    .sa-staff-avatar-initials {
        position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
        font-size: 22px; font-weight: 800; font-family: var(--sa-font-body); color: #fff;
    }
    .sa-staff-avatar-wrap:hover .sa-staff-avatar-hover { opacity: 1; }
    .sa-staff-avatar-hover {
        position: absolute; inset: 0; border-radius: 50%; background: rgba(0,0,0,.35);
        display: flex; align-items: center; justify-content: center; opacity: 0;
        transition: opacity 150ms; z-index: 2; pointer-events: none;
    }
    .sa-staff-avatar-add-btn {
        position: absolute; bottom: -2px; right: -2px; width: 22px; height: 22px;
        border-radius: 50%; background: var(--sa-secondary); border: 2px solid var(--sa-surface);
        display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 3;
    }
    .sa-staff-card-photo {
        width: 52px; height: 52px; border-radius: 50%; overflow: hidden; flex-shrink: 0;
        border: 2px solid var(--sa-border); position: relative; background: var(--sa-surface2);
    }
    .sa-staff-card-photo img {
        position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block;
    }
    .sa-staff-card {
        background: color-mix(in srgb, var(--sa-primary) 6%, transparent);
        border: 1px solid color-mix(in srgb, var(--sa-primary) 12%, transparent);
        border-radius: 16px; overflow: hidden; transition: box-shadow 200ms;
    }
    .sa-staff-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.1); }
    .sa-staff-especialidades > template { display: contents; }
    .sa-staff-pagination {
        margin-top: 16px; padding: 12px 20px; border: 1px solid var(--sa-border); border-radius: 12px;
        background: var(--sa-surface); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
    }
    .sa-staff-pagination nav { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
    .sa-staff-pagination nav a, .sa-staff-pagination nav span {
        min-width: 30px; height: 30px; padding: 0 8px; border-radius: 7px; border: 1px solid var(--sa-border);
        display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 600;
        text-decoration: none; color: var(--sa-text3); background: transparent;
    }
    .sa-staff-pagination nav span[aria-current="page"] {
        background: var(--sa-primary); color: #fff; border-color: var(--sa-primary);
    }
    .sa-staff-pagination nav a:hover { border-color: var(--sa-primary); color: var(--sa-text1); }
    .sa-view-toggle {
        margin-left: auto; display: flex; flex-shrink: 0;
        border: 1px solid var(--sa-border); border-radius: 8px; overflow: hidden;
    }
    .sa-view-toggle button {
        width: 38px; height: 34px; padding: 0; border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        background: var(--sa-surface); color: var(--sa-text2);
        transition: background 150ms, color 150ms;
    }
    .sa-view-toggle button + button { border-left: 1px solid var(--sa-border); }
    .sa-view-toggle button.is-active {
        background: var(--sa-primary); color: #fff;
    }
    .sa-view-toggle button:not(.is-active):hover {
        background: var(--sa-surface2); color: var(--sa-text1);
    }
</style>
@endpush

@section('content')
<x-sa.page x-data="staffApp()" x-init="init()">
    <x-sa.app-header
        title="Funcionários"
        :subtitle="'Gerencie sua equipe e comissões · ' . $stats['total'] . ' cadastrado' . ($stats['total'] !== 1 ? 's' : '')">
        <x-slot:actions>
            @can('viewAny', \App\Models\Profissional::class)
            <div style="position:relative" @click.outside="exportOpen = false">
                <button type="button" @click="exportOpen = !exportOpen"
                        style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;cursor:pointer;font-family:var(--sa-font-body);transition:border-color 180ms,color 180ms"
                        onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                        onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Exportar
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div x-show="exportOpen" x-cloak class="sa-export-menu">
                    <a href="{{ route('profissionais.exportar') }}">Exportar CSV</a>
                    <a href="{{ route('profissionais.exportar.pdf') }}">Exportar PDF</a>
                </div>
            </div>
            @endcan
            @can('create', \App\Models\Profissional::class)
            @if($planLimits->canAddProfissional())
            <x-sa.btn type="button" @click="openCreateModal()" :icon="view('components.sa.icons.plus')->render()">
                Novo Funcionário
            </x-sa.btn>
            @else
            <a href="{{ route('planos.index') }}"
               style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:#d97706;color:#fff;text-decoration:none;transition:filter 200ms"
               onmouseover="this.style.filter='brightness(1.1)'"
               onmouseout="this.style.filter='none'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                Upgrade de Plano
            </a>
            @endif
            @endcan
        </x-slot:actions>
    </x-sa.app-header>

    <x-sa.body>
        {{-- Banner de limite de plano --}}
        @if(!$planLimits->canAddProfissional())
        <div style="display:flex;align-items:center;gap:14px;padding:14px 18px;border-radius:10px;
                    background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);margin-bottom:20px">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" flex-shrink="0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <div style="flex:1">
                <span style="font-size:13px;font-weight:600;color:#92400e">Limite de profissionais atingido</span>
                <span style="font-size:13px;color:#78350f;margin-left:6px">{{ $planLimits->mensagemLimiteProfissionais() }}</span>
            </div>
            <a href="{{ route('planos.index') }}"
               style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:7px;
                      background:#d97706;color:#fff;font-size:12px;font-weight:700;text-decoration:none;
                      white-space:nowrap;transition:filter 180ms"
               onmouseover="this.style.filter='brightness(1.1)'"
               onmouseout="this.style.filter='none'">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                Fazer upgrade
            </a>
        </div>
        @endif

        {{-- Stat cards --}}
        <div class="sa-grid-4" style="margin-bottom:20px">
            <div class="sa-tint-card" style="--tint:var(--sa-primary)">
                <div class="sa-tint-card__label">Total de funcionários</div>
                <div class="sa-tint-card__value">{{ $stats['total'] }}</div>
                <div class="sa-tint-card__icon"><svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg></div>
            </div>
            <div class="sa-tint-card" style="--tint:#10b981">
                <div class="sa-tint-card__label">Ativos</div>
                <div class="sa-tint-card__value">{{ $stats['ativos'] }}</div>
                <div class="sa-tint-card__icon"><svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="1.5"><polyline points="20 6 9 17 4 12"/></svg></div>
            </div>
            <div class="sa-tint-card" style="--tint:#f59e0b">
                <div class="sa-tint-card__label">De férias</div>
                <div class="sa-tint-card__value">{{ $stats['ferias'] }}</div>
                <div class="sa-tint-card__icon"><svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            </div>
            <div class="sa-tint-card" style="--tint:var(--sa-secondary)">
                <div class="sa-tint-card__label">Comissão média</div>
                <div class="sa-tint-card__value">{{ number_format($stats['comissao_media'], 0) }}%</div>
                <div class="sa-tint-card__icon"><svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="1.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
            </div>
        </div>

        {{-- Filter bar --}}
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:20px;flex-wrap:wrap">
            <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;flex:1;margin:0">
                <div style="position:relative;flex:1;min-width:200px;max-width:300px">
                    <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--sa-text3);pointer-events:none;display:flex">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nome ou cargo..." class="sa-search-input">
                </div>
                <select name="role" onchange="this.form.submit()"
                        style="padding:9px 32px 9px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;font-weight:600;font-family:var(--sa-font-body);color:var(--sa-text2);background:var(--sa-surface);cursor:pointer;appearance:none;background-image:url(&quot;data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'/%3e%3c/svg%3e&quot;);background-repeat:no-repeat;background-position:right 8px center;background-size:14px">
                    <option value="">Todos os cargos</option>
                    @foreach($staffRoles as $role)
                    <option value="{{ $role }}" @selected(request('role') === $role)>{{ $role }}</option>
                    @endforeach
                </select>
                <select name="status" onchange="this.form.submit()"
                        style="padding:9px 32px 9px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;font-weight:600;font-family:var(--sa-font-body);color:var(--sa-text2);background:var(--sa-surface);cursor:pointer;appearance:none;background-image:url(&quot;data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'/%3e%3c/svg%3e&quot;);background-repeat:no-repeat;background-position:right 8px center;background-size:14px">
                    <option value="">Todos os status</option>
                    <option value="ativo" @selected(request('status') === 'ativo')>Ativo</option>
                    <option value="ferias" @selected(request('status') === 'ferias')>Férias</option>
                    <option value="licenca" @selected(request('status') === 'licenca')>Licença</option>
                    <option value="inativo" @selected(request('status') === 'inativo')>Inativo</option>
                </select>
            </form>

            {{-- View toggle (cards | tabela) --}}
            <div class="sa-view-toggle">
                <button type="button" @click="view='cards';localStorage.setItem('sa_staff_view','cards')"
                        :class="view==='cards' ? 'is-active' : ''" title="Cards">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                </button>
                <button type="button" @click="view='table';localStorage.setItem('sa_staff_view','table')"
                        :class="view==='table' ? 'is-active' : ''" title="Tabela">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
            </div>
        </div>

        @if($profissionais->isEmpty())
            <div style="text-align:center;padding:60px;color:var(--sa-text3);font-size:14px">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 16px;display:block;opacity:.3"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                Nenhum funcionário encontrado.
                @can('create', \App\Models\Profissional::class)
                <button type="button" @click="openCreateModal()" style="color:var(--sa-secondary);font-weight:600;background:none;border:none;cursor:pointer;text-decoration:underline;font-size:14px"> Cadastrar o primeiro</button>
                @endcan
            </div>
        @else
            {{-- ── CARDS VIEW ─────────────────────────────────────────── --}}
            <div x-show="view==='cards'" class="sa-grid-3">
                @foreach($profissionais as $prof)
                    @php
                        $cor = $prof->cor ?? $colorFor($prof->name);
                        $statusProf = $prof->status ?? ($prof->ativo ? 'ativo' : 'inativo');
                        $badgeStatus = match ($statusProf) {
                            'ferias' => 'ferias',
                            'licenca' => 'licenca',
                            'inativo' => 'inativo',
                            default => 'ativo',
                        };
                        $badgeLabel = match ($statusProf) {
                            'ferias' => 'Férias',
                            'licenca' => 'Licença',
                            'inativo' => 'Inativo',
                            default => 'Ativo',
                        };
                        $notaMedia = $notasMap[$prof->id] ?? null;
                    @endphp
                    <div class="sa-staff-card">
                        <div style="height:5px;background:{{ $cor }}"></div>
                        <div style="padding:20px 20px 0">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px">
                                <div style="display:flex;gap:12px;align-items:center;min-width:0">
                                    @if($prof->foto_path)
                                    <div class="sa-staff-card-photo">
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($prof->foto_path) }}" alt="{{ $prof->name }}">
                                    </div>
                                    @else
                                    <x-sa.avatar :name="$prof->name" :size="52" :color="$cor" />
                                    @endif
                                    <div style="min-width:0">
                                        <div style="font-family:var(--sa-font-heading,'Poppins',sans-serif);font-size:16px;font-weight:700;color:var(--sa-text1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $prof->name }}</div>
                                        <div style="font-size:12px;color:var(--sa-text3);margin-top:2px">{{ $prof->especialidade ?? 'Funcionário' }}</div>
                                    </div>
                                </div>
                                <x-sa.badge :status="$badgeStatus" :label="$badgeLabel" />
                            </div>

                            {{-- Stats row --}}
                            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px;padding:10px;background:var(--sa-surface);border-radius:10px;border:1px solid var(--sa-border)">
                                <div style="text-align:center">
                                    <div style="font-family:var(--sa-font-heading,'Poppins',sans-serif);font-size:18px;font-weight:800;color:var(--sa-text1)">{{ $prof->agendamentos_count }}</div>
                                    <div style="font-size:10px;color:var(--sa-text3);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:2px">Agendamentos</div>
                                </div>
                                <div style="text-align:center">
                                    <div style="font-family:var(--sa-font-heading,'Poppins',sans-serif);font-size:18px;font-weight:800;color:var(--sa-text1)">{{ $prof->comissao_pct !== null ? number_format((float) $prof->comissao_pct, 0) . '%' : '—' }}</div>
                                    <div style="font-size:10px;color:var(--sa-text3);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:2px">Comissão</div>
                                </div>
                                <div style="text-align:center">
                                    <div style="font-family:var(--sa-font-heading,'Poppins',sans-serif);font-size:18px;font-weight:800;color:var(--sa-text1)">{{ $notaMedia !== null ? '★ ' . number_format((float) $notaMedia, 1) : '—' }}</div>
                                    <div style="font-size:10px;color:var(--sa-text3);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:2px">Avaliação</div>
                                </div>
                            </div>

                            @if(!empty($prof->especialidades))
                            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:16px">
                                @foreach($prof->especialidades as $spec)
                                <span style="font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;background:color-mix(in srgb,{{ $cor }} 8%,transparent);color:{{ $cor }};border:1px solid color-mix(in srgb,{{ $cor }} 19%,transparent)">{{ $spec }}</span>
                                @endforeach
                            </div>
                            @elseif($prof->especialidade)
                            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:16px">
                                <span style="font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;background:color-mix(in srgb,{{ $cor }} 8%,transparent);color:{{ $cor }};border:1px solid color-mix(in srgb,{{ $cor }} 19%,transparent)">{{ $prof->especialidade }}</span>
                            </div>
                            @endif

                            {{-- Contact --}}
                            <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:var(--sa-text3);margin-bottom:16px;flex-wrap:wrap">
                                <span style="display:inline-flex;align-items:center;gap:5px">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.5a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.09a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
                                    {{ PhoneFormatter::format($prof->phone) ?: '—' }}
                                </span>
                                <span>·</span>
                                <span style="display:inline-flex;align-items:center;gap:5px;min-width:0">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $prof->email ?: '—' }}</span>
                                </span>
                            </div>
                        </div>

                        <div style="padding:10px 14px;border-top:1px solid var(--sa-border);display:flex;gap:8px">
                            @can('update', $prof)
                            <button type="button" @click="openModal('{{ $prof->id }}')"
                                    style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:7px 12px;border-radius:8px;border:1.5px solid var(--sa-border);background:var(--sa-surface2);color:var(--sa-text2);font-size:13px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;transition:border-color 180ms,color 180ms,background 180ms"
                                    onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                                    onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Editar
                            </button>
                            @endcan
                            @can('delete', $prof)
                            <form method="POST" action="{{ route('profissionais.destroy', $prof) }}" onsubmit="return confirmDelete(event, '{{ addslashes($prof->name) }}')" style="margin:0">
                                @csrf @method('DELETE')
                                <button type="submit" title="Excluir"
                                        style="width:34px;height:34px;border-radius:8px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--sa-text3);transition:all 150ms"
                                        onmouseover="this.style.borderColor='#ef4444';this.style.color='#ef4444'"
                                        onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                                </button>
                            </form>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ── TABLE VIEW ─────────────────────────────────────────── --}}
            <x-sa.card :flush="true" x-show="view==='table'" x-cloak>
                <div style="overflow-x:auto">
                    <table style="width:100%;border-collapse:collapse">
                        <thead>
                            <tr style="background:var(--sa-surface2);border-bottom:1px solid var(--sa-border)">
                                <th class="sa-th">Funcionário</th>
                                <th class="sa-th hide-mobile">Cargo</th>
                                <th class="sa-th hide-mobile">Status</th>
                                <th class="sa-th hide-mobile">Comissão</th>
                                <th class="sa-th hide-mobile">Agendamentos</th>
                                <th class="sa-th" style="text-align:right;width:90px">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($profissionais as $prof)
                            @php
                                $cor = $colorFor($prof->name);
                                $statusProf = $prof->status ?? ($prof->ativo ? 'ativo' : 'inativo');
                                $badgeStatus = match ($statusProf) {
                                    'ferias' => 'ferias',
                                    'licenca' => 'licenca',
                                    'inativo' => 'inativo',
                                    default => 'ativo',
                                };
                                $badgeLabel = match ($statusProf) {
                                    'ferias' => 'Férias',
                                    'licenca' => 'Licença',
                                    'inativo' => 'Inativo',
                                    default => 'Ativo',
                                };
                            @endphp
                            <tr class="sa-tr">
                                <td class="sa-td">
                                    <div style="display:flex;align-items:center;gap:10px">
                                        @if($prof->foto_path)
                                        <div style="width:34px;height:34px;border-radius:50%;overflow:hidden;flex-shrink:0;border:1px solid var(--sa-border)">
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($prof->foto_path) }}" alt="{{ $prof->name }}" style="width:100%;height:100%;object-fit:cover">
                                        </div>
                                        @else
                                        <x-sa.avatar :name="$prof->name" :color="$cor" />
                                        @endif
                                        <a href="{{ route('profissionais.show', $prof) }}" style="font-size:14px;font-weight:600;color:var(--sa-text1);text-decoration:none">{{ $prof->name }}</a>
                                    </div>
                                </td>
                                <td class="sa-td hide-mobile" style="color:var(--sa-text2)">{{ $prof->especialidade ?? '—' }}</td>
                                <td class="sa-td hide-mobile">
                                    <x-sa.badge :status="$badgeStatus" :label="$badgeLabel" />
                                </td>
                                <td class="sa-td hide-mobile" style="color:var(--sa-secondary);font-weight:600">{{ $prof->comissao_pct !== null ? number_format((float) $prof->comissao_pct, 0) . '%' : '—' }}</td>
                                <td class="sa-td hide-mobile">
                                    <span style="font-size:13px;font-weight:600;padding:2px 10px;border-radius:20px;background:rgba(26,26,26,.06);color:var(--sa-text2)">{{ $prof->agendamentos_count }}</span>
                                </td>
                                <td class="sa-td" style="text-align:right">
                                    <div style="display:inline-flex;gap:4px">
                                        <x-sa.icon-btn href="{{ route('profissionais.show', $prof) }}" title="Ver">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </x-sa.icon-btn>
                                        @can('update', $prof)
                                        <button type="button" @click="openModal('{{ $prof->id }}')"
                                                title="Editar"
                                                style="width:30px;height:30px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--sa-text3);transition:all 150ms"
                                                onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'"
                                                onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </button>
                                        @endcan
                                        @can('delete', $prof)
                                        <form method="POST" action="{{ route('profissionais.destroy', $prof) }}" onsubmit="return confirmDelete(event, '{{ addslashes($prof->name) }}')" style="margin:0">
                                            @csrf @method('DELETE')
                                            <x-sa.icon-btn type="submit" title="Excluir" :danger="true">
                                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                                            </x-sa.icon-btn>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-sa.card>

            @if($profissionais->hasPages())
            <div class="sa-staff-pagination">
                <span style="font-size:13px;color:var(--sa-text3)">
                    {{ $profissionais->firstItem() }}–{{ $profissionais->lastItem() }} de {{ $profissionais->total() }}
                </span>
                {{ $profissionais->links() }}
            </div>
            @endif
        @endif
    </x-sa.body>

    {{-- Modal create/edit --}}
    <div x-show="modalOpen" x-cloak
         @keydown.escape.window="closeModal()"
         class="sa-modal-overlay"
         @click.self="closeModal()">
        <div class="sa-staff-modal" @click.stop x-show="modalOpen">
            <div style="padding:24px 28px 0;display:flex;justify-content:space-between;align-items:flex-start;flex-shrink:0">
                <div>
                    <h3 style="font-family:var(--sa-font-heading);font-size:18px;font-weight:600;color:var(--sa-text1);margin:0"
                        x-text="form.id ? 'Editar Funcionário' : 'Novo Funcionário'"></h3>
                    <p style="font-size:13px;color:var(--sa-text3);margin:4px 0 0"
                       x-text="form.id ? ('Editando ' + (form.name || '')) : 'Preencha os dados do novo funcionário'"></p>
                </div>
                <button type="button" @click="closeModal()" style="background:none;border:none;cursor:pointer;color:var(--sa-text3);padding:4px;display:flex;border-radius:6px">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            {{-- Body --}}
            <div style="padding:20px 28px;overflow-y:auto;flex:1">
                <div class="sa-staff-modal-grid">
                    {{-- Left column --}}
                    <div style="display:flex;flex-direction:column;gap:14px;min-width:0">
                        {{-- Avatar + color picker --}}
                        <div style="padding:16px;background:var(--sa-surface2);border-radius:12px;border:1px solid var(--sa-border)">
                            <div style="display:flex;align-items:center;gap:16px">
                                <div class="sa-staff-avatar-wrap" @click="$refs.fotoInput.click()">
                                    <div class="sa-staff-avatar-circle"
                                         :style="form.foto_url ? '' : ('background:' + form.cor + ';border:2px dashed color-mix(in srgb,' + form.cor + ' 40%, transparent)')">
                                        <img x-show="form.foto_url" :src="form.foto_url" alt="">
                                        <span x-show="!form.foto_url" class="sa-staff-avatar-initials" x-text="(form.name || '?').substring(0,2).toUpperCase()"></span>
                                        <div class="sa-staff-avatar-hover">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                        </div>
                                    </div>
                                    <button type="button" class="sa-staff-avatar-add-btn" @click.stop="$refs.fotoInput.click()" :disabled="uploadingFoto">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                    <input type="file" x-ref="fotoInput" accept="image/*" style="display:none" @change="pickFoto($event)">
                                </div>
                                <div style="flex:1">
                                    <div style="font-size:13px;font-weight:600;color:var(--sa-text1);margin-bottom:8px">Foto & Cor</div>
                                    <div class="sa-staff-colors">
                                        @foreach($palette as $corOpt)
                                        <button type="button" @click="form.cor = '{{ $corOpt }}'"
                                                :style="'width:22px;height:22px;border-radius:50%;background:{{ $corOpt }};border:' + (form.cor === '{{ $corOpt }}' ? '3px solid var(--sa-text1)' : '2px solid transparent') + ';cursor:pointer;transition:border 150ms'"></button>
                                        @endforeach
                                    </div>
                                    <div style="font-size:11px;color:var(--sa-text3);margin-top:6px">Passe o mouse na foto para trocar</div>
                                </div>
                            </div>
                        </div>

                        {{-- Name --}}
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Nome completo <span style="color:var(--sa-secondary)">*</span></label>
                            <div class="sa-staff-inp-wrap">
                                <span class="sa-staff-inp-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                                <input type="text" x-model="form.name" class="sa-staff-inp" required placeholder="Nome do funcionário">
                            </div>
                        </div>

                        {{-- Cargo + Status --}}
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                            <div>
                                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Cargo / Função</label>
                                <select x-model="form.especialidade" class="sa-staff-inp sa-staff-inp--plain" style="cursor:pointer;appearance:none;background-image:url(&quot;data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'/%3e%3c/svg%3e&quot;);background-repeat:no-repeat;background-position:right 8px center;background-size:14px">
                                    <option value="">Selecione...</option>
                                    @foreach($staffRoles as $role)
                                    <option value="{{ $role }}">{{ $role }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Status</label>
                                <select x-model="form.status" class="sa-staff-inp sa-staff-inp--plain" style="cursor:pointer;appearance:none;background-image:url(&quot;data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'/%3e%3c/svg%3e&quot;);background-repeat:no-repeat;background-position:right 8px center;background-size:14px">
                                    <option value="ativo">Ativo</option>
                                    <option value="inativo">Inativo</option>
                                    <option value="ferias">Férias</option>
                                    <option value="licenca">Licença</option>
                                </select>
                            </div>
                        </div>

                        {{-- E-mail --}}
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">E-mail <span style="color:var(--sa-secondary)">*</span></label>
                            <div class="sa-staff-inp-wrap">
                                <span class="sa-staff-inp-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
                                <input type="email" x-model="form.email" class="sa-staff-inp" placeholder="email@empresa.com">
                            </div>
                        </div>

                        {{-- WhatsApp --}}
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">WhatsApp</label>
                            <div class="sa-staff-inp-wrap">
                                <span class="sa-staff-inp-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.5a19.79 19.79 0 01-3.07-8.67A2 2 0 012 .84h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 8.09a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg></span>
                                <input type="tel" :value="form.phone" @input="form.phone = saMaskPhone($event.target.value)"
                                       maxlength="15" class="sa-staff-inp" placeholder="(11) 99999-0000">
                            </div>
                        </div>
                    </div>

                    {{-- Right column --}}
                    <div style="display:flex;flex-direction:column;gap:14px;min-width:0">
                        {{-- Admission date --}}
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Data de admissão</label>
                            <div class="sa-staff-inp-wrap">
                                <span class="sa-staff-inp-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                                <input type="date" x-model="form.admissao" class="sa-staff-inp">
                            </div>
                        </div>

                        {{-- Commission slider --}}
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
                                Comissão:
                                <span style="color:var(--sa-secondary);font-weight:800" x-text="form.comissao_pct + '%'"></span>
                            </label>
                            <input type="range" x-model.number="form.comissao_pct" min="0" max="70" step="1"
                                   style="width:100%;accent-color:var(--sa-primary);cursor:pointer">
                            <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--sa-text3);margin-top:3px">
                                <span>0%</span><span>35%</span><span>70%</span>
                            </div>
                        </div>

                        {{-- Especialidades --}}
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:8px">Especialidades</label>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px;min-height:28px" class="sa-staff-especialidades">
                                <span x-show="!(form.especialidades || []).length" style="font-size:12px;color:var(--sa-text3);font-style:italic">Nenhuma especialidade</span>
                                <template x-for="spec in (form.especialidades || [])" :key="spec">
                                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;padding:4px 10px;border-radius:20px;background:color-mix(in srgb,var(--sa-primary) 10%,transparent);border:1px solid color-mix(in srgb,var(--sa-primary) 20%,transparent);color:var(--sa-primary)">
                                        <span x-text="spec"></span>
                                        <button type="button" @click="removeSpec(spec)" style="background:none;border:none;cursor:pointer;color:var(--sa-text3);line-height:1;padding:0;display:flex;align-items:center">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </button>
                                    </span>
                                </template>
                            </div>
                            <div style="display:flex;gap:8px">
                                <input type="text" x-model="newSpec" @keydown.enter.prevent="addSpec()" placeholder="Ex: Degradê, Coloração..." class="sa-staff-inp sa-staff-inp--plain" style="flex:1;font-size:13px">
                                <button type="button" @click="addSpec()"
                                        style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;border:1px solid var(--sa-border);background:var(--sa-surface2);cursor:pointer;font-size:13px;font-weight:600;color:var(--sa-text2);white-space:nowrap">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    Add
                                </button>
                            </div>
                        </div>

                        {{-- Serviços vinculados (agenda) --}}
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:8px">Serviços vinculados</label>
                            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:8px;min-height:28px" class="sa-staff-servicos-tags">
                                <span x-show="form.servicos.length === 0" style="font-size:12px;color:var(--sa-text3);font-style:italic">Nenhum serviço vinculado</span>
                                <template x-for="sid in form.servicos" :key="sid">
                                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;padding:4px 10px;border-radius:20px;background:color-mix(in srgb,var(--sa-secondary) 10%,transparent);border:1px solid color-mix(in srgb,var(--sa-secondary) 20%,transparent);color:var(--sa-secondary)">
                                        <span x-text="servicoName(sid)"></span>
                                        <button type="button" @click="form.servicos = form.servicos.filter(x => x !== sid)" style="background:none;border:none;cursor:pointer;color:var(--sa-text3);line-height:1;padding:0;display:flex;align-items:center">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </button>
                                    </span>
                                </template>
                            </div>
                            <select @change="addServico($event)" class="sa-staff-inp sa-staff-inp--plain" style="cursor:pointer;font-size:13px;appearance:none">
                                <option value="">+ Adicionar serviço...</option>
                                <template x-for="s in availableServicos" :key="s.id">
                                    <option :value="s.id" x-text="s.nome"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Stats (read-only, só edição) --}}
                        <div x-show="form.id" style="background:var(--sa-surface2);border-radius:10px;padding:14px 16px;border:1px solid var(--sa-border)">
                            <div style="font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">Estatísticas</div>
                            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--sa-border)">
                                <span style="font-size:12px;color:var(--sa-text3)">Agendamentos</span>
                                <span style="font-size:13px;font-weight:700;color:var(--sa-text1)" x-text="form.agendamentos_count || 0"></span>
                            </div>
                            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--sa-border)">
                                <span style="font-size:12px;color:var(--sa-text3)">Avaliação</span>
                                <span style="font-size:13px;font-weight:700;color:var(--sa-text1)" x-text="form.nota_media ? ('★ ' + form.nota_media) : '—'"></span>
                            </div>
                            <div style="display:flex;justify-content:space-between;padding:6px 0">
                                <span style="font-size:12px;color:var(--sa-text3)">Cliente desde</span>
                                <span style="font-size:13px;font-weight:700;color:var(--sa-text1)" x-text="form.admissao_fmt || form.admissao || '—'"></span>
                            </div>
                        </div>

                        {{-- Social media --}}
                        <div style="background:var(--sa-surface2);border-radius:10px;padding:14px 16px;border:1px solid var(--sa-border)">
                            <div style="font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Redes Sociais</div>
                            <div style="display:flex;flex-direction:column;gap:10px">
                                <div>
                                    <label style="display:block;font-size:12px;font-weight:600;color:var(--sa-text2);margin-bottom:4px">Instagram</label>
                                    <input type="text" x-model="form.instagram" placeholder="@usuario"
                                           style="width:100%;padding:8px 12px;border:1px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;box-sizing:border-box"
                                           onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                                </div>
                                <div>
                                    <label style="display:block;font-size:12px;font-weight:600;color:var(--sa-text2);margin-bottom:4px">TikTok</label>
                                    <input type="text" x-model="form.tiktok" placeholder="@usuario"
                                           style="width:100%;padding:8px 12px;border:1px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;box-sizing:border-box"
                                           onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                                </div>
                                <div>
                                    <label style="display:block;font-size:12px;font-weight:600;color:var(--sa-text2);margin-bottom:4px">Facebook</label>
                                    <input type="text" x-model="form.facebook" placeholder="Nome da página"
                                           style="width:100%;padding:8px 12px;border:1px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;box-sizing:border-box"
                                           onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div style="padding:16px 28px 24px;border-top:1px solid var(--sa-border);display:flex;gap:10px;justify-content:flex-end;flex-shrink:0">
                <button type="button" @click="closeModal()"
                        style="padding:9px 18px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;transition:border-color 180ms,color 180ms"
                        onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                        onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                    Cancelar
                </button>
                <button type="button" @click="saveModal()" :disabled="saving"
                        style="padding:9px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:var(--sa-primary);color:#fff;display:inline-flex;align-items:center;gap:7px;transition:filter 200ms"
                        onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                    <svg x-show="saving" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
                    <svg x-show="!saving" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    <span x-text="saving ? 'Salvando...' : (form.id ? 'Salvar alterações' : 'Cadastrar funcionário')"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Modal recorte de foto --}}
    <div x-show="cropOpen" x-cloak class="sa-modal-overlay" style="z-index:1100" @click.self="closeCrop()">
        <div class="sa-crop-modal" @click.stop>
            <div style="padding:20px 24px 0;display:flex;justify-content:space-between;align-items:flex-start">
                <div>
                    <h3 style="font-family:var(--sa-font-heading);font-size:17px;font-weight:600;color:var(--sa-text1);margin:0">Ajustar foto</h3>
                    <p style="font-size:13px;color:var(--sa-text3);margin:4px 0 0">Enquadre o rosto ou logo no círculo</p>
                </div>
                <button type="button" @click="closeCrop()" style="background:none;border:none;cursor:pointer;color:var(--sa-text3);padding:4px">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="sa-crop-area" style="padding:16px 24px">
                <img x-ref="cropImage" :src="cropImageSrc" alt="Recorte">
            </div>
            <div style="padding:16px 24px 24px;border-top:1px solid var(--sa-border);display:flex;gap:10px;justify-content:flex-end">
                <button type="button" @click="closeCrop()"
                        style="padding:9px 18px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;cursor:pointer">
                    Cancelar
                </button>
                <button type="button" @click="confirmCrop()" :disabled="uploadingFoto"
                        style="padding:9px 18px;border-radius:8px;border:none;background:var(--sa-primary);color:#fff;font-size:14px;font-weight:600;cursor:pointer">
                    <span x-text="uploadingFoto ? 'Salvando...' : 'Usar foto'"></span>
                </button>
            </div>
        </div>
    </div>
</x-sa.page>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script>
function staffApp() {
    const servicos = @json($servicosJson);
    const profissionais = @json($profissionaisJson);
    const openNovo = @json(request()->boolean('novo'));

    const blank = () => ({
        id: null, name: '', email: '', especialidade: 'Barbeiro', especialidades: [],
        comissao_pct: 35, status: 'ativo', ativo: true,
        cor: '#1a1a1a', phone: '', admissao: new Date().toISOString().slice(0, 10),
        admissao_fmt: '', instagram: '', tiktok: '', facebook: '',
        agendamentos_count: 0, nota_media: null, servicos: [], foto_url: null,
    });

    return {
        view: localStorage.getItem('sa_staff_view') || 'cards',
        modalOpen: false,
        exportOpen: false,
        cropOpen: false,
        cropImageSrc: '',
        cropper: null,
        pendingFotoBlob: null,
        saving: false,
        uploadingFoto: false,
        newSpec: '',
        form: blank(),
        servicos,
        profissionais,

        init() {
            if (openNovo) this.openCreateModal();
        },

        get availableServicos() {
            return this.servicos.filter(s => !this.form.servicos.includes(s.id));
        },

        servicoName(sid) {
            return this.servicos.find(s => s.id === sid)?.nome || sid;
        },

        addServico(event) {
            const val = event.target.value;
            if (val && !this.form.servicos.includes(val)) {
                this.form.servicos = [...this.form.servicos, val];
            }
            event.target.value = '';
        },

        addSpec() {
            const s = (this.newSpec || '').trim();
            if (!s) return;
            if (!(this.form.especialidades || []).includes(s)) {
                this.form.especialidades = [...(this.form.especialidades || []), s];
            }
            this.newSpec = '';
        },

        removeSpec(spec) {
            this.form.especialidades = (this.form.especialidades || []).filter(x => x !== spec);
        },

        openCreateModal() {
            this.form = blank();
            this.newSpec = '';
            this.pendingFotoBlob = null;
            this.modalOpen = true;
        },

        openModal(id) {
            const prof = this.profissionais.find(p => p.id === id);
            if (!prof) return;
            this.form = {
                ...blank(),
                ...prof,
                phone: saMaskPhone(prof.phone || ''),
                especialidades: [...(prof.especialidades || [])],
                servicos: [...(prof.servicos || [])],
                status: prof.status || (prof.ativo ? 'ativo' : 'inativo'),
            };
            this.newSpec = '';
            this.pendingFotoBlob = null;
            this.modalOpen = true;
        },

        closeModal() {
            this.modalOpen = false;
            this.pendingFotoBlob = null;
        },

        pickFoto(event) {
            const file = event.target.files[0];
            if (!file) return;
            event.target.value = '';
            const reader = new FileReader();
            reader.onload = (e) => {
                this.cropImageSrc = e.target.result;
                this.cropOpen = true;
                this.$nextTick(() => this.initCropper());
            };
            reader.readAsDataURL(file);
        },

        initCropper() {
            if (this.cropper) {
                this.cropper.destroy();
                this.cropper = null;
            }
            const img = this.$refs.cropImage;
            if (!img || typeof Cropper === 'undefined') return;
            this.cropper = new Cropper(img, {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 1,
                responsive: true,
                background: false,
            });
        },

        closeCrop() {
            this.cropOpen = false;
            this.cropImageSrc = '';
            if (this.cropper) {
                this.cropper.destroy();
                this.cropper = null;
            }
        },

        async confirmCrop() {
            if (!this.cropper) return;
            this.uploadingFoto = true;
            try {
                const canvas = this.cropper.getCroppedCanvas({ width: 400, height: 400, imageSmoothingQuality: 'high' });
                const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.9));
                if (!blob) throw new Error('Recorte inválido');

                if (this.form.id) {
                    await this.uploadFotoBlob(this.form.id, blob);
                } else {
                    this.pendingFotoBlob = blob;
                    this.form.foto_url = canvas.toDataURL('image/jpeg', 0.9);
                }
                this.closeCrop();
            } catch {
                Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Erro ao processar foto', showConfirmButton: false, timer: 2000 });
            } finally {
                this.uploadingFoto = false;
            }
        },

        async uploadFotoBlob(profId, blob) {
            const fd = new FormData();
            fd.append('foto', blob, 'foto.jpg');
            const r = await fetch(`{{ url('profissionais') }}/${profId}/foto`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: fd,
            });
            if (!r.ok) throw new Error('Upload falhou');
            const data = await r.json();
            this.form.foto_url = data.foto_url;
            const idx = this.profissionais.findIndex(p => p.id === profId);
            if (idx >= 0) this.profissionais[idx].foto_url = data.foto_url;
        },

        async removeFoto() {
            if (!this.form.id) {
                this.form.foto_url = null;
                this.pendingFotoBlob = null;
                return;
            }
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            await fetch(`{{ url('profissionais') }}/${this.form.id}/foto`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            this.form.foto_url = null;
            const idx = this.profissionais.findIndex(p => p.id === this.form.id);
            if (idx >= 0) this.profissionais[idx].foto_url = null;
        },

        payload() {
            return {
                name: this.form.name,
                email: this.form.email || null,
                especialidade: this.form.especialidade || null,
                especialidades: this.form.especialidades || [],
                comissao_pct: this.form.comissao_pct,
                status: this.form.status || 'ativo',
                cor: this.form.cor || null,
                phone: saMaskPhone(this.form.phone) || null,
                admissao: this.form.admissao || null,
                instagram: this.form.instagram || null,
                tiktok: this.form.tiktok || null,
                facebook: this.form.facebook || null,
                servicos: this.form.servicos,
            };
        },

        async saveModal() {
            if (!this.form.name.trim() || !this.form.email.trim()) {
                return Swal.fire({ title: 'Atenção', text: 'Preencha nome e e-mail.', icon: 'warning', confirmButtonColor: '#1a1a1a' });
            }
            this.saving = true;
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            try {
                let res;
                if (this.form.id) {
                    res = await fetch(`{{ url('profissionais') }}/${this.form.id}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify(this.payload()),
                    });
                } else {
                    res = await fetch(`{{ url('profissionais') }}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify(this.payload()),
                    });
                }

                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    const msg = err.message || (err.errors ? Object.values(err.errors).flat().join(' ') : 'Erro ao salvar.');
                    throw new Error(msg);
                }

                const data = await res.json();
                const profId = data.profissional?.id || this.form.id;

                if (this.pendingFotoBlob && profId) {
                    await this.uploadFotoBlob(profId, this.pendingFotoBlob);
                    this.pendingFotoBlob = null;
                }

                this.closeModal();
                Swal.fire({ title: 'Salvo!', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                setTimeout(() => window.location.reload(), 1200);
            } catch (e) {
                Swal.fire({ title: 'Erro', text: e.message, icon: 'error', confirmButtonColor: '#1a1a1a' });
            } finally {
                this.saving = false;
            }
        },
    };
}

function confirmDelete(e, nome) {
    e.preventDefault();
    const form = e.target;
    Swal.fire({ title: 'Excluir funcionário?', text: `"${nome}" será removido.`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim, excluir', cancelButtonText: 'Cancelar', confirmButtonColor: '#e53e3e' })
        .then(r => { if (r.isConfirmed) form.submit(); });
    return false;
}
</script>
@endpush
@endsection
