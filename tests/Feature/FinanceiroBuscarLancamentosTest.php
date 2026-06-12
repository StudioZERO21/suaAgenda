<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Lancamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia FBsc', 'slug' => 'barbearia-fbsc',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    Lancamento::create([
        'company_id' => $this->company->id, 'tipo' => 'receita',
        'descricao' => 'Corte de cabelo', 'categoria' => 'Serviços',
        'valor' => 50, 'data' => now()->toDateString(), 'status' => 'pago',
    ]);

    Lancamento::create([
        'company_id' => $this->company->id, 'tipo' => 'despesa',
        'descricao' => 'Aluguel sala', 'categoria' => 'Fixo',
        'valor' => 800, 'data' => now()->toDateString(), 'status' => 'pendente',
    ]);
});

describe('financeiro_buscar_lancamentos', function () {
    it('retorna array vazio quando q está ausente', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.lancamentos.buscar'))
            ->assertOk()
            ->json();

        expect($data)->toBeArray()->toHaveCount(0);
    });

    it('retorna array vazio quando q é string vazia', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.lancamentos.buscar', ['q' => '']))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(0);
    });

    it('busca por descrição', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.lancamentos.buscar', ['q' => 'Corte']))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(1);
        expect($data[0]['descricao'])->toBe('Corte de cabelo');
        expect($data[0])->toHaveKeys(['id', 'descricao', 'tipo', 'categoria', 'valor', 'data', 'status']);
    });

    it('busca por categoria', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.lancamentos.buscar', ['q' => 'Fixo']))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(1);
        expect($data[0]['descricao'])->toBe('Aluguel sala');
    });

    it('busca por tipo', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.lancamentos.buscar', ['q' => 'receita']))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(1);
        expect($data[0]['tipo'])->toBe('receita');
    });

    it('não retorna lançamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-fbsc', 'plano' => 'trial', 'ativo' => true]);
        Lancamento::create([
            'company_id' => $outra->id, 'tipo' => 'receita',
            'descricao' => 'Corte outra', 'valor' => 30,
            'data' => now()->toDateString(), 'status' => 'pago',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.lancamentos.buscar', ['q' => 'Corte']))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(1);
        expect($data[0]['descricao'])->toBe('Corte de cabelo');
    });

    it('analista pode buscar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('financeiro.lancamentos.buscar', ['q' => 'Corte']))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('financeiro.lancamentos.buscar', ['q' => 'Corte']))
            ->assertUnauthorized();
    });
});
