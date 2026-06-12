<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Estoque', 'slug' => 'barbearia-estoque',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->produto = Produto::create([
        'company_id' => $this->company->id, 'nome' => 'Pomada',
        'preco' => 25.0, 'estoque' => 10, 'estoque_min' => 3, 'ativo' => true,
    ]);
});

describe('produto_estoque', function () {
    it('admin pode atualizar estoque', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('produtos.estoque', $this->produto), ['estoque' => 20])
            ->assertOk()
            ->assertJson(['estoque' => 20, 'status' => 'ok']);

        expect($this->produto->fresh()->estoque)->toBe(20);
    });

    it('gestor pode atualizar estoque', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('produtos.estoque', $this->produto), ['estoque' => 5])
            ->assertOk();
    });

    it('retorna status zerado quando estoque zero', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('produtos.estoque', $this->produto), ['estoque' => 0])
            ->json();

        expect($data['status'])->toBe('zerado');
    });

    it('retorna status baixo quando abaixo do mínimo', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('produtos.estoque', $this->produto), ['estoque' => 2])
            ->json();

        expect($data['status'])->toBe('baixo');
    });

    it('rejeita estoque negativo', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('produtos.estoque', $this->produto), ['estoque' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['estoque']);
    });

    it('não pode editar produto de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-est', 'plano' => 'trial', 'ativo' => true]);
        $prodOutra = Produto::create([
            'company_id' => $outra->id, 'nome' => 'X', 'preco' => 5.0, 'estoque' => 10, 'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('produtos.estoque', $prodOutra), ['estoque' => 99])
            ->assertNotFound();
    });

    it('analista pode alterar estoque da própria empresa', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('produtos.estoque', $this->produto), ['estoque' => 5])
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('produtos.estoque', $this->produto), ['estoque' => 5])
            ->assertUnauthorized();
    });
});
