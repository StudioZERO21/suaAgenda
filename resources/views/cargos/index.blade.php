@extends('layouts.app')
@section('title', 'Cargos')

@section('content')
<x-sa.page x-data="cargosApp()">
    <x-sa.app-header title="Cargos" subtitle="Defina os cargos da sua equipe">
        <x-slot:actions>
            <x-sa.btn @click="openNew()"
                      :icon="view('components.sa.icons.plus')->render()">
                Novo Cargo
            </x-sa.btn>
        </x-slot:actions>
    </x-sa.app-header>

    <x-sa.body>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px">
            <div class="sa-tint-card" style="--tint:var(--sa-primary)">
                <div class="sa-tint-card__label">Total de cargos</div>
                <div class="sa-tint-card__value" x-text="cargos.length"></div>
                <div class="sa-tint-card__icon"><svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="1.5"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
            </div>
            <div class="sa-tint-card" style="--tint:var(--sa-primary)">
                <div class="sa-tint-card__label">Funcionários</div>
                <div class="sa-tint-card__value" x-text="totalFuncionarios"></div>
                <div class="sa-tint-card__icon"><svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg></div>
            </div>
            <div class="sa-tint-card" style="--tint:var(--sa-primary)">
                <div class="sa-tint-card__label">Com comissão</div>
                <div class="sa-tint-card__value" x-text="comComissaoCount"></div>
                <div class="sa-tint-card__icon"><svg width="130" height="130" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="1.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg></div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
            <template x-for="cargo in cargos" :key="cargo.id">
                <div style="background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:16px;overflow:hidden;transition:box-shadow 200ms"
                     @mouseenter="$el.style.boxShadow = '0 6px 20px rgba(0,0,0,.08)'"
                     @mouseleave="$el.style.boxShadow = 'none'">
                    <div :style="'height:4px;background:' + cargo.cor"></div>
                    <div style="padding:18px 18px 0">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px">
                            <div style="display:flex;align-items:center;gap:12px">
                                <div :style="'width:42px;height:42px;border-radius:12px;background:' + cargo.cor + ';display:flex;align-items:center;justify-content:center;flex-shrink:0'">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                </div>
                                <div>
                                    <div style="font-family:var(--sa-font-heading);font-size:16px;font-weight:700;color:var(--sa-text1)" x-text="cargo.nome"></div>
                                    <div style="font-size:11px;color:var(--sa-text3);margin-top:2px" x-text="cargo.membros + ' funcion' + (cargo.membros === 1 ? 'ário' : 'ários')"></div>
                                </div>
                            </div>
                            <span :style="'font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;background:' + cargo.cor + '15;color:' + cargo.cor" x-text="cargo.nivel"></span>
                        </div>
                        <p style="font-size:12px;color:var(--sa-text3);line-height:1.6;margin:0 0 14px" x-text="cargo.descricao"></p>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px;padding:10px;background:var(--sa-surface2);border-radius:8px">
                            <div style="text-align:center">
                                <div style="font-family:var(--sa-font-heading);font-size:20px;font-weight:800;color:var(--sa-text1)" x-text="cargo.comissao > 0 ? cargo.comissao + '%' : '—'"></div>
                                <div style="font-size:10px;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.5px;margin-top:2px">Comissão</div>
                            </div>
                            <div style="text-align:center;border-left:1px solid var(--sa-border)">
                                <div style="font-family:var(--sa-font-heading);font-size:12px;font-weight:700;color:var(--sa-text1);line-height:1.4;padding-top:2px" x-text="nivelShort(cargo.nivel)"></div>
                                <div style="font-size:10px;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.5px;margin-top:2px">Nível</div>
                            </div>
                        </div>
                    </div>
                    <div style="padding:10px 14px;border-top:1px solid var(--sa-border);display:flex;gap:8px">
                        <x-sa.btn size="sm" variant="muted" style="flex:1" @click="openEdit(cargo)"
                                  :icon="'<svg width=\'13\' height=\'13\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><path d=\'M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7\'/><path d=\'M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z\'/></svg>'">
                            Editar
                        </x-sa.btn>
                        <x-sa.btn size="sm" variant="ghost" title="Excluir" @click="deleteCargo(cargo.id)"
                                  :icon="'<svg width=\'13\' height=\'13\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><polyline points=\'3 6 5 6 21 6\'/><path d=\'M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6\'/></svg>'">
                            &nbsp;
                        </x-sa.btn>
                    </div>
                </div>
            </template>

            <button type="button" @click="openNew()"
                    style="background:var(--sa-surface2);border:2px dashed var(--sa-border);border-radius:16px;padding:32px;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;transition:all 200ms;font-family:var(--sa-font-body)"
                    @mouseenter="$el.style.borderColor = 'var(--sa-primary)'; $el.style.background = 'color-mix(in srgb,var(--sa-primary) 4%,transparent)'"
                    @mouseleave="$el.style.borderColor = 'var(--sa-border)'; $el.style.background = 'var(--sa-surface2)'">
                <div style="width:44px;height:44px;border-radius:12px;background:color-mix(in srgb,var(--sa-primary) 10%,transparent);display:flex;align-items:center;justify-content:center">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--sa-primary)" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </div>
                <div style="font-size:14px;font-weight:600;color:var(--sa-text2)">Novo Cargo</div>
            </button>
        </div>
    </x-sa.body>

    <x-sa.modal open="modalOpen" size="md">
        <x-slot:title><span x-text="editing ? 'Editar Cargo' : 'Novo Cargo'"></span></x-slot:title>
        <x-slot:subtitle>Configure o cargo e suas permissões padrão</x-slot:subtitle>
        <x-slot:footer>
            <x-sa.btn variant="secondary" size="sm" @click="closeModal()">Cancelar</x-sa.btn>
            <x-sa.btn size="sm" @click="saveCargo()" x-bind:disabled="saving"
                      :icon="'<svg width=\'14\' height=\'14\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\'><polyline points=\'20 6 9 17 4 12\'/></svg>'">
                <span x-text="editing ? 'Salvar' : 'Criar Cargo'"></span>
            </x-sa.btn>
        </x-slot:footer>

        <div style="display:flex;flex-direction:column;gap:16px">
            <div style="display:flex;align-items:center;gap:14px;padding:14px;background:var(--sa-surface2);border-radius:12px;border:1px solid var(--sa-border)">
                <div :style="'width:44px;height:44px;border-radius:12px;background:' + form.cor + ';display:flex;align-items:center;justify-content:center;flex-shrink:0'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div>
                    <div style="font-family:var(--sa-font-heading);font-size:16px;font-weight:700;color:var(--sa-text1)" x-text="form.nome || 'Nome do cargo'"></div>
                    <div style="font-size:12px;color:var(--sa-text3);margin-top:2px" x-text="nivelLabel(form.nivel)"></div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div>
                    <label style="font-size:13px;font-weight:600;color:var(--sa-text1);display:block;margin-bottom:6px">Nome do cargo</label>
                    <input type="text" x-model="form.nome" placeholder="Ex: Barbeiro Sênior" class="sa-search-input" style="max-width:none;width:100%">
                </div>
                <div>
                    <label style="font-size:13px;font-weight:600;color:var(--sa-text1);display:block;margin-bottom:6px">Comissão padrão (%)</label>
                    <input type="number" x-model.number="form.comissao" min="0" max="100" @input="form.comissao = Math.min(100, Math.max(0, form.comissao || 0))"
                           style="width:100%;padding:9px 12px;font-size:13px;border:1px solid var(--sa-border);border-radius:8px;background:var(--sa-surface);color:var(--sa-text1);font-family:var(--sa-font-body);outline:none;box-sizing:border-box">
                </div>
            </div>

            <div>
                <label style="font-size:13px;font-weight:600;color:var(--sa-text1);display:block;margin-bottom:6px">Nível de permissão padrão</label>
                <select x-model="form.nivel" style="width:100%;font-size:13px;padding:9px 12px;border:1px solid var(--sa-border);border-radius:8px;background:var(--sa-surface);color:var(--sa-text1);font-family:var(--sa-font-body);cursor:pointer;outline:none">
                    <template x-for="n in niveis" :key="n.value">
                        <option :value="n.value" x-text="n.label"></option>
                    </template>
                </select>
            </div>

            <div>
                <label style="font-size:13px;font-weight:600;color:var(--sa-text1);display:block;margin-bottom:6px">Descrição do cargo</label>
                <textarea x-model="form.descricao" rows="2" placeholder="Descreva as responsabilidades..."
                          style="width:100%;padding:9px 12px;font-size:13px;border:1px solid var(--sa-border);border-radius:8px;background:var(--sa-surface);color:var(--sa-text1);font-family:var(--sa-font-body);outline:none;resize:vertical;box-sizing:border-box"></textarea>
            </div>

            <div>
                <label style="font-size:13px;font-weight:600;color:var(--sa-text1);display:block;margin-bottom:8px">Cor do cargo</label>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <template x-for="c in cores" :key="c">
                        <button type="button" @click="form.cor = c"
                                :style="'width:28px;height:28px;border-radius:50%;background:' + c + ';border:' + (form.cor === c ? '3px solid var(--sa-text1)' : '2px solid transparent') + ';cursor:pointer;transition:border 150ms'"></button>
                    </template>
                </div>
            </div>

            <div style="background:var(--sa-surface2);border-radius:10px;padding:14px 16px;border:1px solid var(--sa-border)">
                <div style="font-size:12px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">Permissões incluídas neste nível</div>
                <template x-for="perm in permPreview(form.nivel)" :key="perm">
                    <div style="display:flex;align-items:center;gap:8px;padding:4px 0">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" style="flex-shrink:0"><polyline points="20 6 9 17 4 12"/></svg>
                        <span style="font-size:12px;color:var(--sa-text2)" x-text="perm"></span>
                    </div>
                </template>
            </div>
        </div>
    </x-sa.modal>
</x-sa.page>

@push('scripts')
<script>
function cargosApp() {
    const blankForm = () => ({
        nome: '',
        nivel: 'professional',
        cor: '#6366f1',
        descricao: '',
        comissao: 0,
        membros: 0,
    });

    const permMap = {
        admin: ['Acesso total', 'Gerenciar planos', 'Editar permissões', 'Ver todos os relatórios', 'Configurações da empresa'],
        manager: ['Ver agenda completa', 'Relatórios de receita', 'Gerenciar equipe', 'Criar agendamentos'],
        professional: ['Ver própria agenda', 'Criar agendamentos próprios', 'Ver histórico de clientes', 'Ver próprias comissões'],
        receptionist: ['Ver agenda completa', 'Criar/editar agendamentos', 'Cadastrar clientes', 'Ver pagamentos'],
        intern: ['Ver própria agenda', 'Criar agendamentos (supervisionado)'],
    };

    return {
        cargos: @json($cargosJson),
        niveis: @json($niveis),
        cores: @json($cores),
        modalOpen: false,
        editing: null,
        saving: false,
        form: blankForm(),

        init() {
            this.$watch('modalOpen', val => {
                if (!val) {
                    this.editing = null;
                    this.saving = false;
                    this.form = blankForm();
                }
            });
        },

        get totalFuncionarios() {
            return this.cargos.reduce((s, c) => s + c.membros, 0);
        },

        get comComissaoCount() {
            return this.cargos.filter(c => c.comissao > 0).length;
        },

        toast(text, icon = 'success') {
            Swal.fire({ toast: true, position: 'top-end', icon, title: text, showConfirmButton: false, timer: 2800, timerProgressBar: true });
        },

        nivelLabel(value) {
            return this.niveis.find(n => n.value === value)?.label || '—';
        },

        nivelShort(value) {
            const label = this.nivelLabel(value);
            return label.split('—')[0].trim() || value;
        },

        permPreview(nivel) {
            return permMap[nivel] || [];
        },

        openNew() {
            this.editing = null;
            this.form = blankForm();
            this.modalOpen = true;
        },

        openEdit(cargo) {
            this.editing = cargo;
            this.form = {
                nome: cargo.nome,
                nivel: cargo.nivel,
                cor: cargo.cor,
                descricao: cargo.descricao,
                comissao: cargo.comissao,
                membros: cargo.membros,
            };
            this.modalOpen = true;
        },

        closeModal() {
            this.modalOpen = false;
            this.editing = null;
            this.saving = false;
            this.form = blankForm();
        },

        deleteCargo(id) {
            const cargo = this.cargos.find(c => c.id === id);
            if (cargo?.nome === 'Administrador') {
                return this.toast('Cargo padrão não pode ser excluído', 'error');
            }
            this.cargos = this.cargos.filter(c => c.id !== id);
            this.toast('Cargo removido', 'error');
        },

        saveCargo() {
            if (!this.form.nome.trim()) {
                return this.toast('Preencha o nome do cargo', 'error');
            }
            this.saving = true;
            setTimeout(() => {
                const nivelLabel = this.nivelLabel(this.form.nivel);
                if (this.editing) {
                    this.cargos = this.cargos.map(c => c.id === this.editing.id ? {
                        ...c,
                        nome: this.form.nome.trim(),
                        nivel: this.form.nivel,
                        nivel_label: nivelLabel,
                        cor: this.form.cor,
                        descricao: this.form.descricao,
                        comissao: this.form.comissao,
                    } : c);
                    this.toast('Cargo atualizado!', 'success');
                } else {
                    this.cargos.push({
                        id: Date.now(),
                        nome: this.form.nome.trim(),
                        nivel: this.form.nivel,
                        nivel_label: nivelLabel,
                        cor: this.form.cor,
                        descricao: this.form.descricao,
                        comissao: this.form.comissao,
                        membros: 0,
                    });
                    this.toast('Cargo criado!', 'success');
                }
                this.saving = false;
                this.closeModal();
            }, 600);
        },
    };
}
</script>
@endpush
@endsection
