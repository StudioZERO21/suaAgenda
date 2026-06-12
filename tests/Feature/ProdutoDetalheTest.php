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
        'name' => 'Barbearia ProdDet', 'slug' => 'barbearia-proddet',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->produto = Produto::create([
        'company_id' => $this->company->id,
        'nome' => 'Pomada Matte',
        'sku' => 'PM001',
        'categoria' => 'Finalizadores',
        'preco' => 45.0,
        'custo' => 20.0,
        'estoque' => 15,
        'estoque_min' => 3,
        'unidade' => 'un.',
        'ativo' => true,
    ]);
});

describe('produto_detalhe', function () {
    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.detalhe', $this->produto))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['id', 'nome', 'sku', 'categoria', 'preco', 'custo', 'estoque', 'estoque_min', 'unidade', 'descricao', 'ativo', 'imagens']);
    });

    it('retorna dados corretos do produto', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.detalhe', $this->produto))
            ->json();

        expect($data['nome'])->toBe('Pomada Matte');
        expect($data['sku'])->toBe('PM001');
        expect($data['categoria'])->toBe('Finalizadores');
        expect((float) $data['preco'])->toBe(45.0);
        expect((float) $data['custo'])->toBe(20.0);
        expect($data['estoque'])->toBe(15);
        expect($data['ativo'])->toBeTrue();
        expect($data['imagens'])->toBeArray();
    });

    it('não acessa produto de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-proddet', 'plano' => 'trial', 'ativo' => true]);
        $prodOutra = Produto::create(['company_id' => $outra->id, 'nome' => 'X', 'preco' => 10.0, 'estoque' => 1, 'ativo' => true]);

        $this->actingAs($this->admin)
            ->getJson(route('produtos.detalhe', $prodOutra))
            ->assertNotFound();
    });

    it('analista pode ver detalhe', function () {
        $this->actingAs($this->analista)
            ->getJson(route('produtos.detalhe', $this->produto))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('produtos.detalhe', $this->produto))
            ->assertUnauthorized();
    });
});
