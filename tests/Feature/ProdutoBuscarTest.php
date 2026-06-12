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
        'name' => 'Barbearia ProdBuscar', 'slug' => 'barbearia-prodbuscar',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    Produto::create(['company_id' => $this->company->id, 'nome' => 'Pomada Modeladora', 'preco' => 35.00, 'estoque' => 10, 'ativo' => true]);
    Produto::create(['company_id' => $this->company->id, 'nome' => 'Shampoo Profissional', 'preco' => 50.00, 'estoque' => 5, 'ativo' => true]);
    Produto::create(['company_id' => $this->company->id, 'nome' => 'Pomada Capilar', 'preco' => 40.00, 'estoque' => 8, 'ativo' => true]);
    Produto::create(['company_id' => $this->company->id, 'nome' => 'Inativo', 'preco' => 10.00, 'estoque' => 0, 'ativo' => false]);
});

describe('produto_buscar', function () {
    it('retorna produtos ativos sem query', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.buscar'))
            ->assertOk()
            ->json();

        expect(count($data))->toBe(3);
    });

    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.buscar', ['q' => 'Pomada']))
            ->assertOk()
            ->json();

        expect($data[0])->toHaveKeys(['id', 'nome', 'sku', 'categoria', 'preco', 'estoque', 'unidade']);
    });

    it('filtra por nome quando q é fornecido', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.buscar', ['q' => 'Pomada']))
            ->assertOk()
            ->json();

        expect(count($data))->toBe(2);
        $nomes = collect($data)->pluck('nome')->all();
        expect($nomes)->toContain('Pomada Modeladora');
        expect($nomes)->toContain('Pomada Capilar');
    });

    it('não retorna produtos inativos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.buscar', ['q' => 'Inativo']))
            ->assertOk()
            ->json();

        expect($data)->toBeEmpty();
    });

    it('não retorna produtos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-prodbuscar', 'plano' => 'trial', 'ativo' => true]);
        Produto::create(['company_id' => $outra->id, 'nome' => 'Produto Outra', 'preco' => 30.00, 'estoque' => 5, 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.buscar', ['q' => 'Outra']))
            ->assertOk()
            ->json();

        expect($data)->toBeEmpty();
    });

    it('analista pode buscar produtos', function () {
        $this->actingAs($this->analista)
            ->getJson(route('produtos.buscar'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('produtos.buscar'))
            ->assertUnauthorized();
    });
});
