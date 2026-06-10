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
        'name' => 'Barbearia ProdExport', 'slug' => 'barbearia-prodexport',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    Produto::create([
        'company_id' => $this->company->id,
        'nome' => 'Pomada Modeladora',
        'sku' => 'SKU-001',
        'categoria' => 'Cabelo',
        'preco' => 35.00,
        'custo' => 15.00,
        'estoque' => 10,
        'estoque_min' => 2,
        'unidade' => 'un.',
        'ativo' => true,
    ]);
});

describe('produto_export', function () {
    it('admin pode exportar CSV', function () {
        $this->actingAs($this->admin)
            ->get(route('produtos.exportar'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    });

    it('CSV contém cabeçalho correto', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('produtos.exportar'));

        $content = $response->streamedContent();
        expect($content)->toContain('Nome');
        expect($content)->toContain('SKU');
        expect($content)->toContain('Categoria');
        expect($content)->toContain('Preço');
        expect($content)->toContain('Estoque');
    });

    it('CSV contém dados do produto', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('produtos.exportar'));

        $content = $response->streamedContent();
        expect($content)->toContain('Pomada Modeladora');
        expect($content)->toContain('SKU-001');
        expect($content)->toContain('Cabelo');
        expect($content)->toContain('Ativo');
    });

    it('não inclui produtos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-prodexport', 'plano' => 'trial', 'ativo' => true]);
        Produto::create(['company_id' => $outra->id, 'nome' => 'Produto Outra', 'preco' => 30.00, 'estoque' => 5, 'ativo' => true]);

        $response = $this->actingAs($this->admin)
            ->get(route('produtos.exportar'));

        $content = $response->streamedContent();
        expect($content)->not->toContain('Produto Outra');
    });

    it('analista pode exportar', function () {
        $this->actingAs($this->analista)
            ->get(route('produtos.exportar'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->get(route('produtos.exportar'))
            ->assertRedirect();
    });
});
