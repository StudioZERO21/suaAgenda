<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Lancamento;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia FCLst', 'slug' => 'barbearia-fclst',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('financeiro_categorias', function () {
    it('retorna array vazio quando sem categorias', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.categorias'))
            ->assertOk()
            ->json();

        expect($data)->toBeArray()->toHaveCount(0);
    });

    it('lista categorias únicas em ordem alfabética', function () {
        Lancamento::create(['company_id' => $this->company->id, 'tipo' => 'receita', 'descricao' => 'X', 'categoria' => 'Serviços', 'valor' => 50, 'data' => now()->toDateString(), 'status' => 'pago']);
        Lancamento::create(['company_id' => $this->company->id, 'tipo' => 'despesa', 'descricao' => 'Y', 'categoria' => 'Aluguel', 'valor' => 500, 'data' => now()->toDateString(), 'status' => 'pendente']);
        Lancamento::create(['company_id' => $this->company->id, 'tipo' => 'receita', 'descricao' => 'Z', 'categoria' => 'Serviços', 'valor' => 30, 'data' => now()->toDateString(), 'status' => 'pago']);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.categorias'))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(2);
        expect($data[0])->toBe('Aluguel');
        expect($data[1])->toBe('Serviços');
    });

    it('não retorna lançamentos sem categoria', function () {
        Lancamento::create(['company_id' => $this->company->id, 'tipo' => 'receita', 'descricao' => 'Sem cat', 'valor' => 50, 'data' => now()->toDateString(), 'status' => 'pago']);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.categorias'))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(0);
    });

    it('não retorna categorias de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-fclst', 'plano' => 'trial', 'ativo' => true]);
        Lancamento::create(['company_id' => $outra->id, 'tipo' => 'receita', 'descricao' => 'X', 'categoria' => 'OutraCat', 'valor' => 10, 'data' => now()->toDateString(), 'status' => 'pago']);
        Lancamento::create(['company_id' => $this->company->id, 'tipo' => 'receita', 'descricao' => 'Y', 'categoria' => 'MinhaCat', 'valor' => 20, 'data' => now()->toDateString(), 'status' => 'pago']);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.categorias'))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(1);
        expect($data[0])->toBe('MinhaCat');
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('financeiro.categorias'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('financeiro.categorias'))
            ->assertUnauthorized();
    });
});
