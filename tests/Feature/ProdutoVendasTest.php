<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Produto;
use App\Models\User;
use App\Models\Venda;
use App\Models\VendaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia ProdVendas', 'slug' => 'barbearia-pv',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->produto = Produto::create([
        'company_id' => $this->company->id,
        'nome' => 'Pomada', 'preco' => 30.0, 'estoque' => 10, 'ativo' => true,
    ]);
});

describe('produto_vendas', function () {
    it('retorna lista vazia quando sem vendas', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.vendas', $this->produto))
            ->assertOk()
            ->assertJsonStructure(['total_vendas', 'items'])
            ->json();

        expect($data['total_vendas'])->toBe(0);
        expect($data['items'])->toBeEmpty();
    });

    it('retorna histórico de vendas do produto', function () {
        $venda = Venda::create([
            'company_id' => $this->company->id,
            'subtotal' => 30.0, 'desconto' => 0, 'total' => 30.0, 'metodo_pagamento' => 'dinheiro',
        ]);
        VendaItem::create([
            'venda_id' => $venda->id, 'produto_id' => $this->produto->id,
            'descricao' => 'Pomada', 'qtd' => 1, 'preco_unit' => 30.0, 'total' => 30.0,
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.vendas', $this->produto))
            ->assertOk()
            ->json();

        expect($data['total_vendas'])->toBe(1);
        expect($data['items'][0])->toHaveKeys(['venda_id', 'data', 'qtd', 'preco_unit', 'total']);
        expect((float) $data['items'][0]['preco_unit'])->toBe(30.0);
    });

    it('analista pode acessar histórico de vendas', function () {
        $this->actingAs($this->analista)
            ->getJson(route('produtos.vendas', $this->produto))
            ->assertOk();
    });

    it('não expõe vendas de produto de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-pv', 'plano' => 'trial', 'ativo' => true]);
        $prodOutra = Produto::create([
            'company_id' => $outra->id, 'nome' => 'X', 'preco' => 10.0, 'estoque' => 0, 'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('produtos.vendas', $prodOutra))
            ->assertNotFound();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('produtos.vendas', $this->produto))
            ->assertUnauthorized();
    });
});
