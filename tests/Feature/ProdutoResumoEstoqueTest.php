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
        'name' => 'Barbearia ResumoEstoque', 'slug' => 'barbearia-resumoestoque',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('produto_resumo_estoque', function () {
    it('retorna resumo zerado sem produtos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.resumo-estoque'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['total_produtos', 'sem_estoque', 'estoque_baixo', 'ok', 'valor_total_venda', 'valor_total_custo', 'margem_bruta', 'por_categoria']);
        expect($data['total_produtos'])->toBe(0);
        expect((float) $data['valor_total_venda'])->toBe(0.0);
        expect($data['margem_bruta'])->toBeNull();
        expect($data['por_categoria'])->toBeEmpty();
    });

    it('conta corretamente sem_estoque e estoque_baixo', function () {
        Produto::create(['company_id' => $this->company->id, 'nome' => 'OK', 'preco' => 50, 'custo' => 20, 'estoque' => 10, 'estoque_min' => 3, 'ativo' => true]);
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Baixo', 'preco' => 30, 'custo' => 10, 'estoque' => 2, 'estoque_min' => 5, 'ativo' => true]);
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Zero', 'preco' => 40, 'custo' => 15, 'estoque' => 0, 'estoque_min' => 2, 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.resumo-estoque'))
            ->assertOk()
            ->json();

        expect($data['total_produtos'])->toBe(3);
        expect($data['ok'])->toBe(1);
        expect($data['estoque_baixo'])->toBe(1);
        expect($data['sem_estoque'])->toBe(1);
    });

    it('calcula valor total de venda e custo corretamente', function () {
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Prod A', 'preco' => 100, 'custo' => 60, 'estoque' => 5, 'estoque_min' => 2, 'ativo' => true]);
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Prod B', 'preco' => 50, 'custo' => 20, 'estoque' => 10, 'estoque_min' => 2, 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.resumo-estoque'))
            ->assertOk()
            ->json();

        expect((float) $data['valor_total_venda'])->toBe(1000.0);
        expect((float) $data['valor_total_custo'])->toBe(500.0);
        expect((float) $data['margem_bruta'])->toBe(50.0);
    });

    it('agrupa por categoria', function () {
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Pomada A', 'preco' => 40, 'custo' => 10, 'estoque' => 5, 'estoque_min' => 2, 'categoria' => 'pomadas', 'ativo' => true]);
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Pomada B', 'preco' => 30, 'custo' => 10, 'estoque' => 3, 'estoque_min' => 2, 'categoria' => 'pomadas', 'ativo' => true]);
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Shampoo A', 'preco' => 50, 'custo' => 20, 'estoque' => 2, 'estoque_min' => 1, 'categoria' => 'shampoos', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.resumo-estoque'))
            ->assertOk()
            ->json();

        expect($data['por_categoria'])->toHaveCount(2);

        $pomadas = collect($data['por_categoria'])->firstWhere('categoria', 'pomadas');
        expect($pomadas['total_produtos'])->toBe(2);
        expect((float) $pomadas['valor_estoque'])->toBe(290.0);
    });

    it('ignora produtos inativos', function () {
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Ativo', 'preco' => 50, 'custo' => 20, 'estoque' => 5, 'estoque_min' => 2, 'ativo' => true]);
        Produto::create(['company_id' => $this->company->id, 'nome' => 'Inativo', 'preco' => 50, 'custo' => 20, 'estoque' => 5, 'estoque_min' => 2, 'ativo' => false]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.resumo-estoque'))
            ->assertOk()
            ->json();

        expect($data['total_produtos'])->toBe(1);
    });

    it('ignora produtos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra RE', 'slug' => 'outra-re', 'plano' => 'trial', 'ativo' => true]);
        Produto::create(['company_id' => $outra->id, 'nome' => 'Prod Outra', 'preco' => 50, 'custo' => 20, 'estoque' => 5, 'estoque_min' => 2, 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.resumo-estoque'))
            ->assertOk()
            ->json();

        expect($data['total_produtos'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('produtos.resumo-estoque'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('produtos.resumo-estoque'))
            ->assertUnauthorized();
    });
});
