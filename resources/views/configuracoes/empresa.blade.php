@extends('layouts.app')
@section('title', 'Configurações da Empresa')

@push('styles')
<style>
    .sa-empresa-wrap { width:100%; }
    .sa-empresa-card { background:var(--sa-surface); border-radius:16px; border:1px solid var(--sa-border); box-shadow:0 8px 32px rgba(0,0,0,.08); overflow:hidden; }
    .sa-empresa-tabs { display:flex; border-bottom:1px solid var(--sa-border); background:var(--sa-surface2); }
    .sa-empresa-tabs button {
        flex:1; display:flex; align-items:center; justify-content:center; gap:8px; padding:14px 12px; border:none; cursor:pointer;
        font-size:13px; font-family:var(--sa-font-body); color:var(--sa-text3); background:transparent; font-weight:500;
        border-bottom:2px solid transparent; margin-bottom:-1px; transition:all 150ms;
    }
    .sa-empresa-tabs button.active { color:var(--sa-text1); font-weight:600; border-bottom-color:var(--sa-primary); background:var(--sa-surface); }
    .sa-empresa-body { padding:28px; }
    .sa-empresa-footer { padding:16px 28px; border-top:1px solid var(--sa-border); display:flex; justify-content:flex-end; gap:10px; background:var(--sa-surface2); }
    .sa-field label { display:block; font-size:13px; font-weight:600; color:var(--sa-text1); margin-bottom:5px; }
    .sa-field input, .sa-field select, .sa-field textarea {
        width:100%; padding:10px 13px; border:1.5px solid var(--sa-border); border-radius:8px; font-size:14px;
        color:var(--sa-text1); background:var(--sa-surface); outline:none;
    }
    .sa-field textarea { resize:vertical; min-height:100px; }
    .sa-hours-preset { padding:16px; border-radius:12px; border:1px solid var(--sa-border); background:var(--sa-surface2); }
    .sa-hours-preset__tabs { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px; }
    .sa-hours-preset__tab {
        padding:6px 12px; border-radius:20px; border:1.5px solid var(--sa-border); background:var(--sa-surface);
        font-size:12px; font-weight:600; color:var(--sa-text2); cursor:pointer; transition:all 150ms;
    }
    .sa-hours-preset__tab.is-active { border-color:var(--sa-primary); color:var(--sa-text1); background:color-mix(in srgb,var(--sa-primary) 6%,transparent); }
    .sa-hours-preset__row { display:flex; flex-wrap:wrap; align-items:flex-end; gap:10px; }
    .sa-hours-preset__field { flex:1; min-width:120px; }
    .sa-hours-preset__field label { display:block; font-size:11px; font-weight:600; color:var(--sa-text3); margin-bottom:4px; text-transform:uppercase; letter-spacing:.04em; }
    .sa-hours-preset__field select, .sa-hours-preset__field input {
        width:100%; padding:8px 10px; border:1.5px solid var(--sa-border); border-radius:8px;
        font-size:13px; color:var(--sa-text1); background:var(--sa-surface); outline:none;
    }
    .sa-hours-preset__times { display:flex; align-items:center; gap:6px; flex:1; min-width:180px; }
    .sa-hours-preset__times input { flex:1; padding:8px 10px; border:1.5px solid var(--sa-border); border-radius:8px; font-size:13px; background:var(--sa-surface); color:var(--sa-text1); }
    .sa-hours-preset__times span { font-size:12px; color:var(--sa-text3); flex-shrink:0; }
    .sa-hours-chips { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:12px; }
    .sa-hours-chip {
        width:34px; height:34px; border-radius:8px; border:1.5px solid var(--sa-border); background:var(--sa-surface);
        font-size:11px; font-weight:700; color:var(--sa-text2); cursor:pointer; transition:all 150ms; padding:0;
    }
    .sa-hours-chip.is-on { border-color:var(--sa-primary); background:var(--sa-primary); color:#fff; }
    .sa-hours-table-wrap { border-radius:12px; border:1px solid var(--sa-border); overflow:hidden; background:var(--sa-surface); }
    .sa-hours-table { width:100%; border-collapse:collapse; }
    .sa-hours-table thead tr { background:var(--sa-surface2); border-bottom:1px solid var(--sa-border); }
    .sa-hours-table th { padding:9px 12px; text-align:left; font-size:11px; font-weight:600; color:var(--sa-text3); text-transform:uppercase; letter-spacing:.05em; white-space:nowrap; }
    .sa-hours-table td { padding:8px 12px; border-bottom:1px solid var(--sa-border); vertical-align:middle; }
    .sa-hours-table tbody tr:last-child td { border-bottom:none; }
    .sa-hours-table tbody tr { transition:background 120ms; }
    .sa-hours-table tbody tr:hover { background:var(--sa-surface2); }
    .sa-hours-table tbody tr.is-open { background:color-mix(in srgb,var(--sa-primary) 3%,transparent); }
    .sa-hours-table tbody tr.is-temp { background:color-mix(in srgb,#f59e0b 4%,transparent); }
    .sa-hours-table__day { font-size:13px; font-weight:600; color:var(--sa-text1); white-space:nowrap; width:72px; }
    .sa-hours-table__day span { display:block; font-size:10px; font-weight:500; color:var(--sa-text3); }
    .sa-hours-table select, .sa-hours-table input[type=time], .sa-hours-table input[type=date] {
        padding:6px 8px; border:1.5px solid var(--sa-border); border-radius:7px; font-size:12px;
        color:var(--sa-text1); background:var(--sa-surface); outline:none; width:100%;
    }
    .sa-hours-table__times { display:flex; align-items:center; gap:6px; min-width:160px; }
    .sa-hours-table__times input { width:auto; flex:1; min-width:0; }
    .sa-hours-table__times span { font-size:11px; color:var(--sa-text3); flex-shrink:0; }
    .sa-hours-table__muted { font-size:11px; color:var(--sa-text3); font-style:italic; white-space:nowrap; }
    .sa-empresa-sections { display:flex; flex-direction:column; gap:24px; }
    .sa-empresa-section { display:flex; flex-direction:column; gap:16px; }
    .sa-empresa-section__title { font-size:14px; font-weight:600; color:var(--sa-text1); margin:0; }
    .sa-empresa-section__sub { font-size:13px; color:var(--sa-text3); margin:0; line-height:1.5; }
    .sa-closure-card { padding:20px; border-radius:12px; border:1px solid color-mix(in srgb,#f59e0b 25%,transparent); background:color-mix(in srgb,#f59e0b 5%,transparent); }
    .sa-toggle { width:42px; height:24px; border-radius:12px; border:none; cursor:pointer; background:var(--sa-border); position:relative; flex-shrink:0; padding:0; }
    .sa-toggle.is-on { background:var(--sa-primary); }
    .sa-toggle__knob { position:absolute; top:3px; left:3px; width:18px; height:18px; border-radius:50%; background:#fff; transition:left 200ms; box-shadow:0 1px 4px rgba(0,0,0,.2); }
    .sa-toggle.is-on .sa-toggle__knob { left:20px; }
    .sa-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
    .sa-grid2--tight { gap:12px; }
    .sa-empresa-tabs button span.lbl { display:inline; }
    .sa-closure-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-top:14px; }
    @media (max-width:760px) {
        .sa-grid2 { grid-template-columns:1fr; }
        .sa-empresa-body { padding:20px; }
        .sa-empresa-tabs { overflow-x:auto; }
        .sa-empresa-tabs button { flex:0 0 auto; padding:14px 16px; }
        .sa-closure-grid { grid-template-columns:1fr; }
        .sa-hours-table-wrap { overflow-x:auto; }
        .sa-hours-table { min-width:520px; }
    }
</style>
@endpush

@section('content')
@php
    use App\Support\CompanyHours;
    $tab = request('tab', 'dados');
    $adv = $settings['advanced'];
    $payments = $settings['payments'] ?? [];
    $weekdays = CompanyHours::DAY_LABELS;
    $weekdayShort = ['seg'=>'Seg','ter'=>'Ter','qua'=>'Qua','qui'=>'Qui','sex'=>'Sex','sab'=>'Sáb','dom'=>'Dom'];
    $statusOptions = CompanyHours::STATUS_LABELS;
    $empresaTabs = [
        'dados' => ['label'=>'Dados','icon'=>'<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>'],
        'horarios' => ['label'=>'Horários','icon'=>'<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
        'link' => ['label'=>'Link Público','icon'=>'<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>'],
        'avancado' => ['label'=>'Avançado','icon'=>'<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51a1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>'],
    ];
@endphp

<x-sa.page>
    <x-sa.app-header title="Configurações da Empresa" subtitle="Dados, horários e link público do seu negócio" />

    <x-sa.body padding="24px 32px 40px">
        <div class="sa-empresa-wrap">
            <form method="POST" action="{{ route('configuracoes.empresa.update') }}" class="sa-empresa-card"
                  x-data="{ tab: @js($tab) }">
                @csrf @method('PUT')
                <input type="hidden" name="tab" :value="tab">

                <div class="sa-empresa-tabs">
                    @foreach($empresaTabs as $id => $t)
                    <button type="button" @click="tab = '{{ $id }}'" :class="{ active: tab === '{{ $id }}' }">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $t['icon'] !!}</svg>
                        {{ $t['label'] }}
                    </button>
                    @endforeach
                </div>

                <div class="sa-empresa-body">
                    {{-- DADOS --}}
                    <div x-show="tab === 'dados'" x-cloak>
                        <div class="sa-grid2">
                            <div style="display:flex;flex-direction:column;gap:14px">
                                <div x-data="logoUploader('{{ $company->logo_path ? \Illuminate\Support\Facades\Storage::url($company->logo_path) : '' }}')">
                                    <label style="font-size:13px;font-weight:600;display:block;margin-bottom:8px">Logo</label>
                                    <div style="display:flex;align-items:center;gap:14px">
                                        <div @click="$refs.logoInput.click()"
                                             style="width:64px;height:64px;border-radius:14px;background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:2px dashed var(--sa-border);display:flex;align-items:center;justify-content:center;overflow:hidden;cursor:pointer;transition:border-color 150ms"
                                             onmouseover="this.style.borderColor='var(--sa-primary)'" onmouseout="this.style.borderColor='var(--sa-border)'">
                                            <img x-show="logoUrl" :src="logoUrl" style="width:100%;height:100%;object-fit:cover;border-radius:12px">
                                            <svg x-show="!logoUrl" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/></svg>
                                        </div>
                                        <div>
                                            <div style="display:flex;gap:6px;align-items:center">
                                                <button type="button" @click="$refs.logoInput.click()" :disabled="uploading"
                                                        style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:7px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:12px;font-weight:600;cursor:pointer;transition:all 150ms"
                                                        onmouseover="if(!this.disabled){this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'}"
                                                        onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'"
                                                        x-text="uploading ? 'Enviando...' : (logoUrl ? 'Trocar' : 'Upload')">
                                                </button>
                                                <button x-show="logoUrl" type="button" @click="removeLogo()"
                                                        style="font-size:11px;color:#ef4444;background:none;border:none;cursor:pointer;text-decoration:underline">Remover</button>
                                            </div>
                                            <p style="font-size:11px;color:var(--sa-text3);margin:5px 0 0">PNG/JPG/WebP · máx 2MB · 400×400px</p>
                                        </div>
                                        <input type="file" x-ref="logoInput" accept="image/*" style="display:none" @change="upload($event)">
                                    </div>
                                </div>
                                <div class="sa-field">
                                    <label>Nome da Empresa <span style="color:var(--sa-secondary)">*</span></label>
                                    <input type="text" name="name" value="{{ old('name', $company->name) }}" required>
                                </div>
                                <div class="sa-field">
                                    <label>Segmento</label>
                                    <select name="segment">
                                        <option value="">Selecione...</option>
                                        @foreach($segments as $seg)
                                        <option value="{{ $seg }}" {{ old('segment', $company->segment) === $seg ? 'selected' : '' }}>{{ $seg }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="sa-field">
                                    <label>Endereço</label>
                                    <input type="text" name="address" value="{{ old('address', $company->address) }}" placeholder="Rua, número — bairro, cidade">
                                </div>
                                <div>
                                    <label style="font-size:12px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:10px">Redes Sociais</label>
                                    <div style="display:flex;flex-direction:column;gap:9px">
                                        @foreach(['whatsapp'=>'WhatsApp','instagram'=>'Instagram','facebook'=>'Facebook','tiktok'=>'TikTok','youtube'=>'YouTube'] as $field => $lbl)
                                        <div class="sa-field">
                                            <label>{{ $lbl }}</label>
                                            <input type="text" name="{{ $field }}" value="{{ old($field, $company->$field) }}">
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:14px">
                                <div class="sa-field">
                                    <label>Telefone / WhatsApp</label>
                                    <input type="text" name="phone" value="{{ old('phone', $company->phone ?? $company->whatsapp) }}">
                                </div>
                                <div class="sa-field">
                                    <label>E-mail</label>
                                    <input type="email" name="email" value="{{ old('email', $company->email) }}">
                                </div>
                                <div class="sa-field">
                                    <label>Descrição / Bio</label>
                                    <textarea name="description" maxlength="300" placeholder="Descreva seu negócio...">{{ old('description', $company->description) }}</textarea>
                                    <p style="font-size:11px;color:var(--sa-text3);margin-top:4px">{{ strlen($company->description ?? '') }}/300</p>
                                </div>
                                <div style="padding:16px;border-radius:12px;border:1px solid var(--sa-border);background:var(--sa-surface2)">
                                    <div style="font-size:12px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Recebimentos Pix (PDV)</div>
                                    <div style="display:flex;flex-direction:column;gap:12px">
                                        <div class="sa-field">
                                            <label>Tipo de chave</label>
                                            <select name="pix_key_type">
                                                @foreach(['random'=>'Aleatória','email'=>'E-mail','phone'=>'Telefone','document'=>'CPF/CNPJ'] as $val => $lbl)
                                                <option value="{{ $val }}" {{ old('pix_key_type', $payments['pix_key_type'] ?? 'random') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="sa-field">
                                            <label>Chave Pix</label>
                                            <input type="text" name="pix_key" value="{{ old('pix_key', $payments['pix_key'] ?? '') }}" placeholder="Ex: seu@email.com ou UUID">
                                        </div>
                                        <div class="sa-field">
                                            <label>Cidade (obrigatório no QR Code)</label>
                                            <input type="text" name="pix_city" value="{{ old('pix_city', $payments['pix_city'] ?? '') }}" placeholder="Ex: São Paulo">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- HORÁRIOS --}}
                    <div x-show="tab === 'horarios'" x-cloak class="sa-empresa-sections">
                        <div class="sa-empresa-section" x-data="companyHoursEditor(@js($hours), @js($weekdayShort))">
                            <div>
                                <h3 class="sa-empresa-section__title">Dias e horários de atendimento</h3>
                                <p class="sa-empresa-section__sub" style="margin-top:6px">Use os atalhos para aplicar horários em lote ou ajuste dia a dia na tabela abaixo.</p>
                            </div>

                            {{-- Atalhos em lote --}}
                            <div class="sa-hours-preset">
                                <div style="font-size:12px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">Aplicar em lote</div>
                                <div class="sa-hours-preset__tabs">
                                    <button type="button" class="sa-hours-preset__tab" :class="{ 'is-active': preset === 'weekdays' }" @click="preset = 'weekdays'">Segunda a Sexta</button>
                                    <button type="button" class="sa-hours-preset__tab" :class="{ 'is-active': preset === 'saturday' }" @click="preset = 'saturday'">Sábado</button>
                                    <button type="button" class="sa-hours-preset__tab" :class="{ 'is-active': preset === 'all' }" @click="preset = 'all'">Todos os dias</button>
                                    <button type="button" class="sa-hours-preset__tab" :class="{ 'is-active': preset === 'custom' }" @click="preset = 'custom'">Escolher dias</button>
                                </div>

                                <div x-show="preset === 'custom'" x-cloak class="sa-hours-chips">
                                    <template x-for="(short, key) in dayShort" :key="key">
                                        <button type="button" class="sa-hours-chip" :class="{ 'is-on': presetDays[key] }" @click="presetDays[key] = !presetDays[key]" x-text="short"></button>
                                    </template>
                                </div>

                                <div class="sa-hours-preset__row">
                                    <div class="sa-hours-preset__field" style="max-width:150px">
                                        <label>Situação</label>
                                        <select x-model="presetStatus">
                                            @foreach($statusOptions as $val => $lbl)
                                            <option value="{{ $val }}">{{ $lbl }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="sa-hours-preset__times" x-show="presetStatus === 'aberto'" x-cloak>
                                        <input type="time" x-model="presetOpen">
                                        <span>às</span>
                                        <input type="time" x-model="presetClose">
                                    </div>
                                    <div class="sa-hours-preset__field" x-show="needsReturn(presetStatus)" x-cloak style="max-width:160px">
                                        <label>Data de retorno</label>
                                        <input type="date" x-model="presetReturn">
                                    </div>
                                    <button type="button" @click="applyPreset()"
                                            style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;border:none;cursor:pointer;font-size:13px;font-weight:600;font-family:var(--sa-font-body);background:var(--sa-primary);color:#fff;white-space:nowrap;transition:filter 200ms;flex-shrink:0"
                                            onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                                        Aplicar
                                    </button>
                                </div>
                                <p style="font-size:11px;color:var(--sa-text3);margin:10px 0 0" x-text="presetHint()"></p>
                            </div>

                            {{-- Tabela compacta por dia --}}
                            <div>
                                <div style="font-size:12px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">Ajuste por dia</div>
                                <div class="sa-hours-table-wrap">
                                    <table class="sa-hours-table">
                                        <thead>
                                            <tr>
                                                <th>Dia</th>
                                                <th>Situação</th>
                                                <th>Horário</th>
                                                <th>Retorno</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($weekdays as $key => $label)
                                            <tr :class="{ 'is-open': hours['{{ $key }}'].status === 'aberto', 'is-temp': needsReturn(hours['{{ $key }}'].status) }">
                                                <td class="sa-hours-table__day">
                                                    {{ $weekdayShort[$key] }}
                                                    <span>{{ $label }}</span>
                                                </td>
                                                <td style="width:130px">
                                                    <select name="hours[{{ $key }}][status]" x-model="hours.{{ $key }}.status">
                                                        @foreach($statusOptions as $val => $lbl)
                                                        <option value="{{ $val }}">{{ $lbl }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <div class="sa-hours-table__times" x-show="hours.{{ $key }}.status === 'aberto'" x-cloak>
                                                        <input type="time" name="hours[{{ $key }}][open]" x-model="hours.{{ $key }}.open">
                                                        <span>às</span>
                                                        <input type="time" name="hours[{{ $key }}][close]" x-model="hours.{{ $key }}.close">
                                                    </div>
                                                    <span class="sa-hours-table__muted" x-show="hours.{{ $key }}.status === 'fechado'" x-cloak>Fechado</span>
                                                    <span class="sa-hours-table__muted" x-show="needsReturn(hours.{{ $key }}.status)" x-cloak x-text="statusLabel(hours.{{ $key }}.status)"></span>
                                                </td>
                                                <td style="width:140px">
                                                    <input type="date" name="hours[{{ $key }}][return_date]" x-model="hours.{{ $key }}.return_date"
                                                           x-show="needsReturn(hours.{{ $key }}.status)" x-cloak>
                                                    <span class="sa-hours-table__muted" x-show="!needsReturn(hours.{{ $key }}.status)" x-cloak>—</span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="sa-closure-card sa-empresa-section">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap">
                                <div>
                                    <h3 class="sa-empresa-section__title">Fechamento temporário do estabelecimento</h3>
                                    <p class="sa-empresa-section__sub" style="margin-top:6px">Use quando todo o negócio estiver fechado por férias, feriado prolongado ou reforma.</p>
                                </div>
                                <label style="display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:var(--sa-text1);cursor:pointer;flex-shrink:0">
                                    <input type="hidden" name="closure[active]" value="0">
                                    <input type="checkbox" name="closure[active]" value="1" {{ $closure['active'] ? 'checked' : '' }} style="width:16px;height:16px;accent-color:var(--sa-primary)">
                                    Ativo
                                </label>
                            </div>
                            <div class="sa-closure-grid">
                                <div class="sa-field" style="margin:0">
                                    <label>Motivo</label>
                                    <select name="closure[status]">
                                        @foreach(['ferias'=>'Férias','feriado'=>'Feriado','reforma'=>'Reforma','outro'=>'Outro'] as $val => $lbl)
                                        <option value="{{ $val }}" @selected($closure['status'] === $val)>{{ $lbl }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="sa-field" style="margin:0">
                                    <label>Data de retorno</label>
                                    <input type="date" name="closure[return_date]" value="{{ $closure['return_date'] }}">
                                </div>
                            </div>
                            <div class="sa-field" style="margin-top:14px;margin-bottom:0">
                                <label>Observação (opcional)</label>
                                <input type="text" name="closure[note]" value="{{ $closure['note'] }}" placeholder="Ex: Retornamos dia 15/07 com horário normal">
                            </div>
                        </div>
                    </div>

                    {{-- LINK PÚBLICO --}}
                    <div x-show="tab === 'link'" x-cloak>
                        <div style="margin-bottom:20px">
                            <label style="font-size:13px;font-weight:600;display:block;margin-bottom:8px">Link de Agendamento</label>
                            <div style="display:flex;gap:8px">
                                <div style="flex:1;display:flex;border:1px solid var(--sa-border);border-radius:8px;overflow:hidden;background:var(--sa-surface2)">
                                    <span style="padding:10px 12px;font-size:12px;color:var(--sa-text3);border-right:1px solid var(--sa-border);white-space:nowrap">{{ url('/agendar') }}/</span>
                                    <input type="text" name="slug" value="{{ old('slug', $company->slug) }}" style="flex:1;padding:10px 12px;border:none;background:var(--sa-surface);font-size:13px;outline:none" pattern="[a-z0-9-]+">
                                </div>
                                <x-sa.btn type="button" variant="muted" size="sm" onclick="navigator.clipboard.writeText('{{ route('vitrine.show', $company->slug) }}'); Swal.fire({toast:true,position:'top-end',icon:'success',title:'Link copiado!',showConfirmButton:false,timer:2000})">Copiar</x-sa.btn>
                            </div>
                            <p style="font-size:12px;color:var(--sa-text3);margin-top:6px">Este é o link que seus clientes usarão para agendar online.</p>
                        </div>
                        <div style="display:flex;gap:16px;padding:20px;background:var(--sa-surface2);border-radius:12px;border:1px solid var(--sa-border);align-items:center;margin-bottom:20px">
                            <div style="width:80px;height:80px;background:var(--sa-surface);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid var(--sa-border);overflow:hidden">
                                <img src="{{ route('configuracoes.empresa.qrcode') }}" alt="QR Code" style="width:72px;height:72px;object-fit:contain">
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:600;margin-bottom:4px">QR Code do seu negócio</div>
                                <p style="font-size:12px;color:var(--sa-text3);margin:0 0 10px;line-height:1.6">Imprima e disponibilize no balcão para facilitar o agendamento.</p>
                                <a href="{{ route('configuracoes.empresa.qrcode') }}" download="qrcode-{{ $company->slug }}.svg"
                                   style="display:inline-flex;align-items:center;gap:7px;padding:7px 14px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:13px;font-weight:600;text-decoration:none;transition:border-color 180ms,color 180ms"
                                   onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                                   onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    Baixar QR Code
                                </a>
                            </div>
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;display:block;margin-bottom:10px">Compartilhar</label>
                            <div style="display:flex;gap:8px;flex-wrap:wrap">
                                <a href="https://wa.me/?text={{ urlencode(route('vitrine.show', $company->slug)) }}" target="_blank" rel="noopener"
                                   style="display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:8px;border:1.5px solid #25D36620;background:#25D36610;color:#25D366;font-size:13px;font-weight:600;text-decoration:none">WhatsApp</a>
                                <x-sa.btn type="button" variant="muted" size="sm" onclick="navigator.clipboard.writeText('{{ route('vitrine.show', $company->slug) }}'); Swal.fire({toast:true,position:'top-end',icon:'success',title:'Link copiado!',showConfirmButton:false,timer:2000})">Copiar link</x-sa.btn>
                                <x-sa.btn href="{{ route('vitrine.show', $company->slug) }}" target="_blank" variant="ghost" size="sm">Abrir página</x-sa.btn>
                            </div>
                        </div>
                    </div>

                    {{-- Analytics do link --}}
                    @php
                        $visitsMes   = \App\Models\LinkVisit::where('company_id', $company->id)->where('type', 'view')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
                        $bookingsMes = \App\Models\LinkVisit::where('company_id', $company->id)->where('type', 'booking')->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
                        $totalGeral  = \App\Models\LinkVisit::where('company_id', $company->id)->where('type', 'view')->count();
                        $convRate    = $visitsMes > 0 ? round($bookingsMes / $visitsMes * 100, 1) : 0;
                    @endphp
                    <div style="background:var(--sa-surface2);border-radius:12px;border:1px solid var(--sa-border);padding:20px;margin-top:20px">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
                            <div style="font-size:13px;font-weight:700;color:var(--sa-text1);display:flex;align-items:center;gap:6px">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                                Analytics do link · {{ now()->translatedFormat('F Y') }}
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
                            <div style="background:var(--sa-surface);border-radius:8px;border:1px solid var(--sa-border);padding:14px">
                                <div style="font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Visitas este mês</div>
                                <div style="font-size:22px;font-weight:800;color:var(--sa-text1);font-family:'Poppins',sans-serif">{{ number_format($visitsMes) }}</div>
                            </div>
                            <div style="background:var(--sa-surface);border-radius:8px;border:1px solid var(--sa-border);padding:14px">
                                <div style="font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Agendamentos via link</div>
                                <div style="font-size:22px;font-weight:800;color:var(--sa-secondary);font-family:'Poppins',sans-serif">{{ number_format($bookingsMes) }}</div>
                            </div>
                            <div style="background:var(--sa-surface);border-radius:8px;border:1px solid var(--sa-border);padding:14px">
                                <div style="font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Taxa de conversão</div>
                                <div style="font-size:22px;font-weight:800;color:#10b981;font-family:'Poppins',sans-serif">{{ $convRate }}%</div>
                            </div>
                        </div>
                        @if($totalGeral === 0)
                        <p style="font-size:12px;color:var(--sa-text3);margin-top:12px;text-align:center">
                            Compartilhe seu link de agendamento para começar a rastrear visitas.
                        </p>
                        @endif
                    </div>

                    {{-- AVANÇADO --}}
                    <div x-show="tab === 'avancado'" x-cloak class="sa-empresa-sections">
                        <x-sa.card padding="20px">
                            <h4 class="sa-empresa-section__title" style="margin-bottom:16px">Regras de Agendamento</h4>
                            <div class="sa-grid2 sa-grid2--tight">
                                <div class="sa-field">
                                    <label>Antecedência mínima</label>
                                    <select name="min_advance_mins">
                                        @foreach([0=>'Sem restrição',30=>'30 minutos',60=>'1 hora',120=>'2 horas',1440=>'1 dia'] as $v => $l)
                                        <option value="{{ $v }}" {{ ($adv['min_advance_mins'] ?? 30) == $v ? 'selected' : '' }}>{{ $l }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="sa-field">
                                    <label>Agendamento máximo</label>
                                    <select name="max_advance_days">
                                        @foreach([7,15,30,60,90] as $v)
                                        <option value="{{ $v }}" {{ ($adv['max_advance_days'] ?? 60) == $v ? 'selected' : '' }}>{{ $v }} dias</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </x-sa.card>
                        <x-sa.card padding="20px">
                            <h4 class="sa-empresa-section__title" style="margin-bottom:16px">Confirmações & Lembretes</h4>
                            <x-sa.setting-row label="Exigir confirmação do cliente" sub="O agendamento só é confirmado após resposta do cliente">
                                <x-sa.toggle name="confirm_required" :checked="$adv['confirm_required']" />
                            </x-sa.setting-row>
                            <x-sa.setting-row label="Lembrete automático" sub="Enviar WhatsApp/SMS antes do horário">
                                <x-sa.toggle name="auto_reminder" :checked="$adv['auto_reminder']" />
                            </x-sa.setting-row>
                            <div class="sa-field" style="margin-top:16px;margin-bottom:0">
                                <label>Enviar lembrete</label>
                                <select name="reminder_hours">
                                    @foreach([1=>'1 hora antes',2=>'2 horas antes',24=>'24 horas antes',48=>'48 horas antes'] as $v => $l)
                                    <option value="{{ $v }}" {{ ($adv['reminder_hours'] ?? 24) == $v ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </x-sa.card>
                        <x-sa.card padding="20px">
                            <h4 class="sa-empresa-section__title" style="margin-bottom:12px">Política de Cancelamento</h4>
                            <textarea name="cancel_policy" rows="3" placeholder="Descreva sua política..." style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;background:var(--sa-surface)">{{ old('cancel_policy', $adv['cancel_policy']) }}</textarea>
                            <p style="font-size:11px;color:var(--sa-text3);margin-top:6px">Exibida na página pública de agendamento.</p>
                        </x-sa.card>
                        <x-sa.card padding="20px">
                            <div style="display:flex;justify-content:space-between;align-items:center">
                                <div>
                                    <h4 style="font-size:14px;font-weight:600;margin:0 0 4px">Conformidade LGPD</h4>
                                    <p style="font-size:12px;color:var(--sa-text3);margin:0">Exibir checkbox de consentimento no formulário público</p>
                                </div>
                                <x-sa.toggle name="lgpd_consent" :checked="$company->lgpd_consent" />
                            </div>
                        </x-sa.card>
                    </div>
                </div>

                <div class="sa-empresa-footer">
                    <x-sa.btn href="{{ route('dashboard') }}" variant="secondary" size="sm">Cancelar</x-sa.btn>
                    @can('update', $company)
                    <x-sa.btn type="submit" size="sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:4px"><polyline points="20 6 9 17 4 12"/></svg>
                        Salvar
                    </x-sa.btn>
                    @endcan
                </div>
            </form>
        </div>
    </x-sa.body>
</x-sa.page>

@push('scripts')
<script>
function companyHoursEditor(initialHours, dayShort) {
    const tempStatuses = ['ferias', 'feriado', 'reforma', 'outro'];
    const statusLabels = @json($statusOptions);

    return {
        hours: Object.fromEntries(Object.entries(initialHours).map(([k, v]) => [k, {
            ...v,
            return_date: v.return_date || '',
        }])),
        dayShort,
        preset: 'weekdays',
        presetStatus: 'aberto',
        presetOpen: '08:00',
        presetClose: '20:00',
        presetReturn: '',
        presetDays: { seg: true, ter: true, qua: true, qui: true, sex: true, sab: false, dom: false },

        needsReturn(status) {
            return tempStatuses.includes(status);
        },

        statusLabel(status) {
            return statusLabels[status] || status;
        },

        presetKeys() {
            if (this.preset === 'weekdays') return ['seg', 'ter', 'qua', 'qui', 'sex'];
            if (this.preset === 'saturday') return ['sab'];
            if (this.preset === 'all') return Object.keys(this.dayShort);
            return Object.keys(this.presetDays).filter(k => this.presetDays[k]);
        },

        presetHint() {
            const labels = {
                weekdays: 'Segunda a Sexta',
                saturday: 'Sábado',
                all: 'Todos os dias (Seg–Dom)',
                custom: 'Dias selecionados nos chips acima',
            };
            return 'Será aplicado em: ' + (labels[this.preset] || '');
        },

        applyPreset() {
            const keys = this.presetKeys();
            if (!keys.length) {
                Swal.fire({ toast: true, position: 'top-end', icon: 'warning', title: 'Selecione ao menos um dia', showConfirmButton: false, timer: 2200 });
                return;
            }
            keys.forEach(key => {
                this.hours[key].status = this.presetStatus;
                if (this.presetStatus === 'aberto') {
                    this.hours[key].open = this.presetOpen;
                    this.hours[key].close = this.presetClose;
                    this.hours[key].return_date = '';
                } else if (this.needsReturn(this.presetStatus)) {
                    this.hours[key].return_date = this.presetReturn;
                } else {
                    this.hours[key].return_date = '';
                }
            });
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Horário aplicado!', showConfirmButton: false, timer: 1800 });
        },
    };
}

function logoUploader(initialUrl) {
    return {
        logoUrl: initialUrl || null,
        uploading: false,
        async upload(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.uploading = true;
            const fd = new FormData;
            fd.append('logo', file);
            fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
            try {
                const r = await fetch('{{ route('configuracoes.empresa.logo.upload') }}', { method: 'POST', body: fd });
                if (!r.ok) throw new Error('Upload falhou');
                const data = await r.json();
                this.logoUrl = data.logo_url;
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Logo atualizado!', showConfirmButton: false, timer: 2000 });
            } catch {
                Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Erro ao enviar logo', showConfirmButton: false, timer: 2000 });
            } finally {
                this.uploading = false;
                event.target.value = '';
            }
        },
        async removeLogo() {
            const r = await Swal.fire({ title: 'Remover logo?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Remover', cancelButtonText: 'Cancelar', confirmButtonColor: '#ef4444' });
            if (!r.isConfirmed) return;
            await fetch('{{ route('configuracoes.empresa.logo.delete') }}', { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } });
            this.logoUrl = null;
        },
    };
}
</script>
@endpush
@endsection
