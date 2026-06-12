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
        'name' => 'Barbearia ProdCat', 'slug' => 'barbearia-prodcat',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('produto_categorias', function () {
    it('retorna array vazio quando sem categorias', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.categorias'))
            ->assertOk()
            ->json();

        expect($data)->toBeArray()->toHaveCount(0);
    });

    it('lista categorias únicas em ordem alfabética', function () {
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Pomada', 'preco' => 30, 'ativo' => true, 'estoque' => 5, 'categoria' => 'Finalizadores']);
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Shampoo', 'preco' => 20, 'ativo' => true, 'estoque' => 10, 'categoria' => 'Cuidados']);
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Cera', 'preco' => 25, 'ativo' => true, 'estoque' => 8, 'categoria' => 'Finalizadores']);

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.categorias'))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(2);
        expect($data[0])->toBe('Cuidados');
        expect($data[1])->toBe('Finalizadores');
    });

    it('não retorna produtos sem categoria', function () {
        Produto::create(['company_id' => $this->company->id, 'nome' => 'SemCat', 'preco' => 10, 'ativo' => true, 'estoque' => 1]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.categorias'))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(0);
    });

    it('não retorna categorias de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-prodcat', 'plano' => 'trial', 'ativo' => true]);
        Produto::create(['company_id' => $outra->id, 'nome' => 'X', 'preco' => 10, 'ativo' => true, 'estoque' => 1, 'categoria' => 'Cat Outra']);
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Y', 'preco' => 10, 'ativo' => true, 'estoque' => 1, 'categoria' => 'Cat Minha']);

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.categorias'))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(1);
        expect($data[0])->toBe('Cat Minha');
    });

    it('analista pode listar categorias', function () {
        $this->actingAs($this->analista)
            ->getJson(route('produtos.categorias'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('produtos.categorias'))
            ->assertUnauthorized();
    });
});
