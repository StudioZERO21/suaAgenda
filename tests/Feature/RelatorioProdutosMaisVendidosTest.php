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
        'name' => 'Barbearia Mais Vendidos', 'slug' => 'barbearia-mv',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

function makeVendaComItem(string $companyId, string $produtoId, int $qtd, float $total): VendaItem
{
    $venda = Venda::create([
        'company_id' => $companyId, 'subtotal' => $total, 'desconto' => 0, 'total' => $total,
        'metodo_pagamento' => 'dinheiro',
    ]);

    return VendaItem::create([
        'venda_id' => $venda->id, 'produto_id' => $produtoId,
        'descricao' => 'produto', 'qtd' => $qtd, 'preco_unit' => $total / $qtd, 'total' => $total,
    ]);
}

describe('relatorio_produtos_mais_vendidos', function () {
    it('retorna lista vazia quando sem vendas', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.produtos-mais-vendidos'))
            ->assertOk()
            ->json();

        expect($data)->toBeEmpty();
    });

    it('retorna produtos ordenados por quantidade vendida', function () {
        $p1 = Produto::create(['company_id' => $this->company->id, 'nome' => 'Pomada', 'preco' => 30.0, 'estoque' => 10, 'ativo' => true]);
        $p2 = Produto::create(['company_id' => $this->company->id, 'nome' => 'Shampoo', 'preco' => 20.0, 'estoque' => 5, 'ativo' => true]);

        makeVendaComItem($this->company->id, $p1->id, 3, 90.0);
        makeVendaComItem($this->company->id, $p2->id, 1, 20.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.produtos-mais-vendidos'))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(2);
        expect($data[0]['nome'])->toBe('Pomada');
        expect($data[0]['qtd_total'])->toBe(3);
        expect((float) $data[0]['receita_total'])->toBe(90.0);
    });

    it('item contém campos esperados', function () {
        $p = Produto::create(['company_id' => $this->company->id, 'nome' => 'Gel', 'preco' => 15.0, 'estoque' => 5, 'ativo' => true]);
        makeVendaComItem($this->company->id, $p->id, 2, 30.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.produtos-mais-vendidos'))
            ->assertOk()
            ->json();

        expect($data[0])->toHaveKeys(['produto_id', 'nome', 'sku', 'categoria', 'qtd_total', 'receita_total']);
    });

    it('não inclui vendas de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-mv', 'plano' => 'trial', 'ativo' => true]);
        $p = Produto::create(['company_id' => $outra->id, 'nome' => 'X', 'preco' => 10.0, 'estoque' => 10, 'ativo' => true]);
        makeVendaComItem($outra->id, $p->id, 5, 50.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.produtos-mais-vendidos'))
            ->assertOk()
            ->json();

        expect($data)->toBeEmpty();
    });

    it('respeita o limite de resultados', function () {
        for ($i = 1; $i <= 5; $i++) {
            $p = Produto::create(['company_id' => $this->company->id, 'nome' => "P{$i}", 'preco' => 10.0, 'estoque' => 10, 'ativo' => true]);
            makeVendaComItem($this->company->id, $p->id, $i, $i * 10.0);
        }

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.produtos-mais-vendidos').'?limite=3')
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(3);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('relatorios.produtos-mais-vendidos'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('relatorios.produtos-mais-vendidos'))
            ->assertUnauthorized();
    });
});
