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
        'name' => 'Barbearia PdvBusc', 'slug' => 'barbearia-pdvbusc',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('pdv_produtos_buscar', function () {
    it('retorna produtos sem filtro quando q vazio', function () {
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Pomada', 'preco' => 30.0, 'estoque' => 5, 'ativo' => true]);
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Shampoo', 'preco' => 20.0, 'estoque' => 3, 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.produtos.buscar'))
            ->assertOk()
            ->json();

        expect(count($data))->toBe(2);
    });

    it('filtra por nome', function () {
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Pomada Brilho', 'preco' => 30.0, 'estoque' => 5, 'ativo' => true]);
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Shampoo Premium', 'preco' => 20.0, 'estoque' => 3, 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.produtos.buscar', ['q' => 'Pomada']))
            ->json();

        expect(count($data))->toBe(1);
        expect($data[0]['nome'])->toBe('Pomada Brilho');
    });

    it('retorna estrutura correta', function () {
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Gel', 'preco' => 15.0, 'estoque' => 10, 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.produtos.buscar'))
            ->json();

        expect($data[0])->toHaveKeys(['id', 'nome', 'sku', 'categoria', 'preco', 'estoque', 'unidade']);
    });

    it('não retorna produtos com estoque zero', function () {
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Zerado', 'preco' => 10.0, 'estoque' => 0, 'ativo' => true]);
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Disponivel', 'preco' => 10.0, 'estoque' => 2, 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.produtos.buscar'))
            ->json();

        $nomes = collect($data)->pluck('nome')->all();
        expect($nomes)->not->toContain('Zerado');
        expect($nomes)->toContain('Disponivel');
    });

    it('não retorna produtos inativos', function () {
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Inativo', 'preco' => 10.0, 'estoque' => 5, 'ativo' => false]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.produtos.buscar'))
            ->json();

        expect($data)->toBeEmpty();
    });

    it('não retorna produtos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-pdvbusc', 'plano' => 'trial', 'ativo' => true]);
        Produto::create(['company_id' => $outra->id, 'nome' => 'Produto Outra', 'preco' => 10.0, 'estoque' => 5, 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.produtos.buscar'))
            ->json();

        expect($data)->toBeEmpty();
    });

    it('analista pode buscar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('pdv.produtos.buscar'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('pdv.produtos.buscar'))
            ->assertUnauthorized();
    });
});
