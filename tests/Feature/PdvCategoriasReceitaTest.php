<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Produto;
use App\Models\Role;
use App\Models\User;
use App\Models\Venda;
use App\Models\VendaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia CatRec', 'slug' => 'barbearia-catrec',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

function makeVendaCatRec(string $companyId, string $categoria, float $preco, int $qtd): void
{
    $produto = Produto::create([
        'company_id' => $companyId, 'nome' => "Prod {$categoria}", 'preco' => $preco,
        'categoria' => $categoria, 'estoque' => 100, 'ativo' => true,
    ]);
    $venda = Venda::create([
        'company_id' => $companyId, 'subtotal' => $preco * $qtd,
        'desconto' => 0, 'total' => $preco * $qtd, 'metodo_pagamento' => 'pix',
    ]);
    VendaItem::create([
        'venda_id' => $venda->id, 'produto_id' => $produto->id,
        'descricao' => 'Item', 'qtd' => $qtd, 'preco_unit' => $preco,
        'total' => $preco * $qtd,
    ]);
}

describe('pdv_categorias_receita', function () {
    it('retorna lista vazia sem vendas', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.categorias-receita'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo_dias', 'total_categorias', 'items']);
        expect($data['total_categorias'])->toBe(0);
        expect($data['items'])->toBeEmpty();
    });

    it('agrupa por categoria e ordena por receita', function () {
        makeVendaCatRec($this->company->id, 'pomadas', 30.0, 5);
        makeVendaCatRec($this->company->id, 'shampoos', 25.0, 2);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.categorias-receita'))
            ->assertOk()
            ->json();

        expect($data['total_categorias'])->toBe(2);
        expect($data['items'][0]['categoria'])->toBe('pomadas');
        expect((float) $data['items'][0]['receita'])->toBe(150.0);
        expect($data['items'][0]['total_itens'])->toBe(5);
    });

    it('respeita o parâmetro dias', function () {
        makeVendaCatRec($this->company->id, 'pomadas', 30.0, 1);

        $produto = Produto::create([
            'company_id' => $this->company->id, 'nome' => 'Antigo', 'preco' => 50.0,
            'categoria' => 'antigos', 'estoque' => 10, 'ativo' => true,
        ]);
        $venda = Venda::create([
            'company_id' => $this->company->id, 'subtotal' => 50.0,
            'desconto' => 0, 'total' => 50.0, 'metodo_pagamento' => 'pix',
        ]);
        $venda->created_at = now()->subDays(60);
        $venda->save();
        VendaItem::create([
            'venda_id' => $venda->id, 'produto_id' => $produto->id,
            'descricao' => 'Item', 'qtd' => 1, 'preco_unit' => 50.0, 'total' => 50.0,
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.categorias-receita', ['dias' => 30]))
            ->assertOk()
            ->json();

        expect($data['total_categorias'])->toBe(1);
        expect($data['items'][0]['categoria'])->toBe('pomadas');
        expect($data['periodo_dias'])->toBe(30);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('pdv.categorias-receita'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('pdv.categorias-receita'))
            ->assertUnauthorized();
    });
});
