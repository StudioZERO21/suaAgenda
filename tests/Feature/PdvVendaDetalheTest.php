<?php

declare(strict_types=1);

use App\Models\Cliente;
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
        'name' => 'Barbearia PdvDet', 'slug' => 'barbearia-pdvdet',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Marcos', 'phone' => '11999990066']);
    $this->produto = Produto::create(['company_id' => $this->company->id, 'nome' => 'Pomada', 'preco' => 30.0, 'estoque' => 10, 'ativo' => true]);

    $this->venda = Venda::create([
        'company_id' => $this->company->id,
        'cliente_id' => $this->cliente->id,
        'subtotal' => 60.0,
        'desconto' => 10.0,
        'total' => 50.0,
        'metodo_pagamento' => 'dinheiro',
    ]);

    VendaItem::create([
        'venda_id' => $this->venda->id,
        'produto_id' => $this->produto->id,
        'descricao' => 'Pomada',
        'qtd' => 2,
        'preco_unit' => 30.0,
        'total' => 60.0,
    ]);
});

describe('pdv_venda_detalhe', function () {
    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.vendas.detalhe', $this->venda))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['id', 'created_at', 'subtotal', 'desconto', 'total', 'metodo_pagamento', 'observacao', 'cliente', 'profissional', 'itens']);
    });

    it('retorna dados corretos da venda', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.vendas.detalhe', $this->venda))
            ->json();

        expect((float) $data['subtotal'])->toBe(60.0);
        expect((float) $data['desconto'])->toBe(10.0);
        expect((float) $data['total'])->toBe(50.0);
        expect($data['metodo_pagamento'])->toBe('dinheiro');
        expect($data['cliente']['name'])->toBe('Marcos');
    });

    it('inclui itens com estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.vendas.detalhe', $this->venda))
            ->json();

        expect(count($data['itens']))->toBe(1);
        expect($data['itens'][0])->toHaveKeys(['id', 'descricao', 'qtd', 'preco_unit', 'total', 'produto_nome', 'produto_sku', 'servico_nome']);
        expect($data['itens'][0]['qtd'])->toBe(2);
        expect($data['itens'][0]['produto_nome'])->toBe('Pomada');
    });

    it('não acessa venda de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-pdvdet', 'plano' => 'trial', 'ativo' => true]);
        $vendaOutra = Venda::create(['company_id' => $outra->id, 'subtotal' => 10.0, 'desconto' => 0.0, 'total' => 10.0]);

        $this->actingAs($this->admin)
            ->getJson(route('pdv.vendas.detalhe', $vendaOutra))
            ->assertNotFound();
    });

    it('analista pode ver detalhe', function () {
        $this->actingAs($this->analista)
            ->getJson(route('pdv.vendas.detalhe', $this->venda))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('pdv.vendas.detalhe', $this->venda))
            ->assertUnauthorized();
    });
});
