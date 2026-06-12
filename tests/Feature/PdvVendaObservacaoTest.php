<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use App\Models\Venda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia PVObs', 'slug' => 'barbearia-pvobs',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->venda = Venda::create([
        'company_id' => $this->company->id,
        'subtotal' => 50.00,
        'desconto' => 0.00,
        'total' => 50.00,
        'metodo_pagamento' => 'pix',
    ]);
});

describe('pdv_venda_observacao', function () {
    it('admin atualiza observação da venda', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('pdv.vendas.observacao', $this->venda), ['observacao' => 'Cliente VIP'])
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['observacao', 'updated_at']);
        expect($data['observacao'])->toBe('Cliente VIP');
        expect($this->venda->fresh()->observacao)->toBe('Cliente VIP');
    });

    it('gestor atualiza observação', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('pdv.vendas.observacao', $this->venda), ['observacao' => 'Obs gestor'])
            ->assertOk();
    });

    it('analista pode atualizar observação', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('pdv.vendas.observacao', $this->venda), ['observacao' => 'Obs'])
            ->assertOk();
    });

    it('observação nula limpa o campo', function () {
        $this->venda->update(['observacao' => 'Texto anterior']);

        $data = $this->actingAs($this->admin)
            ->patchJson(route('pdv.vendas.observacao', $this->venda), ['observacao' => null])
            ->assertOk()
            ->json();

        expect($data['observacao'])->toBe('');
    });

    it('observação acima de 500 chars retorna 422', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('pdv.vendas.observacao', $this->venda), ['observacao' => str_repeat('A', 501)])
            ->assertUnprocessable();
    });

    it('não pode atualizar venda de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-pvobs', 'plano' => 'trial', 'ativo' => true]);
        $vendaOutra = Venda::create([
            'company_id' => $outra->id, 'subtotal' => 10.00, 'desconto' => 0.00, 'total' => 10.00, 'metodo_pagamento' => 'dinheiro',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('pdv.vendas.observacao', $vendaOutra), ['observacao' => 'Hack'])
            ->assertNotFound();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('pdv.vendas.observacao', $this->venda), ['observacao' => 'X'])
            ->assertUnauthorized();
    });
});
