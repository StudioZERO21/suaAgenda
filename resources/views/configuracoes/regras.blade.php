@extends('layouts.app')
@section('title', 'Regras do Estabelecimento')

@section('content')
<x-sa.page x-data="regrasEmpresaApp()">
    <x-sa.app-header
        title="Regras do Estabelecimento"
        subtitle="Ative e configure as políticas que valem para o seu negócio — ex: cancelamento, sinal e no-show"
    />

    <x-sa.body padding="20px 32px 40px">
        <div style="max-width:860px;display:flex;flex-direction:column;gap:14px">
            <template x-for="regra in regras" :key="regra.codigo">
                <div style="background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.05)">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap">
                        <div style="flex:1;min-width:220px">
                            <div style="font-size:11px;font-weight:700;color:var(--sa-secondary);text-transform:uppercase;letter-spacing:.5px" x-text="regra.categoria"></div>
                            <div style="font-family:var(--sa-font-heading);font-size:15px;font-weight:700;color:var(--sa-text1);margin-top:2px" x-text="regra.nome"></div>
                            <p style="font-size:13px;color:var(--sa-text3);margin:6px 0 0;line-height:1.5" x-text="regra.descricao"></p>
                        </div>
                        <button type="button" @click="regra.ativo = !regra.ativo; salvar(regra)"
                                :style="'width:44px;height:24px;border-radius:20px;border:none;cursor:pointer;position:relative;transition:background 200ms;flex-shrink:0;background:' + (regra.ativo ? '#10b981' : 'var(--sa-border2)')">
                            <span :style="'position:absolute;top:3px;width:18px;height:18px;border-radius:50%;background:#fff;transition:left 200ms;left:' + (regra.ativo ? '23px' : '3px')"></span>
                        </button>
                    </div>

                    <div x-show="regra.ativo" x-cloak style="margin-top:14px;padding-top:14px;border-top:1px solid var(--sa-border)">
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px">
                            <template x-for="campo in regra.params_schema" :key="regra.codigo + '-' + campo.key">
                                <div>
                                    <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);letter-spacing:.2px;margin-bottom:5px" x-text="campo.label"></label>
                                    <template x-if="campo.type === 'number'">
                                        <input type="number" :min="campo.min" :max="campo.max"
                                               x-model.number="regra.params[campo.key]"
                                               @change="salvar(regra)"
                                               style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:var(--sa-font-body);color:var(--sa-text1);background:var(--sa-surface);outline:none;box-sizing:border-box;transition:border-color 180ms"
                                               onfocus="this.style.borderColor='var(--sa-primary)';this.style.outline='3px solid rgba(0,0,0,.06)'"
                                               onblur="this.style.borderColor='var(--sa-border)';this.style.outline='none'">
                                    </template>
                                    <template x-if="campo.type === 'boolean'">
                                        <select x-model.boolean="regra.params[campo.key]" @change="salvar(regra)"
                                                style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:var(--sa-font-body);color:var(--sa-text1);background:var(--sa-surface);outline:none;box-sizing:border-box;appearance:none">
                                            <option :value="true">Sim</option>
                                            <option :value="false">Não</option>
                                        </select>
                                    </template>
                                    <template x-if="campo.type === 'text'">
                                        <input type="text" x-model="regra.params[campo.key]" @change="salvar(regra)"
                                               style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:14px;font-family:var(--sa-font-body);color:var(--sa-text1);background:var(--sa-surface);outline:none;box-sizing:border-box">
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="regras.length === 0">
                <div style="background:var(--sa-surface);border:1px solid var(--sa-border);border-radius:12px;padding:32px;text-align:center">
                    <p style="font-size:14px;color:var(--sa-text3);margin:0">Nenhuma regra disponível no momento.</p>
                </div>
            </template>
        </div>
    </x-sa.body>
</x-sa.page>

@push('scripts')
<script>
function regrasEmpresaApp() {
    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

    return {
        regras: @json($regrasJson),

        async salvar(regra) {
            try {
                const r = await fetch('/regras/' + regra.codigo, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
                    body: JSON.stringify({ ativo: regra.ativo, params: regra.params }),
                });
                const data = await r.json();
                if (!r.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Erro ao salvar.');
                Swal.fire({ title: 'Regra atualizada!', icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 1800 });
            } catch (e) {
                Swal.fire({ title: 'Erro', text: e.message, icon: 'error', confirmButtonColor: '#1a1a1a' });
            }
        },
    };
}
</script>
@endpush
@endsection
