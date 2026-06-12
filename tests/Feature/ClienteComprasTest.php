<?php

declare(strict_types=1);

use App\Models\Cliente;
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
        'name' => 'Barbearia CC', 'slug' => 'barbearia-ccomp',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'ativo' => true]);

    $this->produto = Produto::create([
        'company_id' => $this->company->id,
        'nome' => 'Pomada', 'preco' => 30.0, 'estoque' => 10, 'unidade' => 'un', 'ativo' => true,
    ]);
});

function makeVenda(string $companyId, string $clienteId, float $total, array $itens = []): Venda
{
    $venda = Venda::create([
        'company_id' => $companyId,
        'cliente_id' => $clienteId,
        'subtotal' => $total,
        'desconto' => 0,
        'total' => $total,
        'metodo_pagamento' => 'dinheiro',
    ]);

    foreach ($itens as $item) {
        VendaItem::create([
            'venda_id' => $venda->id,
            'produto_id' => $item['produto_id'],
            'descricao' => $item['descricao'] ?? 'Item',
            'qtd' => $item['qtd'],
            'preco_unit' => $item['preco_unit'],
            'total' => $item['qtd'] * $item['preco_unit'],
        ]);
    }

    return $venda;
}

describe('cliente_compras', function () {
    it('retorna estrutura correta quando sem compras', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.compras', $this->cliente))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['total_compras', 'total_gasto', 'items']);
        expect($data['total_compras'])->toBe(0);
        expect((float) $data['total_gasto'])->toBe(0.0);
        expect($data['items'])->toBeArray();
    });

    it('retorna compras do cliente com estrutura correta', function () {
        makeVenda($this->company->id, $this->cliente->id, 60.0, [
            ['produto_id' => $this->produto->id, 'qtd' => 2, 'preco_unit' => 30.0],
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.compras', $this->cliente))
            ->assertOk()
            ->json();

        expect($data['total_compras'])->toBe(1);
        expect((float) $data['total_gasto'])->toBe(60.0);
        expect($data['items'][0])->toHaveKeys(['id', 'data', 'total', 'metodo_pagamento', 'itens']);
        expect($data['items'][0]['itens'][0]['produto_nome'])->toBe('Pomada');
    });

    it('soma total_gasto corretamente para múltiplas compras', function () {
        makeVenda($this->company->id, $this->cliente->id, 60.0);
        makeVenda($this->company->id, $this->cliente->id, 40.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.compras', $this->cliente))
            ->assertOk()
            ->json();

        expect($data['total_compras'])->toBe(2);
        expect((float) $data['total_gasto'])->toBe(100.0);
    });

    it('não inclui compras de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-ccomp', 'plano' => 'trial', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Z', 'ativo' => true]);
        makeVenda($outra->id, $cliOutra->id, 999.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.compras', $this->cliente))
            ->assertOk()
            ->json();

        expect($data['total_compras'])->toBe(0);
    });

    it('analista pode ver compras', function () {
        $this->actingAs($this->analista)
            ->getJson(route('clientes.compras', $this->cliente))
            ->assertOk();
    });

    it('não pode ver compras de cliente de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra2', 'slug' => 'outra2-ccomp', 'plano' => 'trial', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'W', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->getJson(route('clientes.compras', $cliOutra))
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('clientes.compras', $this->cliente))
            ->assertUnauthorized();
    });
});
