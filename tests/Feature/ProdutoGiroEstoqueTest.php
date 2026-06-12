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
        'name' => 'Barbearia GE', 'slug' => 'barbearia-ge',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

function makeProdGE(string $companyId, string $nome, int $estoque = 10): Produto
{
    return Produto::create([
        'company_id' => $companyId,
        'nome' => $nome,
        'preco' => 50.0,
        'estoque' => $estoque,
        'estoque_min' => 2,
        'ativo' => true,
    ]);
}

function makeVendaItemGE(string $companyId, string $produtoId, int $qtd): void
{
    $venda = Venda::create([
        'company_id' => $companyId,
        'total' => $qtd * 50.0,
        'subtotal' => $qtd * 50.0,
        'desconto' => 0,
        'metodo_pagamento' => 'dinheiro',
    ]);

    VendaItem::create([
        'venda_id' => $venda->id,
        'produto_id' => $produtoId,
        'descricao' => 'Item GE',
        'qtd' => $qtd,
        'preco_unit' => 50.0,
        'total' => $qtd * 50.0,
    ]);
}

describe('produto_giro_estoque', function () {
    it('retorna estrutura correta sem produtos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.giro-estoque'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo_dias', 'total_produtos', 'items']);
        expect($data['total_produtos'])->toBe(0);
        expect($data['items'])->toBeEmpty();
    });

    it('items têm campos corretos', function () {
        makeProdGE($this->company->id, 'Pomada GE');

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.giro-estoque'))
            ->assertOk()
            ->json();

        $item = $data['items'][0];
        expect($item)->toHaveKeys(['id', 'nome', 'categoria', 'preco', 'estoque_atual', 'estoque_min', 'qtd_vendida', 'receita_periodo', 'giro', 'dias_estoque']);
    });

    it('calcula giro corretamente', function () {
        $prod = makeProdGE($this->company->id, 'Cera GE', 20);
        makeVendaItemGE($this->company->id, $prod->id, 4);

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.giro-estoque'))
            ->assertOk()
            ->json();

        expect($data['items'][0]['qtd_vendida'])->toBe(4);
        expect((float) $data['items'][0]['giro'])->toBe(0.2);
    });

    it('produto sem vendas tem giro null quando estoque > 0', function () {
        makeProdGE($this->company->id, 'Sem Venda GE', 10);

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.giro-estoque'))
            ->assertOk()
            ->json();

        expect($data['items'][0]['qtd_vendida'])->toBe(0);
        expect($data['items'][0]['dias_estoque'])->toBeNull();
    });

    it('ignora produtos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra GE', 'slug' => 'outra-ge', 'plano' => 'trial', 'ativo' => true]);
        makeProdGE($outra->id, 'Prod Outra GE');

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.giro-estoque'))
            ->assertOk()
            ->json();

        expect($data['total_produtos'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('produtos.giro-estoque'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('produtos.giro-estoque'))
            ->assertUnauthorized();
    });
});
