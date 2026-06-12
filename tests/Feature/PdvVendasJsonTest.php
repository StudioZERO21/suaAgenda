<?php

declare(strict_types=1);

use App\Models\Company;
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
        'name' => 'Barbearia PVJson', 'slug' => 'barbearia-pvjson',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

function makePvVenda(string $companyId, float $total = 50.0): Venda
{
    $venda = Venda::create([
        'company_id' => $companyId,
        'subtotal' => $total,
        'desconto' => 0.00,
        'total' => $total,
        'metodo_pagamento' => 'pix',
    ]);

    VendaItem::create([
        'venda_id' => $venda->id,
        'descricao' => 'Item',
        'qtd' => 1,
        'preco_unit' => $total,
        'total' => $total,
    ]);

    return $venda;
}

describe('pdv_vendas_json', function () {
    it('retorna estrutura correta quando sem vendas', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.vendas.json'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo', 'total', 'receita_total', 'items']);
        expect($data['total'])->toBe(0);
        expect($data['items'])->toBeArray()->toHaveCount(0);
    });

    it('lista vendas do período com estrutura correta', function () {
        makePvVenda($this->company->id, 100.0);
        makePvVenda($this->company->id, 75.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.vendas.json', ['periodo' => 'mes']))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(2);
        expect((float) $data['receita_total'])->toBe(175.0);
        expect($data['items'][0])->toHaveKeys(['id', 'created_at', 'cliente_nome', 'total', 'desconto', 'metodo_pagamento', 'total_itens']);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('pdv.vendas.json'))
            ->assertOk();
    });

    it('vendas de outra empresa não aparecem na lista', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-pvjson', 'plano' => 'trial', 'ativo' => true]);
        makePvVenda($outra->id);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.vendas.json'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('pdv.vendas.json'))
            ->assertUnauthorized();
    });
});
