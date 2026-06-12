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
        'name' => 'Barbearia ProdFav', 'slug' => 'barbearia-prodfav',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana', 'lgpd_consent' => true]);
    $this->prodA = Produto::create(['company_id' => $this->company->id, 'nome' => 'Pomada', 'preco' => 30, 'estoque' => 10, 'ativo' => true]);
    $this->prodB = Produto::create(['company_id' => $this->company->id, 'nome' => 'Shampoo', 'preco' => 25, 'estoque' => 5, 'ativo' => true]);
});

function makeVendaProdFav(string $companyId, string $clienteId, array $itens): Venda
{
    $total = array_sum(array_map(fn ($i) => $i[1] * $i[2], $itens));
    $venda = Venda::create([
        'company_id' => $companyId,
        'cliente_id' => $clienteId,
        'subtotal' => $total,
        'desconto' => 0,
        'total' => $total,
        'metodo_pagamento' => 'dinheiro',
    ]);

    foreach ($itens as [$prodId, $qtd, $preco]) {
        VendaItem::create([
            'venda_id' => $venda->id,
            'produto_id' => $prodId,
            'descricao' => 'Item',
            'qtd' => $qtd,
            'preco_unit' => $preco,
            'total' => $qtd * $preco,
        ]);
    }

    return $venda;
}

describe('cliente_produtos_favoritos', function () {
    it('retorna estrutura correta sem compras', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.produtos-favoritos', $this->cliente))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['cliente_id', 'cliente_nome', 'total', 'items']);
        expect($data['total'])->toBe(0);
        expect($data['items'])->toBeEmpty();
    });

    it('ordena produtos por quantidade comprada', function () {
        makeVendaProdFav($this->company->id, $this->cliente->id, [[$this->prodA->id, 3, 30]]);
        makeVendaProdFav($this->company->id, $this->cliente->id, [[$this->prodA->id, 2, 30], [$this->prodB->id, 1, 25]]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.produtos-favoritos', $this->cliente))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(2);
        expect($data['items'][0]['produto_nome'])->toBe('Pomada');
        expect($data['items'][0]['total_comprado'])->toBe(5);
        expect((float) $data['items'][0]['total_gasto'])->toBe(150.0);
    });

    it('ignora vendas de outro cliente', function () {
        $outroCli = Cliente::create(['company_id' => $this->company->id, 'name' => 'Bruno', 'lgpd_consent' => true]);
        makeVendaProdFav($this->company->id, $outroCli->id, [[$this->prodA->id, 10, 30]]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.produtos-favoritos', $this->cliente))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('clientes.produtos-favoritos', $this->cliente))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('clientes.produtos-favoritos', $this->cliente))
            ->assertUnauthorized();
    });
});
