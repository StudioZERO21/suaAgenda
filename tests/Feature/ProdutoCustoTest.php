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
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Custo', 'slug' => 'barbearia-custo',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->produto = Produto::create([
        'company_id' => $this->company->id,
        'nome' => 'Pomada', 'preco' => 30.0, 'custo' => 12.0, 'estoque' => 10, 'ativo' => true,
    ]);
});

describe('produto_custo', function () {
    it('admin pode atualizar custo do produto', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('produtos.custo', $this->produto), ['custo' => 15.50])
            ->assertOk()
            ->assertJsonStructure(['custo', 'updated_at'])
            ->json();

        expect((float) $data['custo'])->toBe(15.50);
        expect((float) $this->produto->fresh()->custo)->toBe(15.50);
    });

    it('analista pode atualizar custo', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('produtos.custo', $this->produto), ['custo' => 10.0])
            ->assertOk();
    });

    it('rejeita custo negativo', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('produtos.custo', $this->produto), ['custo' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['custo']);
    });

    it('rejeita custo ausente', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('produtos.custo', $this->produto), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['custo']);
    });

    it('não pode atualizar custo de produto de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-custo', 'plano' => 'trial', 'ativo' => true]);
        $prodOutra = Produto::create([
            'company_id' => $outra->id, 'nome' => 'Y', 'preco' => 10.0, 'estoque' => 0, 'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('produtos.custo', $prodOutra), ['custo' => 5.0])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('produtos.custo', $this->produto), ['custo' => 10.0])
            ->assertUnauthorized();
    });
});
