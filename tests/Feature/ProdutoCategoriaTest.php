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
        'name' => 'Barbearia Categoria', 'slug' => 'barbearia-cat',
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
        'nome' => 'Pomada', 'preco' => 30.0, 'estoque' => 10,
        'categoria' => 'Outros', 'ativo' => true,
    ]);
});

describe('produto_categoria', function () {
    it('admin pode atualizar categoria', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('produtos.categoria', $this->produto), ['categoria' => 'Pomadas'])
            ->assertOk()
            ->assertJsonStructure(['categoria', 'updated_at'])
            ->json();

        expect($data['categoria'])->toBe('Pomadas');
        expect($this->produto->fresh()->categoria)->toBe('Pomadas');
    });

    it('gestor pode atualizar categoria', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('produtos.categoria', $this->produto), ['categoria' => 'Shampoos'])
            ->assertOk();
    });

    it('analista pode atualizar categoria', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('produtos.categoria', $this->produto), ['categoria' => 'Shampoos'])
            ->assertOk();
    });

    it('rejeita categoria vazia', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('produtos.categoria', $this->produto), ['categoria' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['categoria']);
    });

    it('rejeita categoria maior que 60 caracteres', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('produtos.categoria', $this->produto), [
                'categoria' => str_repeat('x', 61),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['categoria']);
    });

    it('não pode atualizar produto de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-cat', 'plano' => 'trial', 'ativo' => true]);
        $prodOutra = Produto::create([
            'company_id' => $outra->id, 'nome' => 'X', 'preco' => 10.0, 'estoque' => 0, 'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('produtos.categoria', $prodOutra), ['categoria' => 'hack'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('produtos.categoria', $this->produto), ['categoria' => 'x'])
            ->assertUnauthorized();
    });
});
