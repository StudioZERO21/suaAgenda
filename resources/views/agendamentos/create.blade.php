@extends('layouts.app')
@section('title', 'Novo Agendamento')
@section('page-title', 'Novo Agendamento')

@section('content')
<div style="max-width:760px"
     x-data="{
         servicoId: '{{ old('servico_id', '') }}',
         duracao: {{ old('duracao', 60) }},
         valor: '{{ old('valor', '') }}',
         profissionalId: '{{ old('profissional_id', '') }}',
         servicoData: {{ Js::from($servicosMap) }},
         allProfissionais: {{ Js::from($profissionaisMap) }},
         recorrente: {{ old('recorrente') ? 'true' : 'false' }},
         recorrenciaTipo: '{{ old('recorrencia_tipo', 'semanal') }}',
         recorrenciaLimite: '{{ old('recorrencia_tipo_limite', 'ocorrencias') }}',
         recorrenciaTotal: {{ old('recorrencia_total', 4) }},
         get profissionaisFiltrados() {
             if (!this.servicoId) return this.allProfissionais;
             const s = this.servicoData[this.servicoId];
             if (!s || !s.profissionais.length) return this.allProfissionais;
             return this.allProfissionais.filter(p => s.profissionais.includes(p.id));
         },
         onServicoChange() {
             const s = this.servicoData[this.servicoId];
             if (s) {
                 this.duracao = s.duracao_minutos;
                 this.valor = s.preco;
             } else {
                 this.duracao = 60;
                 this.valor = '';
             }
             if (this.profissionalId && !this.profissionaisFiltrados.find(p => p.id === this.profissionalId)) {
                 this.profissionalId = '';
             }
         }
     }">

    <div style="display:flex;align-items:center;gap:14px;margin-bottom:24px">
        <a href="{{ route('agendamentos.index') }}" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--sa-border);border-radius:8px;text-decoration:none;color:var(--sa-text3);transition:all 150ms" onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div>
            <h1 style="font-family:var(--sa-font-heading);font-size:20px;font-weight:700;color:var(--sa-text1);margin:0 0 2px">Novo Agendamento</h1>
            <p style="font-size:13px;color:var(--sa-text3);margin:0">Preencha os dados do agendamento</p>
        </div>
    </div>

    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px">
        <form method="POST" action="{{ route('agendamentos.store') }}" style="display:flex;flex-direction:column;gap:18px">
            @csrf

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

                {{-- Servi�o --}}
                <div style="grid-column:1/-1">
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Servi�o</label>
                    <select name="servico_id" x-model="servicoId" @change="onServicoChange()"
                            style="width:100%;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms;cursor:pointer"
                            onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                        <option value="">� Nenhum servi�o �</option>
                        @foreach($servicos as $servico)
                        <option value="{{ $servico->id }}">{{ $servico->nome }} ({{ $servico->duracaoFormatada() }} � {{ $servico->precoFormatado() }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Profissional --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Profissional <span style="color:var(--sa-secondary)">*</span></label>
                    <select name="profissional_id" x-model="profissionalId" required
                            style="width:100%;padding:10px 12px;border:1.5px solid {{ $errors->has('profissional_id') ? '#e53e3e' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms;cursor:pointer"
                            onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                        <option value="">Selecionar profissional</option>
                        <template x-for="p in profissionaisFiltrados" :key="p.id">
                            <option :value="p.id" :selected="p.id === profissionalId" x-text="p.name + (p.especialidade ? ' � ' + p.especialidade : '')"></option>
                        </template>
                    </select>
                    @error('profissional_id')<p style="font-size:12px;color:#e53e3e;margin-top:4px">{{ $message }}</p>@enderror
                </div>

                {{-- Cliente --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Cliente <span style="color:var(--sa-secondary)">*</span></label>
                    <select name="cliente_id" required
                            style="width:100%;padding:10px 12px;border:1.5px solid {{ $errors->has('cliente_id') ? '#e53e3e' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms;cursor:pointer"
                            onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                        <option value="">Selecionar cliente</option>
                        @foreach($clientes as $c)
                        <option value="{{ $c->id }}" {{ old('cliente_id') === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('cliente_id')<p style="font-size:12px;color:#e53e3e;margin-top:4px">{{ $message }}</p>@enderror
                </div>

                {{-- Data e Hora --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Data e Hora <span style="color:var(--sa-secondary)">*</span></label>
                    <input type="datetime-local" name="data_hora" value="{{ old('data_hora') }}" required
                           style="width:100%;padding:10px 12px;border:1.5px solid {{ $errors->has('data_hora') ? '#e53e3e' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                           onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    @error('data_hora')<p style="font-size:12px;color:#e53e3e;margin-top:4px">{{ $message }}</p>@enderror
                </div>

                {{-- Dura��o --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Dura��o (minutos) <span style="color:var(--sa-secondary)">*</span></label>
                    <input type="number" name="duracao" x-model="duracao" min="15" max="480" required
                           style="width:100%;padding:10px 12px;border:1.5px solid {{ $errors->has('duracao') ? '#e53e3e' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                           onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    @error('duracao')<p style="font-size:12px;color:#e53e3e;margin-top:4px">{{ $message }}</p>@enderror
                </div>

                {{-- Valor --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Valor (R$)</label>
                    <input type="number" name="valor" x-model="valor" min="0" step="0.01" placeholder="0,00"
                           style="width:100%;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                           onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                </div>

                {{-- Observa��o --}}
                <div style="grid-column:1/-1">
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Observa��o</label>
                    <textarea name="observacao" rows="2" maxlength="1000" placeholder="Informa��es adicionais..."
                              style="width:100%;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;resize:vertical;transition:border-color 180ms,outline 180ms;font-family:var(--sa-font-body)"
                              onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">{{ old('observacao') }}</textarea>
                </div>

                {{-- Recorr�ncia --}}
                <div style="grid-column:1/-1;border-top:1px solid var(--sa-border);padding-top:18px">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;user-select:none">
                        <input type="checkbox" name="recorrente" value="1" x-model="recorrente"
                               style="width:16px;height:16px;accent-color:var(--sa-primary);cursor:pointer">
                        <div>
                            <span style="font-size:14px;font-weight:600;color:var(--sa-text1)">Repetir agendamento</span>
                            <span style="font-size:12px;color:var(--sa-text3);margin-left:8px">Cria automaticamente as pr�ximas ocorr�ncias</span>
                        </div>
                    </label>

                    <div x-show="recorrente" x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         style="margin-top:16px;padding:18px;background:var(--sa-surface2);border-radius:10px;border:1px solid var(--sa-border);display:flex;flex-direction:column;gap:16px">

                        {{-- Frequ�ncia --}}
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);margin-bottom:8px">Frequ�ncia <span style="color:var(--sa-secondary)">*</span></label>
                            <div style="display:flex;gap:8px;flex-wrap:wrap">
                                @foreach(['semanal' => 'Semanal (7 dias)', 'quinzenal' => 'Quinzenal (14 dias)', 'mensal' => 'Mensal'] as $val => $label)
                                <label style="display:flex;align-items:center;gap:7px;cursor:pointer;padding:8px 14px;border-radius:8px;border:1.5px solid var(--sa-border);background:var(--sa-surface);transition:all 150ms"
                                       :style="recorrenciaTipo === '{{ $val }}' ? 'border-color:var(--sa-primary);background:color-mix(in srgb,var(--sa-primary) 5%,transparent)' : ''">
                                    <input type="radio" name="recorrencia_tipo" value="{{ $val }}" x-model="recorrenciaTipo" style="accent-color:var(--sa-primary)">
                                    <span style="font-size:13px;font-weight:500;color:var(--sa-text1)">{{ $label }}</span>
                                </label>
                                @endforeach
                            </div>
                            @error('recorrencia_tipo')<p style="font-size:12px;color:#ef4444;margin-top:4px">{{ $message }}</p>@enderror
                        </div>

                        {{-- Limite --}}
                        <div>
                            <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);margin-bottom:8px">Repetir at� <span style="color:var(--sa-secondary)">*</span></label>
                            <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-start">
                                <label style="display:flex;align-items:center;gap:7px;cursor:pointer">
                                    <input type="radio" name="recorrencia_tipo_limite" value="ocorrencias" x-model="recorrenciaLimite" style="accent-color:var(--sa-primary)">
                                    <span style="font-size:13px;color:var(--sa-text1)">N�mero de ocorr�ncias</span>
                                </label>
                                <label style="display:flex;align-items:center;gap:7px;cursor:pointer">
                                    <input type="radio" name="recorrencia_tipo_limite" value="data" x-model="recorrenciaLimite" style="accent-color:var(--sa-primary)">
                                    <span style="font-size:13px;color:var(--sa-text1)">Data limite</span>
                                </label>
                            </div>

                            <div x-show="recorrenciaLimite === 'ocorrencias'" style="margin-top:10px;display:flex;align-items:center;gap:10px">
                                <input type="number" name="recorrencia_total" x-model="recorrenciaTotal" min="2" max="52"
                                       style="width:90px;padding:9px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms"
                                       onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                                <span style="font-size:13px;color:var(--sa-text2)">ocorr�ncias no total (inclui a atual)</span>
                            </div>

                            <div x-show="recorrenciaLimite === 'data'" style="margin-top:10px">
                                <input type="date" name="recorrencia_ate" value="{{ old('recorrencia_ate') }}"
                                       style="padding:9px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms"
                                       onfocus="this.style.borderColor='var(--sa-primary)'" onblur="this.style.borderColor='var(--sa-border)'">
                                <p style="font-size:12px;color:var(--sa-text3);margin-top:4px">A repeti��o continua at� esta data (m�x. 60 ocorr�ncias)</p>
                            </div>
                            @error('recorrencia_total')<p style="font-size:12px;color:#ef4444;margin-top:4px">{{ $message }}</p>@enderror
                            @error('recorrencia_ate')<p style="font-size:12px;color:#ef4444;margin-top:4px">{{ $message }}</p>@enderror
                        </div>

                        {{-- Preview din�mico --}}
                        <div style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:rgba(16,185,129,.06);border-radius:8px;border:1px solid rgba(16,185,129,.18)">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 014-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg>
                            <span style="font-size:12px;color:#059669;font-weight:600"
                                  x-text="recorrenciaLimite === 'ocorrencias'
                                    ? `Ser�o criadas ${Math.max(recorrenciaTotal - 1, 0)} ocorr�ncia(s) adicional(is) ap�s o agendamento inicial`
                                    : 'Ocorr�ncias ser�o criadas at� a data informada (m�x. 60)'">
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:10px;padding-top:4px">
                <button type="submit"
                        style="padding:11px 24px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;background:var(--sa-primary);color:#fff;transition:filter 200ms"
                        onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                    Criar Agendamento
                </button>
                <a href="{{ route('agendamentos.index') }}"
                   style="padding:11px 20px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;text-decoration:none;transition:all 180ms"
                   onmouseover="this.style.borderColor='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)'">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
@endsection
