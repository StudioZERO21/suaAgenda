@extends('layouts.app')
@section('title', 'Catálogo de Regras')
@section('page-title', 'Regras de Negócio')

@section('content')
<div x-data="regrasAdminApp()">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
        <div>
            <h1 style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:700;color:var(--sa-text1);margin:0 0 4px">Catálogo de Regras</h1>
            <p style="font-size:14px;color:var(--sa-text3);margin:0">Regras de negócio disponíveis para as empresas ativarem e configurarem</p>
        </div>
        <button type="button" @click="abrirModal(null)"
                style="display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;background:var(--sa-primary);color:#fff;transition:filter 200ms"
                onmouseover="this.style.filter='brightness(1.1)'" onmouseout="this.style.filter='none'">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nova Regra
        </button>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:14px">
        <template x-for="regra in regras" :key="regra.id">
            <div style="background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.05);display:flex;flex-direction:column;gap:10px">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px">
                    <div>
                        <div style="font-size:11px;font-weight:700;color:var(--sa-secondary);text-transform:uppercase;letter-spacing:.5px" x-text="regra.categoria"></div>
                        <div style="font-family:'Poppins',sans-serif;font-size:15px;font-weight:700;color:var(--sa-text1);margin-top:2px" x-text="regra.nome"></div>
                        <div style="font-size:11px;color:var(--sa-text3);font-family:monospace" x-text="regra.codigo"></div>
                    </div>
                    <span x-show="regra.ativo" style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(16,185,129,.12);color:#059669;flex-shrink:0">
                        <span style="width:5px;height:5px;border-radius:50%;background:currentColor"></span>Ativa
                    </span>
                    <span x-show="!regra.ativo" style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:rgba(107,114,128,.12);color:#6b7280;flex-shrink:0">
                        <span style="width:5px;height:5px;border-radius:50%;background:currentColor"></span>Inativa
                    </span>
                </div>
                <p style="font-size:13px;color:var(--sa-text2);margin:0;line-height:1.5;flex:1" x-text="regra.descricao"></p>
                <div style="font-size:12px;color:var(--sa-text3)">
                    <span x-text="regra.params_schema.length + ' parâmetro(s)'"></span> ·
                    <span x-text="regra.empresas_usando + ' empresa(s) usando'"></span>
                </div>
                <div style="display:flex;gap:6px;padding-top:6px;border-top:1px solid var(--sa-border)">
                    <button type="button" @click="abrirModal(regra)"
                            style="flex:1;padding:8px 12px;border-radius:7px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;font-size:13px;font-weight:600;color:var(--sa-text2);transition:all 150ms"
                            onmouseover="this.style.borderColor='var(--sa-primary)';this.style.color='var(--sa-text1)'"
                            onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text2)'">Editar</button>
                    <button type="button" @click="excluir(regra)"
                            style="width:36px;padding:8px 0;border-radius:7px;border:1px solid var(--sa-border);background:transparent;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;color:var(--sa-text3);transition:all 150ms"
                            onmouseover="this.style.borderColor='#ef4444';this.style.color='#ef4444'"
                            onmouseout="this.style.borderColor='var(--sa-border)';this.style.color='var(--sa-text3)'">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                    </button>
                </div>
            </div>
        </template>
    </div>

    {{-- Modal --}}
    <div x-show="modalAberto" x-cloak @keydown.escape.window="modalAberto = false"
         style="position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:1000;padding:20px"
         @click.self="modalAberto = false">
        <div style="background:var(--sa-surface);border-radius:16px;width:min(640px, calc(100vw - 32px));max-height:90vh;overflow-y:auto;box-shadow:0 24px 64px rgba(0,0,0,.2);animation:sa-modal-in 250ms ease;padding:24px 28px">
            <h3 style="font-family:'Poppins',sans-serif;font-size:17px;font-weight:700;color:var(--sa-text1);margin:0 0 16px" x-text="editando ? 'Editar Regra' : 'Nova Regra'"></h3>
            <div style="display:flex;flex-direction:column;gap:14px">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Código <span style="color:var(--sa-secondary)">*</span></label>
                        <input type="text" x-model="form.codigo" :disabled="editando !== null" placeholder="ex: cancelamento_antecedencia"
                               style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:monospace;color:var(--sa-text1);background:var(--sa-surface);outline:none;box-sizing:border-box">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Categoria <span style="color:var(--sa-secondary)">*</span></label>
                        <input type="text" x-model="form.categoria"
                               style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;box-sizing:border-box">
                    </div>
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Nome <span style="color:var(--sa-secondary)">*</span></label>
                    <input type="text" x-model="form.nome"
                           style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Descrição</label>
                    <textarea x-model="form.descricao" rows="2"
                              style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;color:var(--sa-text1);background:var(--sa-surface);outline:none;box-sizing:border-box;resize:vertical"></textarea>
                </div>
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                        <label style="font-size:13px;font-weight:600;color:var(--sa-text1)">Parâmetros</label>
                        <button type="button" @click="form.params_schema.push({ key: '', label: '', type: 'number', min: null, max: null })"
                                style="font-size:12px;font-weight:600;color:var(--sa-secondary);background:none;border:none;cursor:pointer">+ adicionar</button>
                    </div>
                    <template x-for="(campo, i) in form.params_schema" :key="i">
                        <div style="display:grid;grid-template-columns:1fr 1.4fr auto auto auto auto;gap:6px;margin-bottom:6px;align-items:center">
                            <input type="text" x-model="campo.key" placeholder="chave" style="padding:8px 10px;border:1.5px solid var(--sa-border);border-radius:7px;font-size:12px;font-family:monospace;outline:none;box-sizing:border-box;background:var(--sa-surface);color:var(--sa-text1)">
                            <input type="text" x-model="campo.label" placeholder="rótulo" style="padding:8px 10px;border:1.5px solid var(--sa-border);border-radius:7px;font-size:12px;outline:none;box-sizing:border-box;background:var(--sa-surface);color:var(--sa-text1)">
                            <select x-model="campo.type" style="padding:8px;border:1.5px solid var(--sa-border);border-radius:7px;font-size:12px;background:var(--sa-surface);color:var(--sa-text1)">
                                <option value="number">número</option>
                                <option value="boolean">sim/não</option>
                                <option value="text">texto</option>
                            </select>
                            <input type="number" x-model.number="campo.min" placeholder="min" style="width:58px;padding:8px;border:1.5px solid var(--sa-border);border-radius:7px;font-size:12px;background:var(--sa-surface);color:var(--sa-text1)">
                            <input type="number" x-model.number="campo.max" placeholder="max" style="width:58px;padding:8px;border:1.5px solid var(--sa-border);border-radius:7px;font-size:12px;background:var(--sa-surface);color:var(--sa-text1)">
                            <button type="button" @click="form.params_schema.splice(i, 1)" style="background:none;border:none;cursor:pointer;color:var(--sa-text3)">✕</button>
                        </div>
                    </template>
                </div>
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px">Valores padrão (JSON)</label>
                    <textarea x-model="defaultsJson" rows="2"
                              style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:13px;font-family:monospace;color:var(--sa-text1);background:var(--sa-surface);outline:none;box-sizing:border-box;resize:vertical"></textarea>
                </div>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" x-model="form.ativo">
                    <span style="font-size:13px;color:var(--sa-text1);font-weight:600">Regra disponível para as empresas</span>
                </label>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;padding-top:14px;border-top:1px solid var(--sa-border)">
                <button type="button" @click="modalAberto = false"
                        style="padding:10px 18px;border-radius:8px;border:1.5px solid var(--sa-border);background:transparent;color:var(--sa-text2);font-size:14px;font-weight:600;cursor:pointer">Cancelar</button>
                <button type="button" @click="salvar()"
                        style="padding:10px 18px;border-radius:8px;border:none;cursor:pointer;font-size:14px;font-weight:600;background:var(--sa-primary);color:#fff">Salvar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function regrasAdminApp() {
    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;
    const headers = () => ({ 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() });
    const blank = () => ({ codigo: '', nome: '', descricao: '', categoria: 'Geral', ativo: true, params_schema: [], params_default: {} });

    return {
        regras: @json($regrasJson),
        modalAberto: false,
        editando: null,
        form: blank(),
        defaultsJson: '{}',

        abrirModal(regra) {
            this.editando = regra;
            this.form = regra
                ? JSON.parse(JSON.stringify({ ...regra }))
                : blank();
            this.defaultsJson = JSON.stringify(this.form.params_default || {}, null, 0);
            this.modalAberto = true;
        },

        async salvar() {
            let defaults;
            try { defaults = JSON.parse(this.defaultsJson || '{}'); }
            catch { return Swal.fire({ title: 'JSON inválido', text: 'Corrija os valores padrão.', icon: 'error', confirmButtonColor: '#1a1a1a' }); }

            const payload = { ...this.form, params_default: defaults };
            const url = this.editando ? '/admin/regras/' + this.editando.id : '/admin/regras';

            try {
                const r = await fetch(url, { method: this.editando ? 'PUT' : 'POST', headers: headers(), body: JSON.stringify(payload) });
                const data = await r.json();
                if (!r.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Erro ao salvar.');
                if (this.editando) {
                    this.regras = this.regras.map(rg => rg.id === data.id ? data : rg);
                } else {
                    this.regras.push(data);
                }
                this.modalAberto = false;
                Swal.fire({ title: 'Regra salva!', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
            } catch (e) {
                Swal.fire({ title: 'Erro', text: e.message, icon: 'error', confirmButtonColor: '#1a1a1a' });
            }
        },

        excluir(regra) {
            Swal.fire({
                title: 'Excluir ' + regra.nome + '?',
                text: 'As empresas que usam esta regra perderão a configuração. Esta ação não pode ser desfeita.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: 'transparent',
                customClass: { cancelButton: 'swal-cancel-muted' },
            }).then(async r => {
                if (!r.isConfirmed) return;
                const resp = await fetch('/admin/regras/' + regra.id, { method: 'DELETE', headers: headers() });
                if (resp.ok) {
                    this.regras = this.regras.filter(rg => rg.id !== regra.id);
                    Swal.fire({ title: 'Regra excluída', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                }
            });
        },
    };
}
</script>
@endpush
@endsection
