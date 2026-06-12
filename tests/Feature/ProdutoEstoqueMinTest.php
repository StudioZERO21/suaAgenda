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
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia EstMin', 'slug' => 'barbearia-estmin',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->produto = Produto::create([
        'company_id' => $this->company->id,
        'nome' => 'Pomada', 'preco' => 30.0, 'estoque' => 5, 'estoque_min' => 2, 'ativo' => true,
    ]);
});

describe('produto_estoque_min', function () {
    it('admin pode atualizar estoque mínimo', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('produtos.estoque-min', $this->produto), ['estoque_min' => 3])
            ->assertOk()
            ->assertJsonStructure(['estoque_min', 'status', 'updated_at'])
            ->json();

        expect($data['estoque_min'])->toBe(3);
        expect($this->produto->fresh()->estoque_min)->toBe(3);
    });

    it('analista pode atualizar estoque mínimo', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('produtos.estoque-min', $this->produto), ['estoque_min' => 1])
            ->assertOk();
    });

    it('aceita estoque mínimo zero', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('produtos.estoque-min', $this->produto), ['estoque_min' => 0])
            ->assertOk()
            ->json();

        expect($data['estoque_min'])->toBe(0);
    });

    it('rejeita estoque mínimo negativo', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('produtos.estoque-min', $this->produto), ['estoque_min' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['estoque_min']);
    });

    it('não pode atualizar produto de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-estmin', 'plano' => 'trial', 'ativo' => true]);
        $prodOutra = Produto::create([
            'company_id' => $outra->id, 'nome' => 'Y', 'preco' => 10.0, 'estoque' => 0, 'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('produtos.estoque-min', $prodOutra), ['estoque_min' => 5])
            ->assertNotFound();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('produtos.estoque-min', $this->produto), ['estoque_min' => 3])
            ->assertUnauthorized();
    });
});
