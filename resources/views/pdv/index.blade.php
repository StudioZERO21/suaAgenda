@extends('layouts.app')
@section('title', 'PDV')

@push('styles')
<style>
    .pdv-grid { display:grid; grid-template-columns:1fr 360px; gap:0; flex:1; overflow:hidden; }
    .pdv-catalog { display:flex; flex-direction:column; gap:12px; overflow:hidden; padding-right:20px; }
    .pdv-items { flex:1; overflow-y:auto; display:grid; grid-template-columns:repeat(3,1fr); gap:10px; align-content:start; }
    .pdv-item { background:var(--sa-surface); border:1.5px solid var(--sa-border); border-radius:12px; padding:14px; cursor:pointer; text-align:left; display:flex; flex-direction:column; gap:6px; transition:all 160ms; font-family:var(--sa-font-body); position:relative; }
    .pdv-item:hover { border-color:var(--sa-primary); }
    .pdv-item.in-cart { background:color-mix(in srgb,var(--sa-primary) 8%,transparent); border-color:var(--sa-primary); }
    .pdv-item:disabled { opacity:.5; cursor:not-allowed; }
    .pdv-cart { display:flex; flex-direction:column; background:var(--sa-surface); border:1px solid var(--sa-border); border-radius:16px; overflow:hidden; }
    @media (max-width:1080px) { .pdv-grid { grid-template-columns:1fr; } .pdv-items { grid-template-columns:repeat(2,1fr); } }
    @media (max-width:768px) { .pdv-items { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<x-sa.page x-data="pdvApp()" style="display:flex;flex-direction:column;height:calc(100vh - 60px);overflow:hidden">
    <x-sa.app-header title="PDV — Ponto de Venda" subtitle="Registre vendas de produtos e serviços" />

    <div class="pdv-grid" style="padding:16px 32px 24px">
        {{-- Catálogo --}}
        <div class="pdv-catalog">
            <div style="display:flex;gap:10px">
                <div style="position:relative;flex:1">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2" style="position:absolute;left:11px;top:50%;transform:translateY(-50%)"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input x-model="search" placeholder="Buscar produto ou serviço..." class="sa-search-input">
                </div>
                <div style="display:flex;background:var(--sa-surface2);border:1px solid var(--sa-border);border-radius:8px;overflow:hidden">
                    <button type="button" @click="tab = 'products'" :style="tab === 'products' ? 'background:var(--sa-primary);color:#fff;font-weight:600' : ''" style="padding:9px 16px;border:none;border-right:1px solid var(--sa-border);background:transparent;color:var(--sa-text2);cursor:pointer;font-size:13px;font-family:var(--sa-font-body)">Produtos</button>
                    <button type="button" @click="tab = 'services'" :style="tab === 'services' ? 'background:var(--sa-primary);color:#fff;font-weight:600' : ''" style="padding:9px 16px;border:none;background:transparent;color:var(--sa-text2);cursor:pointer;font-size:13px;font-family:var(--sa-font-body)">Serviços</button>
                </div>
            </div>

            <div class="pdv-items">
                <template x-for="item in filteredItems" :key="item.key">
                    <button type="button" class="pdv-item" :class="{ 'in-cart': cartQty(item.key) > 0 }"
                            :disabled="item.stock === 0"
                            @click="addItem(item)">
                        <div style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center"
                             :style="'background:' + (item.type === 'service' ? item.color + '18' : 'color-mix(in srgb,var(--sa-secondary) 15%,transparent)')">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" :stroke="item.type === 'service' ? item.color : 'var(--sa-secondary)'" stroke-width="2">
                                <template x-if="item.type === 'service'">
                                    <g><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/></g>
                                </template>
                                <template x-if="item.type !== 'service'">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </template>
                            </svg>
                        </div>
                        <div style="font-size:13px;font-weight:700;color:var(--sa-text1);line-height:1.3" x-text="item.name"></div>
                        <div style="font-size:11px;color:var(--sa-text3)" x-show="item.type !== 'service'" x-text="item.stock + ' em estoque'"></div>
                        <div style="font-size:11px;color:var(--sa-text3)" x-show="item.type === 'service'" x-text="item.duration + 'min'"></div>
                        <div style="font-size:15px;font-weight:800;color:var(--sa-secondary);font-family:var(--sa-font-heading)" x-text="formatCurrency(item.price)"></div>
                        <div x-show="cartQty(item.key) > 0" style="position:absolute;top:8px;right:8px;width:20px;height:20px;border-radius:50%;background:var(--sa-primary);display:flex;align-items:center;justify-content:center">
                            <span style="font-size:11px;font-weight:800;color:#fff" x-text="cartQty(item.key)"></span>
                        </div>
                    </button>
                </template>
                <div x-show="filteredItems.length === 0" style="grid-column:1/-1;text-align:center;padding:40px;color:var(--sa-text3);font-size:13px">Nenhum item encontrado</div>
            </div>
        </div>

        {{-- Carrinho --}}
        <div class="pdv-cart">
            <div style="padding:16px 16px 12px;border-bottom:1px solid var(--sa-border);display:flex;justify-content:space-between;align-items:center">
                <div>
                    <div style="font-family:var(--sa-font-heading);font-size:15px;font-weight:700;color:var(--sa-text1)">Carrinho</div>
                    <div style="font-size:12px;color:var(--sa-text3)" x-text="cartCount + ' item(ns)'"></div>
                </div>
                <button type="button" x-show="cart.length > 0" @click="clearCart()" style="font-size:12px;color:var(--sa-text3);background:none;border:none;cursor:pointer;font-weight:600">Limpar</button>
            </div>

            <div style="padding:10px 14px;border-bottom:1px solid var(--sa-border)">
                <select x-model="clientId" style="width:100%;font-size:13px;padding:8px 10px;border:1px solid var(--sa-border);border-radius:8px;background:var(--sa-surface2);color:var(--sa-text1);font-family:var(--sa-font-body);outline:none">
                    <option value="">Selecionar cliente (opcional)</option>
                    @foreach($clientes as $cliente)
                    <option value="{{ $cliente->id }}">{{ $cliente->name }}</option>
                    @endforeach
                </select>
            </div>

            <div style="flex:1;overflow-y:auto;min-height:120px">
                <template x-if="cart.length === 0">
                    <div style="padding:40px;text-align:center;color:var(--sa-text3);font-size:13px">Nenhum item no carrinho</div>
                </template>
                <template x-for="item in cart" :key="item.key">
                    <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid var(--sa-border)">
                        <div style="flex:1;min-width:0">
                            <div style="font-size:13px;font-weight:600;color:var(--sa-text1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap" x-text="item.name"></div>
                            <div style="font-size:11px;color:var(--sa-text3)" x-text="formatCurrency(item.price) + ' cada'"></div>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px">
                            <button type="button" @click="adjustQty(item.key, -1)" style="width:24px;height:24px;border-radius:6px;border:1px solid var(--sa-border);background:var(--sa-surface2);cursor:pointer;font-size:16px;font-weight:700">-</button>
                            <span style="font-size:14px;font-weight:700;width:24px;text-align:center" x-text="item.qty"></span>
                            <button type="button" @click="adjustQty(item.key, 1)" style="width:24px;height:24px;border-radius:6px;border:1px solid var(--sa-border);background:var(--sa-surface2);cursor:pointer;font-size:16px;font-weight:700">+</button>
                        </div>
                        <div style="font-size:14px;font-weight:700;color:var(--sa-secondary);width:64px;text-align:right" x-text="formatCurrency(item.price * item.qty)"></div>
                        <button type="button" @click="removeItem(item.key)" style="background:none;border:none;cursor:pointer;color:var(--sa-text3);padding:2px">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                </template>
            </div>

            <div style="border-top:1px solid var(--sa-border);padding:12px 14px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
                    <span style="font-size:13px;color:var(--sa-text2);flex:1">Desconto (%)</span>
                    <input type="number" x-model.number="discount" min="0" max="100" style="width:64px;padding:5px 8px;font-size:13px;border:1px solid var(--sa-border);border-radius:7px;background:var(--sa-surface);text-align:center;outline:none">
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                    <span style="font-size:13px;color:var(--sa-text2)">Subtotal</span>
                    <span style="font-size:13px" x-text="formatCurrency(subtotal)"></span>
                </div>
                <div x-show="discount > 0" style="display:flex;justify-content:space-between;margin-bottom:4px">
                    <span style="font-size:13px;color:var(--sa-text2)" x-text="'Desconto (' + discount + '%)'"></span>
                    <span style="font-size:13px;color:#ef4444" x-text="'- ' + formatCurrency(discountAmt)"></span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span style="font-size:13px;font-weight:600;color:var(--sa-text2)">Total</span>
                    <span style="font-size:17px;font-weight:800;color:var(--sa-secondary);font-family:var(--sa-font-heading)" x-text="formatCurrency(total)"></span>
                </div>
            </div>

            <div style="border-top:1px solid var(--sa-border);padding:10px 14px">
                <div style="font-size:11px;font-weight:700;color:var(--sa-text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Forma de Pagamento</div>
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px">
                    <template x-for="m in methods" :key="m.id">
                        <button type="button" @click="method = m.id"
                                :style="'padding:7px 4px;border-radius:8px;border:1.5px solid ' + (method === m.id ? m.color : 'var(--sa-border)') + ';background:' + (method === m.id ? m.color + '12' : 'transparent') + ';color:' + (method === m.id ? m.color : 'var(--sa-text2)') + ';font-weight:' + (method === m.id ? '700' : '500')"
                                style="cursor:pointer;font-size:11px;font-family:var(--sa-font-body)" x-text="m.label"></button>
                    </template>
                </div>
                <div x-show="method === 'cash'" style="margin-top:10px;display:flex;align-items:center;gap:8px">
                    <span style="font-size:12px;color:var(--sa-text3);white-space:nowrap">Valor recebido:</span>
                    <input type="number" x-model="cashGiven" placeholder="0,00" style="flex:1;padding:6px 10px;font-size:13px;border:1px solid var(--sa-border);border-radius:7px;outline:none">
                    <span x-show="parseFloat(cashGiven) >= total && total > 0" style="font-size:12px;font-weight:700;color:#10b981;white-space:nowrap" x-text="'Troco: ' + formatCurrency(cashChange)"></span>
                </div>
            </div>

            <div style="padding:10px 14px 16px">
                <template x-if="paid">
                    <div style="text-align:center;padding:16px;background:rgba(16,185,129,.08);border-radius:12px;border:1px solid rgba(16,185,129,.2)">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" style="display:block;margin:0 auto 8px"><polyline points="20 6 9 17 4 12"/></svg>
                        <div style="font-size:14px;font-weight:700;color:#059669">Venda Finalizada!</div>
                        <div x-show="method === 'cash' && change > 0" style="font-size:13px;color:var(--sa-text2);margin-top:4px" x-text="'Troco: ' + formatCurrency(change)"></div>
                        <button type="button" @click="clearCart()" style="margin-top:12px;width:100%;padding:9px;border-radius:8px;border:1.5px solid var(--sa-primary);background:transparent;color:var(--sa-primary);font-weight:600;cursor:pointer;font-family:var(--sa-font-body)">Nova Venda</button>
                    </div>
                </template>
                <template x-if="!paid">
                    <button type="button" @click="finalize()" :disabled="cart.length === 0"
                            style="width:100%;padding:12px;border-radius:8px;border:none;background:var(--sa-primary);color:#fff;font-weight:700;font-size:15px;cursor:pointer;font-family:var(--sa-font-body);opacity:1"
                            :style="cart.length === 0 ? 'opacity:.5;cursor:not-allowed' : ''">
                        <span x-text="'Finalizar Venda · ' + formatCurrency(total)"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>
</x-sa.page>

@push('scripts')
<script>
function pdvApp() {
    const products = @json($produtosJs);
    const services = @json($servicosJs);

    return {
        tab: 'products',
        search: '',
        cart: [],
        clientId: '',
        discount: 0,
        method: 'pix',
        cashGiven: '',
        paid: false,
        change: 0,
        methods: [
            { id: 'pix', label: 'Pix', color: '#10b981' },
            { id: 'credit', label: 'Crédito', color: '#6366f1' },
            { id: 'debit', label: 'Débito', color: '#f59e0b' },
            { id: 'cash', label: 'Dinheiro', color: '#1a1a1a' },
        ],
        get filteredItems() {
            const items = this.tab === 'products' ? products : services;
            const q = this.search.toLowerCase();
            return q ? items.filter(i => i.name.toLowerCase().includes(q)) : items;
        },
        get subtotal() { return this.cart.reduce((s, c) => s + c.price * c.qty, 0); },
        get discountAmt() { return Math.round(this.subtotal * this.discount / 100 * 100) / 100; },
        get total() { return Math.max(this.subtotal - this.discountAmt, 0); },
        get cashChange() { return Math.max((parseFloat(this.cashGiven) || 0) - this.total, 0); },
        get cartCount() { return this.cart.reduce((s, c) => s + c.qty, 0); },
        cartQty(key) { const i = this.cart.find(c => c.key === key); return i ? i.qty : 0; },
        formatCurrency(v) { return 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2 }); },
        addItem(item) {
            const existing = this.cart.find(c => c.key === item.key);
            if (existing) existing.qty++;
            else this.cart.push({ ...item, qty: 1 });
            this.paid = false;
        },
        adjustQty(key, delta) {
            this.cart = this.cart.map(c => c.key === key ? { ...c, qty: Math.max(0, c.qty + delta) } : c).filter(c => c.qty > 0);
        },
        removeItem(key) { this.cart = this.cart.filter(c => c.key !== key); },
        clearCart() { this.cart = []; this.discount = 0; this.clientId = ''; this.cashGiven = ''; this.paid = false; this.change = 0; },
        finalize() {
            if (this.cart.length === 0) return Swal.fire({ title: 'Atenção', text: 'Adicione itens ao carrinho.', icon: 'warning', confirmButtonText: 'OK', confirmButtonColor: '#1a1a1a' });
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            fetch('/pdv/venda', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({
                    cliente_id: this.clientId || null,
                    items: this.cart.map(i => ({ id: i.id, type: i.type, name: i.name, price: i.price, qty: i.qty })),
                    subtotal: this.subtotal,
                    desconto: this.discountAmt,
                    total: this.total,
                    metodo_pagamento: this.method,
                }),
            })
            .then(r => r.ok ? r.json() : Promise.reject(r))
            .then(() => {
                if (this.method === 'cash') this.change = this.cashChange;
                this.paid = true;
            })
            .catch(() => {
                Swal.fire({ title: 'Erro', text: 'Não foi possível registrar a venda.', icon: 'error', confirmButtonText: 'OK', confirmButtonColor: '#1a1a1a' });
            });
        },
    };
}
</script>
@endpush
@endsection
