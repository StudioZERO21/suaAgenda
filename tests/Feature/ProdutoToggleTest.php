<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Produto;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia PToggle', 'slug' => 'barbearia-ptoggle',
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
        'nome' => 'Pomada Modeladora',
        'preco' => 35.00,
        'ativo' => true,
        'estoque' => 10,
    ]);
});

describe('produto_toggle', function () {
    it('admin pode inativar produto ativo', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('produtos.toggle', $this->produto))
            ->assertOk()
            ->json();

        expect($data['ativo'])->toBeFalse();
        expect($this->produto->fresh()->ativo)->toBeFalse();
    });

    it('admin pode reativar produto inativo', function () {
        $this->produto->update(['ativo' => false]);

        $data = $this->actingAs($this->admin)
            ->patchJson(route('produtos.toggle', $this->produto))
            ->assertOk()
            ->json();

        expect($data['ativo'])->toBeTrue();
    });

    it('gestor pode alternar ativo do produto', function () {
        $data = $this->actingAs($this->gestor)
            ->patchJson(route('produtos.toggle', $this->produto))
            ->assertOk()
            ->json();

        expect($data['ativo'])->toBeFalse();
    });

    it('analista pode alternar ativo do produto', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('produtos.toggle', $this->produto))
            ->assertOk();
    });

    it('não pode alternar produto de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-ptoggle', 'plano' => 'trial', 'ativo' => true]);
        $prodOutra = Produto::create([
            'company_id' => $outra->id, 'nome' => 'Produto X', 'preco' => 10, 'ativo' => true, 'estoque' => 5,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('produtos.toggle', $prodOutra))
            ->assertNotFound();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('produtos.toggle', $this->produto))
            ->assertUnauthorized();
    });
});
