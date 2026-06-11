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
        'name' => 'Barbearia Unidade', 'slug' => 'barbearia-un',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->produto = Produto::create([
        'company_id' => $this->company->id,
        'nome' => 'Pomada', 'preco' => 30.0, 'estoque' => 10,
        'unidade' => 'un.', 'ativo' => true,
    ]);
});

describe('produto_unidade', function () {
    it('admin pode atualizar unidade', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('produtos.unidade', $this->produto), ['unidade' => 'ml'])
            ->assertOk()
            ->assertJsonStructure(['unidade', 'updated_at'])
            ->json();

        expect($data['unidade'])->toBe('ml');
        expect($this->produto->fresh()->unidade)->toBe('ml');
    });

    it('analista pode atualizar unidade', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('produtos.unidade', $this->produto), ['unidade' => 'kg'])
            ->assertOk();
    });

    it('rejeita unidade vazia', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('produtos.unidade', $this->produto), ['unidade' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['unidade']);
    });

    it('rejeita unidade maior que 10 caracteres', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('produtos.unidade', $this->produto), ['unidade' => str_repeat('x', 11)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['unidade']);
    });

    it('não pode atualizar produto de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-un', 'plano' => 'trial', 'ativo' => true]);
        $prodOutra = Produto::create([
            'company_id' => $outra->id, 'nome' => 'X', 'preco' => 10.0, 'estoque' => 0, 'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('produtos.unidade', $prodOutra), ['unidade' => 'kg'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('produtos.unidade', $this->produto), ['unidade' => 'ml'])
            ->assertUnauthorized();
    });
});
