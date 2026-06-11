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
        'name' => 'Barbearia Precos', 'slug' => 'barbearia-precos',
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
        'preco' => 30.0, 'estoque' => 10, 'ativo' => true,
    ]);
});

describe('produto_preco', function () {
    it('admin pode atualizar preço do produto', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('produtos.preco', $this->produto), ['preco' => 45.0])
            ->assertOk()
            ->assertJsonStructure(['preco', 'updated_at'])
            ->json();

        expect((float) $data['preco'])->toBe(45.0);
        expect((float) $this->produto->fresh()->preco)->toBe(45.0);
    });

    it('gestor pode atualizar preço', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('produtos.preco', $this->produto), ['preco' => 35.0])
            ->assertOk();
    });

    it('analista pode atualizar preço', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('produtos.preco', $this->produto), ['preco' => 35.0])
            ->assertOk();
    });

    it('aceita preço zero', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('produtos.preco', $this->produto), ['preco' => 0])
            ->assertOk()
            ->json();

        expect((float) $data['preco'])->toBe(0.0);
    });

    it('rejeita preço negativo', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('produtos.preco', $this->produto), ['preco' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['preco']);
    });

    it('não pode atualizar produto de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-pr', 'plano' => 'trial', 'ativo' => true]);
        $prodOutra = Produto::create([
            'company_id' => $outra->id, 'nome' => 'X', 'preco' => 10.0, 'estoque' => 0, 'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('produtos.preco', $prodOutra), ['preco' => 99.0])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('produtos.preco', $this->produto), ['preco' => 30.0])
            ->assertUnauthorized();
    });
});
