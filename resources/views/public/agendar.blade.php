@extends('layouts.public')
@section('title', 'Agendar — ' . $company->name)

@section('header-right')
<div style="margin-left:auto;font-size:13px;color:rgba(255,255,255,.6)">{{ $company->name }}</div>
@endsection

@section('content')
<div x-data="agendadorPublico()" x-init="init()">

    {{-- Empresa header --}}
    <div style="text-align:center;margin-bottom:28px">
        <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin-bottom:4px">{{ $company->name }}</h1>
        <p style="font-size:14px;color:var(--sa-text3)">Selecione o serviço, profissional e horário</p>
    </div>

    {{-- Step indicator --}}
    <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:28px">
        @foreach(['Serviço', 'Profissional', 'Horário', 'Dados'] as $i => $label)
        @php $n = $i + 1; @endphp
        <div style="display:flex;align-items:center;gap:8px">
            <div :style="step >= {{ $n }} ? 'background:var(--sa-primary);color:#fff' : 'background:var(--sa-surface);color:var(--sa-text3);border:1.5px solid var(--sa-border)'"
                 style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;transition:all 200ms;flex-shrink:0">
                {{ $n }}
            </div>
            <span style="font-size:12px;font-weight:600;" :style="step >= {{ $n }} ? 'color:var(--sa-text1)' : 'color:var(--sa-text3)'">{{ $label }}</span>
            @if($n < 4)
            <div style="width:24px;height:1px;background:var(--sa-border)"></div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- STEP 1: Serviço --}}
    <div x-show="step === 1" x-transition>
        <h2 style="font-size:16px;font-weight:700;color:var(--sa-text1);margin-bottom:16px">Escolha o Serviço</h2>
        <div style="display:flex;flex-direction:column;gap:10px">
            @foreach($servicos as $servico)
            <button type="button" @click="selecionarServico('{{ $servico->id }}')"
                    :style="servicoId === '{{ $servico->id }}' ? 'border-color:var(--sa-primary);background:color-mix(in srgb,var(--sa-primary) 4%,transparent)' : ''"
                    style="display:flex;align-items:center;gap:14px;width:100%;padding:16px 18px;border-radius:12px;border:1.5px solid var(--sa-border);background:var(--sa-surface);cursor:pointer;text-align:left;transition:all 150ms"
                    onmouseover="if(this.getAttribute('data-id')!=='{{ $servico->id }}'){this.style.borderColor='var(--sa-text3)'}"
                    onmouseout="if(this.getAttribute('data-id')!=='{{ $servico->id }}'){this.style.borderColor='var(--sa-border)'}">
                <div style="width:14px;height:14px;border-radius:50%;background:{{ $servico->cor }};flex-shrink:0"></div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $servico->nome }}</div>
                    @if($servico->categoria)
                    <div style="font-size:12px;color:var(--sa-text3)">{{ $servico->categoria }}</div>
                    @endif
                </div>
                <div style="text-align:right;flex-shrink:0">
                    <div style="font-size:14px;font-weight:700;color:var(--sa-secondary)">{{ $servico->precoFormatado() }}</div>
                    <div style="font-size:12px;color:var(--sa-text3)">{{ $servico->duracaoFormatada() }}</div>
                </div>
            </button>
            @endforeach
        </div>
    </div>

    {{-- STEP 2: Profissional --}}
    <div x-show="step === 2" x-transition>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
            <button type="button" @click="step = 1"
                    style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--sa-border);border-radius:8px;background:transparent;cursor:pointer;color:var(--sa-text3)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <h2 style="font-size:16px;font-weight:700;color:var(--sa-text1);margin:0">Escolha o Profissional</h2>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px">
            @foreach($profissionais as $prof)
            <template x-if="profissionaisDoServico.includes('{{ $prof->id }}')">
                <button type="button" @click="selecionarProfissional('{{ $prof->id }}')"
                        :style="profissionalId === '{{ $prof->id }}' ? 'border-color:var(--sa-primary);background:color-mix(in srgb,var(--sa-primary) 4%,transparent)' : ''"
                        style="display:flex;align-items:center;gap:14px;width:100%;padding:16px 18px;border-radius:12px;border:1.5px solid var(--sa-border);background:var(--sa-surface);cursor:pointer;text-align:left;transition:all 150ms">
                    <div style="width:40px;height:40px;border-radius:50%;background:var(--sa-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;flex-shrink:0">
                        {{ strtoupper(substr($prof->name, 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-size:14px;font-weight:600;color:var(--sa-text1)">{{ $prof->name }}</div>
                        @if($prof->especialidade)
                        <div style="font-size:12px;color:var(--sa-text3)">{{ $prof->especialidade }}</div>
                        @endif
                    </div>
                </button>
            </template>
            @endforeach
        </div>
    </div>

    {{-- STEP 3: Data + Hora --}}
    <div x-show="step === 3" x-transition>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
            <button type="button" @click="step = 2"
                    style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--sa-border);border-radius:8px;background:transparent;cursor:pointer;color:var(--sa-text3)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <h2 style="font-size:16px;font-weight:700;color:var(--sa-text1);margin:0">Escolha a Data e Horário</h2>
        </div>

        <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:20px;margin-bottom:16px">
            <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:6px">Data</label>
            <input type="date" x-model="data" :min="hoje" @change="buscarSlots()"
                   style="padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms;width:100%;max-width:220px"
                   onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
        </div>

        <div x-show="data && !carregandoSlots">
            <div style="margin-bottom:10px">
                <span style="font-size:13px;font-weight:600;color:var(--sa-text2)">Horários disponíveis</span>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                <template x-for="slot in slots">
                    <button type="button"
                            x-show="slot.disponivel"
                            @click="selecionarHorario(slot.hora)"
                            :style="horario === slot.hora ? 'background:var(--sa-primary);color:#fff;border-color:var(--sa-primary)' : ''"
                            style="padding:8px 16px;border-radius:8px;border:1.5px solid var(--sa-border);background:var(--sa-surface);font-size:13px;font-weight:600;color:var(--sa-text1);cursor:pointer;transition:all 150ms">
                        <span x-text="slot.hora"></span>
                    </button>
                </template>
                <p x-show="slots.length === 0 && data" style="font-size:13px;color:var(--sa-text3)">
                    Nenhum horário disponível nesta data. Tente outro dia.
                </p>
            </div>
        </div>
        <div x-show="carregandoSlots" style="text-align:center;padding:20px;color:var(--sa-text3);font-size:14px">
            Buscando horários...
        </div>
    </div>

    {{-- STEP 4: Dados pessoais --}}
    <div x-show="step === 4" x-transition>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
            <button type="button" @click="step = 3"
                    style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--sa-border);border-radius:8px;background:transparent;cursor:pointer;color:var(--sa-text3)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <h2 style="font-size:16px;font-weight:700;color:var(--sa-text1);margin:0">Seus Dados</h2>
        </div>

        {{-- Resumo --}}
        <div style="background:color-mix(in srgb,var(--sa-secondary) 8%,transparent);border:1px solid color-mix(in srgb,var(--sa-secondary) 20%,transparent);border-radius:10px;padding:14px 16px;margin-bottom:20px">
            <div style="font-size:12px;color:var(--sa-text2);display:flex;flex-direction:column;gap:4px">
                <span><strong x-text="servicoNome"></strong></span>
                <span x-text="profissionalNome" style="color:var(--sa-text3)"></span>
                <span style="font-weight:600;color:var(--sa-secondary)" x-text="data + ' às ' + horario"></span>
            </div>
        </div>

        <form method="POST" action="{{ route('agendar.store', $company->slug) }}"
              style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:24px;display:flex;flex-direction:column;gap:16px">
            @csrf
            <input type="hidden" name="servico_id" :value="servicoId">
            <input type="hidden" name="profissional_id" :value="profissionalId">
            <input type="hidden" name="data_hora" :value="data + ' ' + horario">

            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
                    Nome completo <span style="color:var(--sa-secondary)">*</span>
                </label>
                <input type="text" name="cliente_nome" required
                       style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                       onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
            </div>

            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">
                    WhatsApp / Telefone <span style="color:var(--sa-secondary)">*</span>
                </label>
                <input type="tel" name="cliente_phone" required placeholder="(11) 99999-9999"
                       style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                       onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
            </div>

            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">E-mail (opcional)</label>
                <input type="email" name="cliente_email"
                       style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                       onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
            </div>

            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Observações (opcional)</label>
                <textarea name="observacao" rows="2"
                          style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms;resize:vertical"
                          onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'"></textarea>
            </div>

            <button type="submit"
                    style="display:flex;align-items:center;justify-content:center;gap:7px;width:100%;padding:12px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:var(--sa-primary);color:#fff;transition:filter 200ms"
                    onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                Confirmar Agendamento
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
        data: '',
        hoje: new Date().toISOString().split('T')[0],
        horario: null,
        slots: [],
        carregandoSlots: false,

        init() {},

        selecionarServico(id) {
            this.servicoId = id;
            const s = SERVICOS_MAP[id];
            this.servicoNome = s.nome;
            this.profissionaisDoServico = s.profissionais;
            this.profissionalId = null;
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
