@extends('layouts.app')
@section('title', 'Editar Agendamento')
@section('page-title', 'Editar Agendamento')

@section('content')

    <div style="display:flex;align-items:center;gap:14px;margin-bottom:24px">
        <a href="{{ route('agendamentos.show', $agendamento) }}" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1.5px solid var(--sa-border);border-radius:8px;text-decoration:none;color:var(--sa-text3);transition:all 150ms" onmouseover="this.style.borderColor='var(--sa-secondary)';this.style.color='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div>
            <h1 style="font-family:'Poppins',sans-serif;font-size:20px;font-weight:700;color:var(--sa-text1);margin:0 0 2px">Editar Agendamento</h1>
            <p style="font-size:13px;color:var(--sa-text3);margin:0">{{ $agendamento->cliente->name ?? '—' }} — {{ $agendamento->data_hora->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <div style="background:var(--sa-surface);border-radius:12px;border:1px solid var(--sa-border);padding:28px"
         x-data="{
             servicoId: '{{ old('servico_id', $agendamento->servico_id ?? '') }}',
             duracao: {{ old('duracao', $agendamento->duracao) }},
             valor: '{{ old('valor', $agendamento->valor ?? '') }}',
             profissionalId: '{{ old('profissional_id', $agendamento->profissional_id) }}',
             servicoData: {{ Js::from($servicosMap) }},
             allProfissionais: {{ Js::from($profissionaisMap) }},
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
                     this.duracao = {{ $agendamento->duracao }};
                     this.valor = '{{ $agendamento->valor ?? '' }}';
                 }
                 if (this.profissionalId && !this.profissionaisFiltrados.find(p => p.id === this.profissionalId)) {
                     this.profissionalId = '';
                 }
             }
         }">
        <form method="POST" action="{{ route('agendamentos.update', $agendamento) }}" style="display:flex;flex-direction:column;gap:18px">
            @csrf
            @method('PUT')

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

                {{-- Serviço --}}
                <div style="grid-column:1/-1">
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Serviço</label>
                    <select name="servico_id" x-model="servicoId" @change="onServicoChange()"
                            style="width:100%;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms;cursor:pointer"
                            onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                        <option value="">— Nenhum serviço —</option>
                        @foreach($servicos as $servico)
                        <option value="{{ $servico->id }}">{{ $servico->nome }} ({{ $servico->duracaoFormatada() }} — {{ $servico->precoFormatado() }})</option>
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
                            <option :value="p.id" :selected="p.id === profissionalId" x-text="p.name + (p.especialidade ? ' — ' + p.especialidade : '')"></option>
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
                        @foreach($clientes as $c)
                        <option value="{{ $c->id }}" {{ old('cliente_id', $agendamento->cliente_id) === $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('cliente_id')<p style="font-size:12px;color:#e53e3e;margin-top:4px">{{ $message }}</p>@enderror
                </div>

                {{-- Data e Hora --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Data e Hora <span style="color:var(--sa-secondary)">*</span></label>
                    <input type="datetime-local" name="data_hora" required
                           value="{{ old('data_hora', $agendamento->data_hora->format('Y-m-d\TH:i')) }}"
                           style="width:100%;padding:10px 12px;border:1.5px solid {{ $errors->has('data_hora') ? '#e53e3e' : 'var(--sa-border)' }};border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms"
                           onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                    @error('data_hora')<p style="font-size:12px;color:#e53e3e;margin-top:4px">{{ $message }}</p>@enderror
                </div>

                {{-- Duração --}}
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Duração (minutos) <span style="color:var(--sa-secondary)">*</span></label>
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

                {{-- Status --}}
                <div style="grid-column:1/-1">
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Status</label>
                    <select name="status"
                            style="width:100%;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;transition:border-color 180ms,outline 180ms;cursor:pointer"
                            onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                        @foreach(['pendente','confirmado','finalizado','cancelado'] as $s)
                        <option value="{{ $s }}" {{ old('status', $agendamento->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Observação --}}
                <div style="grid-column:1/-1">
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Observação</label>
                    <textarea name="observacao" rows="2" maxlength="1000" placeholder="Informações adicionais..."
                              style="width:100%;padding:10px 12px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;color:var(--sa-text1);background:var(--sa-surface);outline:none;resize:vertical;transition:border-color 180ms,outline 180ms;font-family:'Inter',sans-serif"
                              onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'" onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">{{ old('observacao', $agendamento->observacao) }}</textarea>
                </div>
            </div>

            <div style="display:flex;gap:10px;padding-top:4px">
                <button type="submit"
                        style="padding:11px 24px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;background:var(--sa-primary);color:#fff;transition:filter 200ms"
                        onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
                    Salvar Alterações
                </button>
                <a href="{{ route('agendamentos.show', $agendamento) }}"
                   style="padding:11px 20px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;text-decoration:none;transition:all 180ms"
                   onmouseover="this.style.borderColor='var(--sa-secondary)'" onmouseout="this.style.borderColor='var(--sa-border)'">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
@endsection
