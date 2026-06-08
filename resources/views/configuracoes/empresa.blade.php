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
    .sa-hours-row { display:flex; align-items:center; gap:12px; padding:12px 16px; border-radius:10px; margin-bottom:8px; border:1px solid var(--sa-border); background:var(--sa-surface2); }
    .sa-hours-row.is-active { background:color-mix(in srgb,var(--sa-primary) 5%,transparent); border-color:color-mix(in srgb,var(--sa-primary) 15%,transparent); }
    .sa-toggle { width:42px; height:24px; border-radius:12px; border:none; cursor:pointer; background:var(--sa-border); position:relative; flex-shrink:0; padding:0; }
    .sa-toggle.is-on { background:var(--sa-primary); }
    .sa-toggle__knob { position:absolute; top:3px; left:3px; width:18px; height:18px; border-radius:50%; background:#fff; transition:left 200ms; box-shadow:0 1px 4px rgba(0,0,0,.2); }
    .sa-toggle.is-on .sa-toggle__knob { left:20px; }
    .sa-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
    .sa-grid2--tight { gap:12px; }
    .sa-empresa-tabs button span.lbl { display:inline; }
    @media (max-width:760px) {
        .sa-grid2 { grid-template-columns:1fr; }
        .sa-empresa-body { padding:20px; }
        .sa-empresa-tabs { overflow-x:auto; }
        .sa-empresa-tabs button { flex:0 0 auto; padding:14px 16px; }
    }
</style>
@endpush

@section('content')
@php
    $tab = request('tab', 'dados');
    $hours = $settings['hours'];
    $adv = $settings['advanced'];
    $weekdays = ['seg'=>'Segunda','ter'=>'Terça','qua'=>'Quarta','qui'=>'Quinta','sex'=>'Sexta','sab'=>'Sábado'];
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
            <form method="POST" action="{{ route('configuracoes.empresa.update') }}" class="sa-empresa-card" x-data="{ tab: '{{ $tab }}' }">
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
                                <div>
                                    <label style="font-size:13px;font-weight:600;display:block;margin-bottom:8px">Logo</label>
                                    <div style="display:flex;align-items:center;gap:14px">
                                        <div style="width:64px;height:64px;border-radius:14px;background:color-mix(in srgb,var(--sa-primary) 8%,transparent);border:2px dashed var(--sa-border);display:flex;align-items:center;justify-content:center">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--sa-secondary)" stroke-width="2"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/></svg>
                                        </div>
                                        <div>
                                            <x-sa.btn type="button" variant="muted" size="sm" onclick="Swal.fire({toast:true,position:'top-end',icon:'info',title:'Upload em breve!',showConfirmButton:false,timer:2500})">Upload</x-sa.btn>
                                            <p style="font-size:11px;color:var(--sa-text3);margin:5px 0 0">PNG/JPG · máx 2MB · 400×400px</p>
                                        </div>
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
                            </div>
                        </div>
                    </div>

                    {{-- HORÁRIOS --}}
                    <div x-show="tab === 'horarios'" x-cloak>
                        <p style="font-size:13px;color:var(--sa-text3);margin-bottom:16px">Defina os dias e horários de atendimento. Horários fechados não aparecerão no link de agendamento.</p>
                        @foreach($weekdays as $key => $label)
                        @php [$open, $close, $active] = $hours[$key] ?? ['08:00','20:00',false]; @endphp
                        <div class="sa-hours-row {{ $active ? 'is-active' : '' }}">
                            <input type="hidden" name="hours[{{ $key }}][2]" value="{{ $active ? '1' : '0' }}" class="hours-active-input">
                            <input type="checkbox" {{ $active ? 'checked' : '' }} onchange="const row=this.closest('.sa-hours-row'); row.classList.toggle('is-active',this.checked); row.querySelector('.hours-active-input').value=this.checked?'1':'0';" style="width:16px;height:16px;accent-color:var(--sa-primary)">
                            <span style="font-size:13px;font-weight:600;width:72px;flex-shrink:0">{{ $label }}</span>
                            <div style="display:flex;align-items:center;gap:8px;flex:1" class="hours-times">
                                <input type="time" name="hours[{{ $key }}][0]" value="{{ $open }}" style="flex:1;padding:5px 9px;border:1px solid var(--sa-border);border-radius:7px;font-size:13px;background:var(--sa-surface)">
                                <span style="font-size:12px;color:var(--sa-text3)">às</span>
                                <input type="time" name="hours[{{ $key }}][1]" value="{{ $close }}" style="flex:1;padding:5px 9px;border:1px solid var(--sa-border);border-radius:7px;font-size:13px;background:var(--sa-surface)">
                            </div>
                        </div>
                        @endforeach
                        <div class="sa-hours-row" style="opacity:.6">
                            <input type="checkbox" disabled style="width:16px;height:16px">
                            <span style="font-size:13px;color:var(--sa-text3);font-style:italic">Domingo — Fechado</span>
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
                                <x-sa.btn type="button" variant="muted" size="sm" onclick="navigator.clipboard.writeText('{{ route('agendar.show', $company->slug) }}'); Swal.fire({toast:true,position:'top-end',icon:'success',title:'Link copiado!',showConfirmButton:false,timer:2000})">Copiar</x-sa.btn>
                            </div>
                            <p style="font-size:12px;color:var(--sa-text3);margin-top:6px">Este é o link que seus clientes usarão para agendar online.</p>
                        </div>
                        <div style="display:flex;gap:16px;padding:20px;background:var(--sa-surface2);border-radius:12px;border:1px solid var(--sa-border);align-items:center;margin-bottom:20px">
                            <div style="width:80px;height:80px;background:var(--sa-border);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;opacity:.4">
                                <svg width="60" height="60" viewBox="0 0 100 100"><rect x="0" y="0" width="40" height="40" fill="var(--sa-primary)"/><rect x="60" y="0" width="40" height="40" fill="var(--sa-primary)"/><rect x="0" y="60" width="40" height="40" fill="var(--sa-primary)"/></svg>
                            </div>
                            <div>
                                <div style="font-size:14px;font-weight:600;margin-bottom:4px">QR Code do seu negócio</div>
                                <p style="font-size:12px;color:var(--sa-text3);margin:0 0 10px;line-height:1.6">Imprima e disponibilize no balcão para facilitar o agendamento.</p>
                                <x-sa.btn type="button" variant="muted" size="sm" onclick="Swal.fire({toast:true,position:'top-end',icon:'info',title:'Download em breve!',showConfirmButton:false,timer:2500})">Baixar QR Code</x-sa.btn>
                            </div>
                        </div>
                        <div>
                            <label style="font-size:13px;font-weight:600;display:block;margin-bottom:10px">Compartilhar</label>
                            <div style="display:flex;gap:8px;flex-wrap:wrap">
                                <a href="https://wa.me/?text={{ urlencode(route('agendar.show', $company->slug)) }}" target="_blank" rel="noopener"
                                   style="display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:8px;border:1.5px solid #25D36620;background:#25D36610;color:#25D366;font-size:13px;font-weight:600;text-decoration:none">WhatsApp</a>
                                <x-sa.btn type="button" variant="muted" size="sm" onclick="navigator.clipboard.writeText('{{ route('agendar.show', $company->slug) }}'); Swal.fire({toast:true,position:'top-end',icon:'success',title:'Link copiado!',showConfirmButton:false,timer:2000})">Copiar link</x-sa.btn>
                                <x-sa.btn href="{{ route('agendar.show', $company->slug) }}" target="_blank" variant="ghost" size="sm">Abrir página</x-sa.btn>
                            </div>
                        </div>
                    </div>

                    {{-- AVANÇADO --}}
                    <div x-show="tab === 'avancado'" x-cloak style="display:flex;flex-direction:column;gap:20px">
                        <x-sa.card padding="18px">
                            <h4 style="font-size:14px;font-weight:600;margin:0 0 14px">Regras de Agendamento</h4>
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
                        <x-sa.card padding="18px">
                            <h4 style="font-size:14px;font-weight:600;margin:0 0 14px">Confirmações & Lembretes</h4>
                            <x-sa.setting-row label="Exigir confirmação do cliente" sub="O agendamento só é confirmado após resposta do cliente">
                                <x-sa.toggle name="confirm_required" :checked="$adv['confirm_required']" />
                            </x-sa.setting-row>
                            <x-sa.setting-row label="Lembrete automático" sub="Enviar WhatsApp/SMS antes do horário">
                                <x-sa.toggle name="auto_reminder" :checked="$adv['auto_reminder']" />
                            </x-sa.setting-row>
                            <div class="sa-field" style="margin-top:12px">
                                <label>Enviar lembrete</label>
                                <select name="reminder_hours">
                                    @foreach([1=>'1 hora antes',2=>'2 horas antes',24=>'24 horas antes',48=>'48 horas antes'] as $v => $l)
                                    <option value="{{ $v }}" {{ ($adv['reminder_hours'] ?? 24) == $v ? 'selected' : '' }}>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </x-sa.card>
                        <x-sa.card padding="18px">
                            <h4 style="font-size:14px;font-weight:600;margin:0 0 10px">Política de Cancelamento</h4>
                            <textarea name="cancel_policy" rows="3" placeholder="Descreva sua política..." style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;background:var(--sa-surface)">{{ old('cancel_policy', $adv['cancel_policy']) }}</textarea>
                            <p style="font-size:11px;color:var(--sa-text3);margin-top:6px">Exibida na página pública de agendamento.</p>
                        </x-sa.card>
                        <x-sa.card padding="18px">
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
@endsection
