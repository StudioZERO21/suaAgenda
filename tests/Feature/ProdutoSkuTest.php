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
        'name' => 'Barbearia SKU', 'slug' => 'barbearia-sku',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->produto = Produto::create([
        'company_id' => $this->company->id,
        'nome' => 'Pomada', 'preco' => 30.0, 'estoque' => 10, 'ativo' => true,
    ]);
});

describe('produto_sku', function () {
    it('admin pode atualizar SKU', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('produtos.sku', $this->produto), ['sku' => 'POA-001'])
            ->assertOk()
            ->assertJsonStructure(['sku', 'updated_at'])
            ->json();

        expect($data['sku'])->toBe('POA-001');
        expect($this->produto->fresh()->sku)->toBe('POA-001');
    });

    it('analista pode atualizar SKU', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('produtos.sku', $this->produto), ['sku' => 'POA-002'])
            ->assertOk();
    });

    it('aceita SKU nulo para limpar campo', function () {
        $this->produto->update(['sku' => 'OLD-001']);

        $data = $this->actingAs($this->admin)
            ->patchJson(route('produtos.sku', $this->produto), ['sku' => null])
            ->assertOk()
            ->json();

        expect($data['sku'])->toBe('');
    });

    it('rejeita SKU maior que 60 caracteres', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('produtos.sku', $this->produto), ['sku' => str_repeat('x', 61)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sku']);
    });

    it('não pode atualizar produto de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-sku', 'plano' => 'trial', 'ativo' => true]);
        $prodOutra = Produto::create([
            'company_id' => $outra->id, 'nome' => 'X', 'preco' => 10.0, 'estoque' => 0, 'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('produtos.sku', $prodOutra), ['sku' => 'X-001'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('produtos.sku', $this->produto), ['sku' => 'X'])
            ->assertUnauthorized();
    });
});
