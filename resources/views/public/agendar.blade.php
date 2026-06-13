@extends('layouts.public')
@section('title', 'Agendar — ' . $company->name)

@section('header-right')
<div style="margin-left:auto;font-size:13px;color:rgba(255,255,255,.6)">{{ $company->name }}</div>
@endsection

@section('content')
<div x-data="agendadorPublico()" x-init="init()">

    {{-- Empresa header --}}
    <div style="text-align:center;margin-bottom:32px">
        <h1 style="font-family:var(--sa-font-heading);font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 6px">{{ $company->name }}</h1>
        <p style="font-size:14px;color:var(--sa-text3);margin:0">Selecione o serviço, o profissional e o horário</p>
    </div>

    {{-- Step indicator --}}
    <div style="display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:32px;flex-wrap:wrap">
        @foreach(['Serviço', 'Profissional', 'Horário', 'Dados'] as $i => $label)
        @php $n = $i + 1; @endphp
        <div style="display:flex;align-items:center;gap:8px">
            <div :style="step >= {{ $n }} ? 'background:var(--sa-primary);color:#fff' : 'background:var(--sa-surface);color:var(--sa-text3);border:1.5px solid var(--sa-border)'"
                 style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;transition:all 200ms;flex-shrink:0">
                {{ $n }}
            </div>
            <span style="font-size:12px;font-weight:600" :style="step >= {{ $n }} ? 'color:var(--sa-text1)' : 'color:var(--sa-text3)'">{{ $label }}</span>
            @if($n < 4)
            <div style="width:24px;height:1px;background:var(--sa-border)"></div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- STEP 1: Serviço --}}
    <div x-show="step === 1" x-transition>
        <h2 style="font-size:16px;font-weight:700;color:var(--sa-text1);margin:0 0 18px">Escolha o serviço</h2>
        <div style="display:flex;flex-direction:column;gap:12px">
            @foreach($servicos as $servico)
            <button type="button" @click="selecionarServico('{{ $servico->id }}')"
                    :style="servicoId === '{{ $servico->id }}' ? 'border-color:var(--sa-primary);background:color-mix(in srgb,var(--sa-primary) 4%,transparent)' : ''"
                    style="display:flex;align-items:center;gap:14px;width:100%;padding:16px 18px;border-radius:12px;border:1.5px solid var(--sa-border);background:var(--sa-surface);cursor:pointer;text-align:left;transition:all 150ms">
                <div style="width:14px;height:14px;border-radius:50%;background:{{ $servico->cor }};flex-shrink:0"></div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $servico->nome }}</div>
                    @if($servico->categoria)
                    <div style="font-size:12px;color:var(--sa-text3);margin-top:2px">{{ $servico->categoria }}</div>
                    @endif
                </div>
                <div style="text-align:right;flex-shrink:0">
                    <div style="font-size:14px;font-weight:700;color:var(--sa-secondary)">{{ $servico->precoFormatado() }}</div>
                    <div style="font-size:12px;color:var(--sa-text3);margin-top:2px">{{ $servico->duracaoFormatada() }}</div>
                </div>
            </button>
            @endforeach
        </div>
    </div>

    {{-- STEP 2: Profissional --}}
    <div x-show="step === 2" x-transition>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px">
            <button type="button" @click="step = 1"
                    style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--sa-border);border-radius:8px;background:transparent;cursor:pointer;color:var(--sa-text3);flex-shrink:0">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <h2 style="font-size:16px;font-weight:700;color:var(--sa-text1);margin:0">Escolha o profissional</h2>
        </div>
        <div style="display:flex;flex-direction:column;gap:12px">
            @foreach($profissionais as $prof)
            <template x-if="profissionaisDoServico.includes('{{ $prof->id }}')">
                <button type="button" @click="selecionarProfissional('{{ $prof->id }}')"
                        :style="profissionalId === '{{ $prof->id }}' ? 'border-color:var(--sa-primary);background:color-mix(in srgb,var(--sa-primary) 4%,transparent)' : ''"
                        style="display:flex;align-items:center;gap:14px;width:100%;padding:16px 18px;border-radius:12px;border:1.5px solid var(--sa-border);background:var(--sa-surface);cursor:pointer;text-align:left;transition:all 150ms">
                    <div style="width:42px;height:42px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;flex-shrink:0">
                        {{ strtoupper(substr($prof->name, 0, 1)) }}
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $prof->name }}</div>
                        @if($prof->especialidade)
                        <div style="font-size:12px;color:var(--sa-text3);margin-top:2px">{{ $prof->especialidade }}</div>
                        @endif
                    </div>
                    <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:var(--sa-secondary);flex-shrink:0">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Ver horários
                    </span>
                </button>
            </template>
            @endforeach
        </div>
    </div>

    {{-- STEP 3: Horários do profissional (dias + slots) --}}
    <div x-show="step === 3" x-transition>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px">
            <button type="button" @click="step = 2"
                    style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--sa-border);border-radius:8px;background:transparent;cursor:pointer;color:var(--sa-text3);flex-shrink:0">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <h2 style="font-size:16px;font-weight:700;color:var(--sa-text1);margin:0">Horários de <span x-text="profissionalNome"></span></h2>
        </div>

        {{-- Abas de dia --}}
        <div style="display:flex;gap:8px;overflow-x:auto;padding-bottom:6px;margin-bottom:20px">
            <template x-for="(d, i) in dias" :key="d.iso">
                <button type="button" @click="selecionarDia(d.iso)"
                        :style="data === d.iso ? 'border-color:var(--sa-secondary);background:var(--sa-secondary);color:#fff' : 'border-color:var(--sa-border);background:var(--sa-surface);color:var(--sa-text1)'"
                        style="padding:9px 14px;border-radius:10px;border:1.5px solid;min-width:64px;flex-shrink:0;cursor:pointer;text-align:center;transition:all 180ms">
                    <div style="font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;opacity:.7" x-text="i === 0 ? 'Hoje' : d.weekday"></div>
                    <div style="font-family:var(--sa-font-heading);font-size:18px;font-weight:700;line-height:1.1" x-text="d.day"></div>
                </button>
            </template>
        </div>

        {{-- Contador / estado --}}
        <div x-show="data && !carregandoSlots" x-cloak style="margin-bottom:14px">
            <div :style="slotsLivres > 0 ? 'background:rgba(16,185,129,.08);border-color:rgba(16,185,129,.2);color:#059669' : 'background:rgba(239,68,68,.06);border-color:rgba(239,68,68,.15);color:#dc2626'"
                 style="display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:8px;border:1px solid">
                <span style="width:7px;height:7px;border-radius:50%;background:currentColor;flex-shrink:0"></span>
                <span style="font-size:13px;font-weight:600" x-text="slotsLivres > 0 ? (slotsLivres + ' horários disponíveis') : 'Nenhum horário disponível neste dia'"></span>
            </div>
        </div>

        {{-- Slots --}}
        <div x-show="data && !carregandoSlots" x-cloak style="display:flex;flex-wrap:wrap;gap:10px">
            <template x-for="slot in slots" :key="slot.hora">
                <button type="button" x-show="slot.disponivel" @click="selecionarHorario(slot.hora)"
                        :style="horario === slot.hora ? 'background:var(--sa-primary);color:#fff;border-color:var(--sa-primary)' : 'background:var(--sa-surface);color:var(--sa-text1);border-color:var(--sa-border)'"
                        style="padding:9px 16px;border-radius:9px;border:1.5px solid;font-size:13px;font-weight:600;cursor:pointer;transition:all 150ms">
                    <span x-text="slot.hora"></span>
                </button>
            </template>
        </div>

        <div x-show="carregandoSlots" x-cloak style="text-align:center;padding:24px;color:var(--sa-text3);font-size:14px">
            Buscando horários…
        </div>
    </div>

    {{-- STEP 4: Dados pessoais --}}
    <div x-show="step === 4" x-transition>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
            <button type="button" @click="step = 3"
                    style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--sa-border);border-radius:8px;background:transparent;cursor:pointer;color:var(--sa-text3);flex-shrink:0">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <h2 style="font-size:16px;font-weight:700;color:var(--sa-text1);margin:0">Seus dados</h2>
        </div>

        {{-- Resumo --}}
        <div style="background:color-mix(in srgb,var(--sa-secondary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-secondary) 20%,transparent);border-radius:10px;padding:16px 18px;margin-bottom:20px">
            <div style="font-size:13px;color:var(--sa-text2);display:flex;flex-direction:column;gap:5px">
                <span style="font-weight:600;color:var(--sa-text1)" x-text="servicoNome"></span>
                <span style="color:var(--sa-text3)" x-text="profissionalNome"></span>
                <span style="font-weight:600;color:var(--sa-secondary)" x-text="dataExtenso + ' às ' + horario"></span>
            </div>
        </div>

        @if($politica ?? null)
        <div style="background:var(--sa-surface2);border:1px solid var(--sa-border);border-radius:10px;padding:12px 16px;margin-bottom:20px">
            <p style="font-size:12px;color:var(--sa-text3);margin:0;line-height:1.6">{{ $politica }}</p>
        </div>
        @endif

        @if($errors->any())
        <div style="background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 16px;margin-bottom:16px">
            @foreach($errors->all() as $erro)
            <p style="font-size:13px;color:#dc2626;margin:0">{{ $erro }}</p>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('agendar.store', $company->slug) }}" @submit="enviando = true"
              style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;display:flex;flex-direction:column;gap:18px">
            @csrf
            <input type="hidden" name="servico_id" :value="servicoId">
            <input type="hidden" name="profissional_id" :value="profissionalId">
            <input type="hidden" name="data_hora" :value="data + ' ' + horario">

            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:6px">
                    Nome completo <span style="color:var(--sa-secondary)">*</span>
                </label>
                <input type="text" name="cliente_nome" required value="{{ old('cliente_nome') }}"
                       style="width:100%;padding:11px 14px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                       onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
            </div>

            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:6px">
                    WhatsApp / Telefone <span style="color:var(--sa-secondary)">*</span>
                </label>
                <input type="tel" name="cliente_phone" required placeholder="(11) 99999-9999" value="{{ old('cliente_phone') }}"
                       style="width:100%;padding:11px 14px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                       onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
            </div>

            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:6px">E-mail (opcional)</label>
                <input type="email" name="cliente_email" value="{{ old('cliente_email') }}"
                       style="width:100%;padding:11px 14px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                       onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
            </div>

            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:6px">Observações (opcional)</label>
                <textarea name="observacao" rows="2"
                          style="width:100%;padding:11px 14px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms;resize:vertical"
                          onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">{{ old('observacao') }}</textarea>
            </div>

            <label style="display:flex;gap:10px;align-items:flex-start;cursor:pointer">
                <input type="checkbox" name="consent" value="1" required style="margin-top:2px;accent-color:var(--sa-primary);flex-shrink:0">
                <span style="font-size:12px;color:var(--sa-text3);line-height:1.6">Concordo com o uso dos meus dados para o agendamento e autorizo o contato via WhatsApp/e-mail.</span>
            </label>

            <button type="submit" :disabled="enviando"
                    :style="enviando ? 'opacity:.6;cursor:not-allowed' : ''"
                    style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:13px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;background:var(--sa-primary);color:#fff;transition:filter 200ms"
                    onmouseover="if(!this.disabled)this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <span x-text="enviando ? 'Enviando…' : 'Confirmar agendamento'"></span>
            </button>
        </form>
    </div>
</div>

@push('styles')
<style>[x-cloak]{display:none!important}</style>
@endpush

<script>
const SERVICOS_MAP = @json($servicosMap);
const PROFISSIONAIS = @json($profissionais->map(fn($p) => ['id' => $p->id, 'name' => $p->name]));
const SLOTS_URL = '{{ route('agendar.slots', $company->slug) }}';

function agendadorPublico() {
    return {
        step: 1,
        servicoId: null,
        servicoNome: '',
        profissionalId: null,
        profissionalNome: '',
        profissionaisDoServico: [],
        dias: [],
        data: '',
        dataExtenso: '',
        horario: null,
        slots: [],
        carregandoSlots: false,
        enviando: false,

        get slotsLivres() {
            return this.slots.filter(s => s.disponivel).length;
        },

        gerarDias() {
            const semana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
            const dias = [];
            for (let i = 0; i < 14; i++) {
                const d = new Date();
                d.setHours(12, 0, 0, 0);
                d.setDate(d.getDate() + i);
                dias.push({
                    iso: d.toISOString().split('T')[0],
                    weekday: semana[d.getDay()],
                    day: String(d.getDate()).padStart(2, '0'),
                });
            }
            this.dias = dias;
        },

        async init() {
            this.gerarDias();
            const p = new URLSearchParams(window.location.search);
            const qServico = p.get('servico_id');
            const qProf = p.get('profissional_id');
            const qData = p.get('data');
            const qHora = p.get('hora');

            if (qServico && SERVICOS_MAP[qServico]) {
                const s = SERVICOS_MAP[qServico];
                this.servicoId = qServico;
                this.servicoNome = s.nome;
                this.profissionaisDoServico = s.profissionais;
            }
            if (qProf) {
                this.profissionalId = qProf;
                const pr = PROFISSIONAIS.find(x => x.id === qProf);
                this.profissionalNome = pr ? pr.name : '';
            }
            if (qData) { this.data = qData; this.atualizarDataExtenso(); }

            if (qServico && qProf && qData) {
                await this.buscarSlots();
                if (qHora && this.slots.find(s => s.hora === qHora && s.disponivel)) {
                    this.horario = qHora;
                    this.step = 4;
                } else {
                    this.step = 3;
                }
            } else if (qServico && qProf) {
                this.step = 3;
            } else if (qServico) {
                this.step = 2;
            }
        },

        selecionarServico(id) {
            this.servicoId = id;
            const s = SERVICOS_MAP[id];
            this.servicoNome = s.nome;
            this.profissionaisDoServico = s.profissionais;
            this.profissionalId = null;
            this.data = '';
            this.slots = [];
            this.horario = null;
            this.step = 2;
        },

        selecionarProfissional(id) {
            this.profissionalId = id;
            const p = PROFISSIONAIS.find(x => x.id === id);
            this.profissionalNome = p ? p.name : '';
            this.data = '';
            this.slots = [];
            this.horario = null;
            this.step = 3;
        },

        selecionarDia(iso) {
            this.data = iso;
            this.atualizarDataExtenso();
            this.buscarSlots();
        },

        atualizarDataExtenso() {
            if (!this.data) { this.dataExtenso = ''; return; }
            const [y, m, d] = this.data.split('-');
            this.dataExtenso = `${d}/${m}/${y}`;
        },

        async buscarSlots() {
            if (!this.data || !this.profissionalId || !this.servicoId) return;
            this.carregandoSlots = true;
            this.slots = [];
            this.horario = null;
            try {
                const r = await fetch(`${SLOTS_URL}?profissional_id=${this.profissionalId}&servico_id=${this.servicoId}&data=${this.data}`);
                this.slots = await r.json();
            } catch (e) { this.slots = []; }
            this.carregandoSlots = false;
        },

        selecionarHorario(h) {
            this.horario = h;
            this.step = 4;
        },
    };
}
</script>
@endsection
