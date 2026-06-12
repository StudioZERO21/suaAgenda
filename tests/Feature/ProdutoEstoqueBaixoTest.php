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
        'name' => 'Barbearia ProdEstq', 'slug' => 'barbearia-prodestq',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('produto_estoque_baixo', function () {
    it('retorna produtos com estoque igual ou abaixo do mínimo', function () {
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Zerado', 'preco' => 10.0, 'estoque' => 0, 'estoque_min' => 2, 'ativo' => true]);
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Baixo', 'preco' => 10.0, 'estoque' => 1, 'estoque_min' => 3, 'ativo' => true]);
        Produto::create(['company_id' => $this->company->id, 'nome' => 'OK', 'preco' => 10.0, 'estoque' => 10, 'estoque_min' => 2, 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.estoque-baixo'))
            ->assertOk()
            ->json();

        expect(count($data))->toBe(2);
        $nomes = collect($data)->pluck('nome')->all();
        expect($nomes)->toContain('Zerado');
        expect($nomes)->toContain('Baixo');
        expect($nomes)->not->toContain('OK');
    });

    it('retorna estrutura correta', function () {
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Baixo', 'preco' => 10.0, 'estoque' => 0, 'estoque_min' => 2, 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.estoque-baixo'))
            ->json();

        expect($data[0])->toHaveKeys(['id', 'nome', 'sku', 'categoria', 'estoque', 'estoque_min', 'unidade', 'status']);
    });

    it('não retorna produtos inativos', function () {
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Inativo Baixo', 'preco' => 10.0, 'estoque' => 0, 'estoque_min' => 2, 'ativo' => false]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.estoque-baixo'))
            ->json();

        expect($data)->toBeEmpty();
    });

    it('retorna vazio quando sem estoque baixo', function () {
        Produto::create(['company_id' => $this->company->id, 'nome' => 'OK', 'preco' => 10.0, 'estoque' => 10, 'estoque_min' => 2, 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.estoque-baixo'))
            ->json();

        expect($data)->toBeEmpty();
    });

    it('não retorna produtos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-prodestq', 'plano' => 'trial', 'ativo' => true]);
        Produto::create(['company_id' => $outra->id, 'nome' => 'Baixo Outra', 'preco' => 10.0, 'estoque' => 0, 'estoque_min' => 5, 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.estoque-baixo'))
            ->json();

        expect($data)->toBeEmpty();
    });

    it('analista pode ver estoque baixo', function () {
        $this->actingAs($this->analista)
            ->getJson(route('produtos.estoque-baixo'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('produtos.estoque-baixo'))
            ->assertUnauthorized();
    });
});
