@extends('layouts.app')
@section('title', 'Hor�rios � ' . $profissional->name)
@section('page-title', 'Hor�rios de Trabalho')

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
                <h1 style="font-family:var(--sa-font-heading);font-size:20px;font-weight:700;color:var(--sa-text1);margin:0">{{ $profissional->name }}</h1>
                <p style="font-size:13px;color:var(--sa-text3);margin:2px 0 0">Configure os dias e hor�rios de atendimento</p>
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
                    2 => 'Ter�a-feira',
                    3 => 'Quarta-feira',
                    4 => 'Quinta-feira',
                    5 => 'Sexta-feira',
                    6 => 'S�bado',
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

                {{-- Hor�rios --}}
                <div x-show="ativo" style="display:flex;align-items:center;gap:10px;flex:1">
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:var(--sa-text3);margin-bottom:3px">In�cio</label>
                        <input type="time" name="dias[{{ $num }}][hora_inicio]"
                               value="{{ $h?->hora_inicio ?? '08:00' }}"
                               style="padding:8px 10px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:var(--sa-font-body);color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                               onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    </div>
                    <span style="font-size:14px;color:var(--sa-text3);margin-top:16px">at�</span>
                    <div>
                        <label style="display:block;font-size:11px;font-weight:600;color:var(--sa-text3);margin-bottom:3px">Fim</label>
                        <input type="time" name="dias[{{ $num }}][hora_fim]"
                               value="{{ $h?->hora_fim ?? '18:00' }}"
                               style="padding:8px 10px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:var(--sa-font-body);color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                               onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    </div>
                </div>

                <div x-show="!ativo" style="flex:1">
                    <span style="font-size:13px;color:var(--sa-text3)">N�o atende</span>
                </div>

            </div>
            @endforeach

            <div style="padding:16px 20px">
                <button type="submit"
                        style="display:inline-flex;align-items:center;gap:7px;padding:10px 20px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:var(--sa-font-body);background:var(--sa-primary);color:#fff;transition:filter 200ms"
                        onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Salvar Hor�rios
                </button>
            </div>
        </form>
    </div>

    {{-- Bloqueios de Agenda --}}
    <div style="margin-top:28px" x-data="bloqueiosApp()" x-init="load()">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <h2 style="font-family:var(--sa-font-heading);font-size:16px;font-weight:700;color:var(--sa-text1);margin:0">
                Folgas e Bloqueios
            </h2>
            <button @click="showForm = !showForm" type="button"
                    style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;border:none;cursor:pointer;font-size:13px;font-weight:600;font-family:var(--sa-font-body);background:var(--sa-primary);color:#fff;transition:filter 200ms"
                    onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Adicionar bloqueio
            </button>
        </div>

        {{-- Form novo bloqueio --}}
        <div x-show="showForm" x-cloak style="background:var(--sa-surface);border:1.5px solid var(--sa-border);border-radius:12px;padding:20px;margin-bottom:16px">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--sa-text1);margin-bottom:4px">Data início</label>
                    <input type="date" x-model="form.data_inicio"
                           style="width:100%;padding:9px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:var(--sa-font-body);color:var(--sa-text1);background:var(--sa-surface);outline:none"
                           onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--sa-text1);margin-bottom:4px">Data fim</label>
                    <input type="date" x-model="form.data_fim"
                           style="width:100%;padding:9px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:var(--sa-font-body);color:var(--sa-text1);background:var(--sa-surface);outline:none"
                           onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:var(--sa-text1);margin-bottom:4px">Motivo (opcional)</label>
                    <input type="text" x-model="form.motivo" placeholder="Férias, feriado..."
                           style="width:100%;padding:9px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:var(--sa-font-body);color:var(--sa-text1);background:var(--sa-surface);outline:none"
                           onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                </div>
                <button @click="salvar()" type="button"
                        style="padding:9px 18px;border-radius:8px;border:none;cursor:pointer;font-size:13px;font-weight:600;font-family:var(--sa-font-body);background:var(--sa-secondary);color:#fff;transition:filter 200ms;white-space:nowrap"
                        onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                    Salvar
                </button>
            </div>
            <p x-show="erro" x-text="erro" style="font-size:12px;color:#ef4444;margin-top:8px"></p>
        </div>

        {{-- Lista de bloqueios --}}
        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);overflow:hidden">
            <div x-show="bloqueios.length === 0" style="padding:24px;text-align:center;color:var(--sa-text3);font-size:14px">
                Nenhum bloqueio cadastrado.
            </div>
            <table x-show="bloqueios.length > 0" style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:var(--sa-surface2);border-bottom:1px solid var(--sa-border)">
                        <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Período</th>
                        <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Motivo</th>
                        <th style="padding:10px 16px;text-align:right;font-size:11px;font-weight:600;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.05em">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="b in bloqueios" :key="b.id">
                        <tr style="border-bottom:1px solid var(--sa-border);transition:background 120ms"
                            onmouseover="this.style.background='var(--sa-surface2)'"
                            onmouseout="this.style.background='transparent'">
                            <td style="padding:12px 16px;font-size:14px;color:var(--sa-text1)">
                                <span x-text="formatDate(b.data_inicio)"></span>
                                <span style="color:var(--sa-text3)"> → </span>
                                <span x-text="formatDate(b.data_fim)"></span>
                            </td>
                            <td style="padding:12px 16px;font-size:13px;color:var(--sa-text3)" x-text="b.motivo || '—'"></td>
                            <td style="padding:12px 16px;text-align:right">
                                <button @click="remover(b.id)" type="button"
                                        style="width:30px;height:30px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;color:var(--sa-text3);transition:all 150ms"
                                        onmouseover="this.style.borderColor='#ef4444';this.style.color='#ef4444'"
                                        onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function bloqueiosApp() {
        return {
            bloqueios: [],
            showForm: false,
            form: { data_inicio: '', data_fim: '', motivo: '' },
            erro: '',
            csrf: document.querySelector('meta[name=csrf-token]').content,
            async load() {
                const r = await fetch('{{ route('profissionais.bloqueios.index', $profissional) }}');
                if (r.ok) this.bloqueios = await r.json();
            },
            async salvar() {
                this.erro = '';
                if (!this.form.data_inicio || !this.form.data_fim) { this.erro = 'Preencha as datas.'; return; }
                if (this.form.data_fim < this.form.data_inicio) { this.erro = 'Data fim deve ser após o início.'; return; }
                const r = await fetch('{{ route('profissionais.bloqueios.store', $profissional) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify(this.form),
                });
                if (r.ok || r.status === 201) {
                    const b = await r.json();
                    this.bloqueios.push(b);
                    this.bloqueios.sort((a,b) => a.data_inicio.localeCompare(b.data_inicio));
                    this.form = { data_inicio: '', data_fim: '', motivo: '' };
                    this.showForm = false;
                } else {
                    this.erro = 'Erro ao salvar.';
                }
            },
            async remover(id) {
                const conf = await Swal.fire({
                    title: 'Remover bloqueio?', icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Remover', cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#ef4444', cancelButtonColor: 'transparent',
                    customClass: { cancelButton: 'swal-cancel-muted' },
                });
                if (!conf.isConfirmed) return;
                await fetch(`/bloqueios/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': this.csrf } });
                this.bloqueios = this.bloqueios.filter(b => b.id !== id);
            },
            formatDate(iso) {
                if (!iso) return '';
                const [y, m, d] = iso.split('-');
                return `${d}/${m}/${y}`;
            },
        };
    }
    </script>
</div>
@endsection
