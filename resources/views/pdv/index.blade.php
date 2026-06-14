@extends('layouts.app')
@section('title', 'PDV')

@push('styles')
<style>
    .pdv-grid { display:grid; grid-template-columns:1fr 360px; gap:0; flex:1; overflow:hidden; }
    .pdv-catalog { display:flex; flex-direction:column; gap:12px; overflow:hidden; padding-right:20px; }
    .pdv-items { flex:1; overflow-y:auto; display:grid; grid-template-columns:repeat(3,1fr); gap:10px; align-content:start; }
    .pdv-item {
        background:var(--sa-surface); border:1.5px solid var(--sa-border); border-radius:12px;
        padding:0; cursor:pointer; text-align:left; display:flex; flex-direction:column;
        overflow:hidden; transition:all 160ms; font-family:var(--sa-font-body); position:relative;
    }
    .pdv-item:hover:not(:disabled) { border-color:var(--sa-primary); }
    .pdv-item.in-cart { background:var(--sa-surface2); border-color:var(--sa-primary); }
    .pdv-item:disabled { opacity:.5; cursor:not-allowed; }
    .pdv-item__header {
        height:88px; display:flex; align-items:center; justify-content:center; flex-shrink:0;
        overflow:hidden; position:relative;
    }
    .pdv-item__photo { width:100%; height:100%; object-fit:cover; display:block; }
    .pdv-item__photo-fallback { width:100%; height:100%; }
    .pdv-item__body {
        padding:14px 12px 12px; display:flex; flex-direction:column; gap:2px; margin-top:2px;
    }
    .pdv-item__name { font-size:12px; font-weight:700; color:var(--sa-text1); line-height:1.25; }
    .pdv-item__meta { font-size:10px; color:var(--sa-text3); line-height:1.3; }
    .pdv-item__price {
        font-size:13px; font-weight:800; color:var(--sa-secondary);
        font-family:var(--sa-font-heading); margin-top:4px;
    }
    .pdv-item__check {
        position:absolute; top:8px; right:8px; width:20px; height:20px; border-radius:50%;
        background:var(--sa-primary); display:flex; align-items:center; justify-content:center;
    }
    .pdv-cart { display:flex; flex-direction:column; background:var(--sa-surface); border:1px solid var(--sa-border); border-radius:16px; overflow:hidden; }
    .pdv-search {
        width:100%; padding:9px 12px 9px 34px; font-size:13px; border:1px solid var(--sa-border);
        border-radius:8px; background:var(--sa-surface); color:var(--sa-text1);
        font-family:var(--sa-font-body); outline:none; box-sizing:border-box; transition:border-color 180ms, box-shadow 180ms;
    }
    .pdv-search:focus { border-color:var(--sa-primary); box-shadow:0 0 0 3px rgba(0,0,0,.06); }
    @media (max-width:1080px) { .pdv-grid { grid-template-columns:1fr; } .pdv-items { grid-template-columns:repeat(2,1fr); } }
    @media (max-width:768px) { .pdv-items { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<x-sa.page x-data="pdvApp()" style="display:flex;flex-direction:column;height:calc(100vh - 60px);overflow:hidden">
    <x-sa.app-header title="PDV — Ponto de Venda" subtitle="Registre vendas de produtos e serviços">
        <x-slot:actions>
            <x-sa.btn variant="secondary" size="sm" :href="route('pdv.exportar')"
                      :icon="'<svg width=\'14\' height=\'14\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><path d=\'M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4\'/><polyline points=\'7 10 12 15 17 10\'/><line x1=\'12\' y1=\'15\' x2=\'12\' y2=\'3\'/></svg>'">
                Exportar CSV
            </x-sa.btn>
        </x-slot:actions>
    </x-sa.app-header>

    <div class="pdv-grid" style="padding:16px 32px 24px">
        {{-- Catálogo --}}
        <div class="pdv-catalog">
            <div style="display:flex;gap:10px">
                <div style="position:relative;flex:1">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--sa-text3)" stroke-width="2" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);pointer-events:none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" x-model="search" placeholder="Buscar produto ou serviço..." class="pdv-search">
                </div>
                <div style="display:flex;background:var(--sa-surface2);border:1px solid var(--sa-border);border-radius:8px;overflow:hidden">
                    <button type="button" @click="tab = 'products'"
                            :style="'padding:9px 16px;border:none;border-right:1px solid var(--sa-border);cursor:pointer;font-size:13px;font-family:var(--sa-font-body);transition:all 150ms;background:' + (tab === 'products' ? 'var(--sa-primary)' : 'transparent') + ';color:' + (tab === 'products' ? '#fff' : 'var(--sa-text2)') + ';font-weight:' + (tab === 'products' ? '600' : '400')">
                        Produtos
                    </button>
                    <button type="button" @click="tab = 'services'"
                            :style="'padding:9px 16px;border:none;cursor:pointer;font-size:13px;font-family:var(--sa-font-body);transition:all 150ms;background:' + (tab === 'services' ? 'var(--sa-primary)' : 'transparent') + ';color:' + (tab === 'services' ? '#fff' : 'var(--sa-text2)') + ';font-weight:' + (tab === 'services' ? '600' : '400')">
                        Serviços
                    </button>
                </div>
            </div>

            <div x-show="tab === 'products' && !search.trim()" style="font-size:12px;color:var(--sa-text3);margin-top:-4px">
                Exibindo os 9 mais vendidos — busque para ver todos os produtos
            </div>

            <div class="pdv-items">
                <template x-for="item in filteredItems" :key="item.key">
                    <button type="button" class="pdv-item" :class="{ 'in-cart': cartQty(item.key) > 0 }"
                            :disabled="item.stock === 0"
                            @click="addItem(item)">
                        <div class="pdv-item__header"
                             :style="item.type === 'product' && !item.photoUrl ? 'background:color-mix(in srgb,var(--sa-secondary) 15%,transparent)' : (item.type === 'service' ? 'background:' + item.color + '18' : '')">
                            <template x-if="item.type === 'product' && item.photoUrl">
                                <img :src="item.photoUrl" :alt="item.name" class="pdv-item__photo">
                            </template>
                            <template x-if="item.type === 'service' && item.iconUrl">
                                <img :src="item.iconUrl" width="28" height="28" alt="" style="object-fit:contain;opacity:.85">
                            </template>
                        </div>
                        <div class="pdv-item__body">
                            <div class="pdv-item__name" x-text="item.name"></div>
                            <div x-show="item.type !== 'service'" class="pdv-item__meta"
                                 :style="item.stock < 5 ? 'color:#ef4444' : ''"
                                 x-text="item.stock + ' em estoque'"></div>
                            <div class="pdv-item__meta" x-show="item.type === 'service'" x-text="item.duration + 'min'"></div>
                            <div class="pdv-item__price" x-text="formatCurrency(item.price)"></div>
                        </div>
                        <div x-show="cartQty(item.key) > 0" class="pdv-item__check">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
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
                    <div style="font-size:12px;color:var(--sa-text3)" x-text="cartCountLabel"></div>
                </div>
                <button type="button" x-show="cart.length > 0" @click="clearCart()"
                        style="display:inline-flex;align-items:center;gap:5px;font-size:13px;color:var(--sa-text2);background:none;border:none;cursor:pointer;font-weight:600;font-family:var(--sa-font-body);padding:7px 14px;border-radius:8px;transition:color 150ms"
                        onmouseover="this.style.color='var(--sa-text1)'" onmouseout="this.style.color='var(--sa-text2)'">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                    Limpar
                </button>
            </div>

            <div style="padding:10px 14px;border-bottom:1px solid var(--sa-border)">
                <select x-model="clientId"
                        :style="'width:100%;font-size:13px;padding:8px 10px;border:1px solid var(--sa-border);border-radius:8px;background:var(--sa-surface2);font-family:var(--sa-font-body);outline:none;color:' + (clientId ? 'var(--sa-text1)' : 'var(--sa-text3)')">
                    <option value="">Selecionar cliente (opcional)</option>
                    @foreach($clientes as $cliente)
                    <option value="{{ $cliente->id }}">{{ $cliente->name }}</option>
                    @endforeach
                </select>
            </div>

            <div style="flex:1;overflow-y:auto;min-height:120px">
                <template x-if="cart.length === 0">
                    <div style="padding:40px;text-align:center;color:var(--sa-text3);font-size:13px">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:block;margin:0 auto 12px;opacity:.25"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                        Nenhum item no carrinho
                    </div>
                </template>
                <template x-for="item in cart" :key="item.key">
                    <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid var(--sa-border)">
                        <div style="flex:1;min-width:0">
                            <div style="font-size:13px;font-weight:600;color:var(--sa-text1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap" x-text="item.name"></div>
                            <div style="font-size:11px;color:var(--sa-text3)" x-text="formatCurrency(item.price) + ' cada'"></div>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0">
                            <button type="button" @click="adjustQty(item.key, -1)" style="width:24px;height:24px;border-radius:6px;border:1px solid var(--sa-border);background:var(--sa-surface2);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;color:var(--sa-text1);font-weight:700">-</button>
                            <span style="font-size:14px;font-weight:700;color:var(--sa-text1);width:24px;text-align:center" x-text="item.qty"></span>
                            <button type="button" @click="adjustQty(item.key, 1)" style="width:24px;height:24px;border-radius:6px;border:1px solid var(--sa-border);background:var(--sa-surface2);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;color:var(--sa-text1);font-weight:700">+</button>
                        </div>
                        <div style="font-size:14px;font-weight:700;color:var(--sa-secondary);width:64px;text-align:right;flex-shrink:0" x-text="formatCurrency(item.price * item.qty)"></div>
                        <button type="button" @click="removeItem(item.key)" style="background:none;border:none;cursor:pointer;color:var(--sa-text3);padding:2px;border-radius:4px;display:flex;align-items:center;flex-shrink:0">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                </template>
            </div>

            <div style="border-top:1px solid var(--sa-border);padding:12px 14px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
                    <span style="font-size:13px;color:var(--sa-text2);flex:1">Desconto (%)</span>
                    <input type="number" x-model.number="discount" min="0" max="100"
                           style="width:64px;padding:5px 8px;font-size:13px;border:1px solid var(--sa-border);border-radius:7px;background:var(--sa-surface);color:var(--sa-text1);text-align:center;font-family:var(--sa-font-body);outline:none">
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                    <span style="font-size:13px;color:var(--sa-text2)">Subtotal</span>
                    <span style="font-size:13px;font-weight:500;color:var(--sa-text1)" x-text="formatCurrency(subtotal)"></span>
                </div>
                <div x-show="discount > 0" style="display:flex;justify-content:space-between;margin-bottom:4px">
                    <span style="font-size:13px;color:var(--sa-text2)" x-text="'Desconto (' + discount + '%)'"></span>
                    <span style="font-size:13px;font-weight:500;color:#ef4444" x-text="'- ' + formatCurrency(discountAmt)"></span>
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
                                :style="'padding:7px 4px;border-radius:8px;border:1.5px solid ' + (method === m.id ? m.color : 'var(--sa-border)') + ';background:' + (method === m.id ? m.color + '12' : 'transparent') + ';color:' + (method === m.id ? m.color : 'var(--sa-text2)') + ';font-weight:' + (method === m.id ? '700' : '500') + ';cursor:pointer;font-size:11px;font-family:var(--sa-font-body);transition:all 150ms'"
                                x-text="m.label"></button>
                    </template>
                </div>
                <div x-show="method === 'cash'" style="margin-top:10px;display:flex;align-items:center;gap:8px">
                    <span style="font-size:12px;color:var(--sa-text3);white-space:nowrap">Valor recebido:</span>
                    <input type="number" x-model="cashGiven" placeholder="0,00"
                           style="flex:1;padding:6px 10px;font-size:13px;border:1px solid var(--sa-border);border-radius:7px;background:var(--sa-surface);color:var(--sa-text1);font-family:var(--sa-font-body);outline:none">
                    <span x-show="parseFloat(cashGiven) >= total && total > 0" style="font-size:12px;font-weight:700;color:#10b981;white-space:nowrap" x-text="'Troco: ' + formatCurrency(cashChange)"></span>
                </div>
            </div>

            <div style="padding:10px 14px 16px">
                <template x-if="paid">
                    <div style="text-align:center;padding:16px;background:rgba(16,185,129,.08);border-radius:12px;border:1px solid rgba(16,185,129,.2)">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" style="display:block;margin:0 auto 8px"><polyline points="20 6 9 17 4 12"/></svg>
                        <div style="font-size:14px;font-weight:700;color:#059669">Venda Finalizada!</div>
                        <div x-show="method === 'cash' && change > 0" style="font-size:13px;color:var(--sa-text2);margin-top:4px" x-text="'Troco: ' + formatCurrency(change)"></div>
                        <button type="button" @click="clearCart()"
                                style="margin-top:12px;width:100%;padding:9px 18px;border-radius:8px;border:1.5px solid var(--sa-primary);background:transparent;color:var(--sa-primary);font-weight:600;cursor:pointer;font-family:var(--sa-font-body);font-size:13px">
                            Nova Venda
                        </button>
                    </div>
                </template>
                <template x-if="!paid">
                    <button type="button" @click="openPaymentModal()" :disabled="cart.length === 0"
                            class="sa-btn sa-btn--primary sa-btn--lg"
                            :style="cart.length === 0 ? 'width:100%;opacity:.45;cursor:not-allowed' : 'width:100%'">
                        <span x-text="'Finalizar Venda · ' + formatCurrency(total)"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    {{-- Modal de pagamento --}}
    <x-sa.modal open="paymentOpen" size="sm">
        <x-slot:title><span x-text="paymentTitle"></span></x-slot:title>
        <x-slot:subtitle><span x-text="'Total: ' + formatCurrency(total)"></span></x-slot:subtitle>

        <div x-show="method === 'pix'" style="display:flex;flex-direction:column;align-items:center;gap:16px">
            <div x-show="paymentLoading" style="padding:40px;color:var(--sa-text3);font-size:13px">Gerando QR Code...</div>
            <template x-if="!paymentLoading && pixData && pixData.configured">
                <div style="display:flex;flex-direction:column;align-items:center;gap:14px;width:100%">
                    <div style="padding:12px;background:#fff;border-radius:12px;border:1px solid var(--sa-border);display:flex;align-items:center;justify-content:center">
                        <div x-html="pixData.qr_code" style="width:220px;height:220px;display:flex;align-items:center;justify-content:center"></div>
                    </div>
                    <p style="font-size:13px;color:var(--sa-text2);text-align:center;margin:0;line-height:1.5">Peça ao cliente escanear o QR Code ou copie o código Pix abaixo.</p>
                    <div style="width:100%;position:relative">
                        <input type="text" readonly :value="pixData.copy_paste"
                               style="width:100%;padding:10px 42px 10px 12px;font-size:11px;border:1.5px solid var(--sa-border);border-radius:8px;background:var(--sa-surface2);color:var(--sa-text2);font-family:monospace;outline:none;box-sizing:border-box">
                        <button type="button" @click="copyPixCode()"
                                style="position:absolute;right:6px;top:50%;transform:translateY(-50%);width:30px;height:30px;border-radius:7px;border:1px solid var(--sa-border);background:var(--sa-surface);cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--sa-text3)">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                        </button>
                    </div>
                </div>
            </template>
            <div x-show="!paymentLoading && pixData && !pixData.configured" style="text-align:center;padding:12px 0">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" style="margin:0 auto 12px;display:block"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <p style="font-size:13px;color:var(--sa-text2);margin:0 0 14px;line-height:1.5" x-text="pixData?.message || 'Chave Pix não configurada.'"></p>
                <a :href="paymentConfig.empresa_config_url" style="font-size:13px;font-weight:600;color:var(--sa-primary);text-decoration:none">Configurar chave Pix →</a>
            </div>
        </div>

        <div x-show="method === 'credit' || method === 'debit'" style="text-align:center;padding:8px 0">
            <div style="width:64px;height:64px;border-radius:16px;margin:0 auto 16px;display:flex;align-items:center;justify-content:center"
                 :style="'background:' + (method === 'credit' ? '#6366f1' : '#f59e0b') + '18'">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" :stroke="method === 'credit' ? '#6366f1' : '#f59e0b'" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            </div>
            <div style="font-family:var(--sa-font-heading);font-size:28px;font-weight:800;color:var(--sa-text1);margin-bottom:8px" x-text="formatCurrency(total)"></div>
            <p style="font-size:13px;color:var(--sa-text2);margin:0 0 16px;line-height:1.5" x-text="method === 'credit' ? 'Peça ao cliente passar o cartão de crédito na maquininha.' : 'Peça ao cliente passar o cartão de débito na maquininha.'"></p>
            <div style="padding:12px 14px;border-radius:10px;background:var(--sa-surface2);border:1px solid var(--sa-border);font-size:12px;color:var(--sa-text3)">Aguardando confirmação do pagamento na maquininha</div>
        </div>

        <div x-show="method === 'cash'" style="display:flex;flex-direction:column;gap:14px">
            <div style="text-align:center;padding:8px 0 4px">
                <div style="font-size:12px;color:var(--sa-text3);margin-bottom:4px">Total a receber</div>
                <div style="font-family:var(--sa-font-heading);font-size:28px;font-weight:800;color:var(--sa-secondary)" x-text="formatCurrency(total)"></div>
            </div>
            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--sa-text1);margin-bottom:5px">Valor recebido</label>
                <input type="number" x-model="cashGiven" placeholder="0,00" step="0.01" min="0"
                       style="width:100%;padding:10px 13px;border:1.5px solid var(--sa-border);border-radius:8px;font-size:16px;font-weight:700;color:var(--sa-text1);background:var(--sa-surface);outline:none;box-sizing:border-box">
            </div>
            <div x-show="parseFloat(cashGiven) >= total && total > 0" style="display:flex;justify-content:space-between;padding:12px 14px;border-radius:10px;background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.2)">
                <span style="font-size:13px;font-weight:600;color:#059669">Troco</span>
                <span style="font-size:15px;font-weight:800;color:#059669" x-text="formatCurrency(cashChange)"></span>
            </div>
            <div x-show="cashGiven && parseFloat(cashGiven) < total" style="font-size:12px;color:#ef4444;text-align:center">
                Valor insuficiente — faltam <span x-text="formatCurrency(total - (parseFloat(cashGiven) || 0))"></span>
            </div>
        </div>

        <x-slot:footer>
            <x-sa.btn variant="secondary" size="sm" type="button" @click="closePaymentModal()">Cancelar</x-sa.btn>
            <x-sa.btn size="sm" type="button" @click="confirmPayment()" x-bind:disabled="paymentSubmitting || !canConfirmPayment()">
                <span x-text="paymentSubmitting ? 'Registrando...' : 'Confirmar pagamento'"></span>
            </x-sa.btn>
        </x-slot:footer>
    </x-sa.modal>
</x-sa.page>

@push('scripts')
<script>
function pdvApp() {
    const products = @json($produtosJs);
    const services = @json($servicosJs);
    const paymentConfig = @json($paymentConfig);
    const pixUrl = @json(route('pdv.pagamento.pix'));
    const storeUrl = @json(route('pdv.store'));

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
        paymentOpen: false,
        paymentLoading: false,
        paymentSubmitting: false,
        pixData: null,
        paymentConfig,
        methods: [
            { id: 'pix', label: 'Pix', color: '#10b981' },
            { id: 'credit', label: 'Crédito', color: '#6366f1' },
            { id: 'debit', label: 'Débito', color: '#f59e0b' },
            { id: 'cash', label: 'Dinheiro', color: '#1a1a1a' },
        ],
        get filteredItems() {
            if (this.tab === 'services') {
                const q = this.search.trim().toLowerCase();
                return q ? services.filter(i => i.name.toLowerCase().includes(q)) : services;
            }

            const q = this.search.trim().toLowerCase();
            if (q) {
                return products.filter(i => i.name.toLowerCase().includes(q));
            }

            return products
                .filter(i => i.featured)
                .sort((a, b) => a.featuredRank - b.featuredRank);
        },
        get subtotal() { return this.cart.reduce((s, c) => s + c.price * c.qty, 0); },
        get discountAmt() { return Math.round(this.subtotal * this.discount / 100 * 100) / 100; },
        get total() { return Math.max(this.subtotal - this.discountAmt, 0); },
        get cashChange() { return Math.max((parseFloat(this.cashGiven) || 0) - this.total, 0); },
        get cartCount() { return this.cart.reduce((s, c) => s + c.qty, 0); },
        get cartCountLabel() {
            const n = this.cartCount;
            return n + ' ite' + (n === 1 ? 'm' : 'ns');
        },
        get paymentTitle() {
            return ({ pix: 'Pagamento via Pix', credit: 'Cartão de Crédito', debit: 'Cartão de Débito', cash: 'Pagamento em Dinheiro' })[this.method] || 'Pagamento';
        },
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
        clearCart() { this.cart = []; this.discount = 0; this.clientId = ''; this.cashGiven = ''; this.paid = false; this.change = 0; this.paymentOpen = false; this.pixData = null; },
        canConfirmPayment() {
            if (this.method === 'cash') return (parseFloat(this.cashGiven) || 0) >= this.total && this.total > 0;
            if (this.method === 'pix') return this.pixData?.configured === true;
            return true;
        },
        openPaymentModal() {
            if (this.cart.length === 0) {
                return Swal.fire({ title: 'Atenção', text: 'Adicione itens ao carrinho.', icon: 'warning', confirmButtonText: 'OK', confirmButtonColor: '#1a1a1a' });
            }
            this.paymentOpen = true;
            this.pixData = null;
            if (this.method === 'pix') this.loadPixQr();
        },
        closePaymentModal() {
            if (this.paymentSubmitting) return;
            this.paymentOpen = false;
            this.paymentLoading = false;
        },
        loadPixQr() {
            this.paymentLoading = true;
            fetch(`${pixUrl}?total=${encodeURIComponent(this.total)}`, { headers: { Accept: 'application/json' } })
                .then(r => r.ok ? r.json() : Promise.reject(r))
                .then(data => { this.pixData = data; })
                .catch(() => { this.pixData = { configured: false, message: 'Não foi possível gerar o QR Code Pix.' }; })
                .finally(() => { this.paymentLoading = false; });
        },
        copyPixCode() {
            if (!this.pixData?.copy_paste) return;
            navigator.clipboard.writeText(this.pixData.copy_paste).then(() => {
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Código Pix copiado!', showConfirmButton: false, timer: 2000 });
            });
        },
        confirmPayment() {
            if (!this.canConfirmPayment() || this.paymentSubmitting) return;
            this.paymentSubmitting = true;
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            fetch(storeUrl, {
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
                this.paymentOpen = false;
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Venda finalizada!', showConfirmButton: false, timer: 2500 });
            })
            .catch(() => {
                Swal.fire({ title: 'Erro', text: 'Não foi possível registrar a venda.', icon: 'error', confirmButtonText: 'OK', confirmButtonColor: '#1a1a1a' });
            })
            .finally(() => { this.paymentSubmitting = false; });
        },
    };
}
</script>
@endpush
@endsection
