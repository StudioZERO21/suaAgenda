<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Lancamento;
use App\Models\Produto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venda;
use App\Models\VendaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia PDVDel', 'slug' => 'barbearia-pdvdel',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->produto = Produto::create([
        'company_id' => $this->company->id,
        'nome' => 'Shampoo',
        'preco' => 25.00,
        'estoque' => 10,
        'ativo' => true,
    ]);

    $this->venda = Venda::create([
        'company_id' => $this->company->id,
        'subtotal' => 50.00,
        'desconto' => 0.00,
        'total' => 50.00,
        'metodo_pagamento' => 'pix',
    ]);

    VendaItem::create([
        'venda_id' => $this->venda->id,
        'produto_id' => $this->produto->id,
        'descricao' => 'Shampoo',
        'qtd' => 2,
        'preco_unit' => 25.00,
        'total' => 50.00,
    ]);

    Lancamento::create([
        'company_id' => $this->company->id,
        'venda_id' => $this->venda->id,
        'tipo' => 'receita',
        'descricao' => 'Venda PDV',
        'categoria' => 'venda',
        'valor' => 50.00,
        'data' => now()->toDateString(),
        'status' => 'pago',
    ]);
});

describe('pdv_venda_destroy', function () {
    it('admin cancela venda e restaura estoque', function () {
        $estoqueAntes = $this->produto->fresh()->estoque;

        $this->actingAs($this->admin)
            ->deleteJson(route('pdv.vendas.destroy', $this->venda))
            ->assertNoContent();

        expect(Venda::find($this->venda->id))->toBeNull();
        expect($this->produto->fresh()->estoque)->toBe($estoqueAntes + 2);
        expect(Lancamento::where('venda_id', $this->venda->id)->withTrashed()->first()->deleted_at)->not->toBeNull();
    });

    it('gestor pode cancelar venda', function () {
        $this->actingAs($this->gestor)
            ->deleteJson(route('pdv.vendas.destroy', $this->venda))
            ->assertNoContent();
    });

    it('analista não pode cancelar venda', function () {
        $this->actingAs($this->analista)
            ->deleteJson(route('pdv.vendas.destroy', $this->venda))
            ->assertForbidden();
    });

    it('não pode cancelar venda de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-pdvdel', 'plano' => 'trial', 'ativo' => true]);
        $vendaOutra = Venda::create([
            'company_id' => $outra->id, 'subtotal' => 10.00, 'desconto' => 0.00, 'total' => 10.00, 'metodo_pagamento' => 'dinheiro',
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('pdv.vendas.destroy', $vendaOutra))
            ->assertNotFound();
    });

    it('unauthenticated é rejeitado', function () {
        $this->deleteJson(route('pdv.vendas.destroy', $this->venda))
            ->assertUnauthorized();
    });
});
