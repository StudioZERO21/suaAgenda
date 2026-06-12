<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Produto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venda;
use App\Models\VendaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia MaisVend', 'slug' => 'barbearia-maisvend',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prodA = Produto::create(['company_id' => $this->company->id, 'nome' => 'Pomada', 'preco' => 30, 'estoque' => 10, 'ativo' => true]);
    $this->prodB = Produto::create(['company_id' => $this->company->id, 'nome' => 'Shampoo', 'preco' => 25, 'estoque' => 5, 'ativo' => true]);
});

function makeVendaMaisVend(string $companyId, string $prodId, int $qtd, float $preco): Venda
{
    $venda = Venda::create([
        'company_id' => $companyId,
        'subtotal' => $qtd * $preco,
        'desconto' => 0,
        'total' => $qtd * $preco,
        'metodo_pagamento' => 'dinheiro',
    ]);

    VendaItem::create([
        'venda_id' => $venda->id,
        'produto_id' => $prodId,
        'descricao' => 'Item',
        'qtd' => $qtd,
        'preco_unit' => $preco,
        'total' => $qtd * $preco,
    ]);

    return $venda;
}

describe('pdv_mais_vendidos', function () {
    it('retorna estrutura correta sem vendas', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.produtos.mais-vendidos'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['total_produtos', 'items']);
        expect($data['total_produtos'])->toBe(0);
        expect($data['items'])->toBeEmpty();
    });

    it('ordena produtos por quantidade vendida', function () {
        makeVendaMaisVend($this->company->id, $this->prodA->id, 5, 30);
        makeVendaMaisVend($this->company->id, $this->prodA->id, 3, 30);
        makeVendaMaisVend($this->company->id, $this->prodB->id, 2, 25);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.produtos.mais-vendidos'))
            ->assertOk()
            ->json();

        expect($data['total_produtos'])->toBe(2);
        expect($data['items'][0]['produto_nome'])->toBe('Pomada');
        expect($data['items'][0]['total_vendido'])->toBe(8);
        expect((float) $data['items'][0]['receita_total'])->toBe(240.0);
    });

    it('não retorna dados de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-maisvend', 'plano' => 'trial', 'ativo' => true]);
        $prodOutra = Produto::create(['company_id' => $outra->id, 'nome' => 'X', 'preco' => 10, 'estoque' => 5, 'ativo' => true]);
        $venda = Venda::create(['company_id' => $outra->id, 'subtotal' => 10, 'desconto' => 0, 'total' => 10, 'metodo_pagamento' => 'dinheiro']);
        VendaItem::create(['venda_id' => $venda->id, 'produto_id' => $prodOutra->id, 'descricao' => 'X', 'qtd' => 1, 'preco_unit' => 10, 'total' => 10]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.produtos.mais-vendidos'))
            ->assertOk()
            ->json();

        expect($data['total_produtos'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('pdv.produtos.mais-vendidos'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('pdv.produtos.mais-vendidos'))
            ->assertUnauthorized();
    });
});
