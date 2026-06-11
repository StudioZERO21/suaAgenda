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
        'name' => 'Barbearia LancBusc', 'slug' => 'barbearia-lancbusc',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('lancamento_buscar', function () {
    it('retorna vazio quando q está vazio', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.lancamentos.buscar'))
            ->assertOk()
            ->json();

        expect($data)->toBeEmpty();
    });

    it('encontra lancamento por descricao', function () {
        Lancamento::create([
            'company_id' => $this->company->id,
            'descricao' => 'Aluguel do salão',
            'tipo' => 'despesa',
            'valor' => 800.0,
            'data' => now(),
            'status' => 'pago',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.lancamentos.buscar', ['q' => 'Aluguel']))
            ->json();

        expect(count($data))->toBe(1);
        expect($data[0]['descricao'])->toBe('Aluguel do salão');
    });

    it('encontra lancamento por tipo', function () {
        Lancamento::create([
            'company_id' => $this->company->id,
            'descricao' => 'Produto X',
            'tipo' => 'receita',
            'valor' => 100.0,
            'data' => now(),
            'status' => 'pago',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.lancamentos.buscar', ['q' => 'receita']))
            ->json();

        expect(count($data))->toBeGreaterThanOrEqual(1);
    });

    it('retorna estrutura correta', function () {
        Lancamento::create([
            'company_id' => $this->company->id,
            'descricao' => 'Comissão',
            'tipo' => 'despesa',
            'valor' => 200.0,
            'data' => now(),
            'status' => 'pendente',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.lancamentos.buscar', ['q' => 'Comissão']))
            ->json();

        expect($data[0])->toHaveKeys(['id', 'descricao', 'tipo', 'categoria', 'valor', 'data', 'status']);
    });

    it('não retorna lancamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-lancbusc', 'plano' => 'trial', 'ativo' => true]);
        Lancamento::create([
            'company_id' => $outra->id,
            'descricao' => 'Água',
            'tipo' => 'despesa',
            'valor' => 50.0,
            'data' => now(),
            'status' => 'pago',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.lancamentos.buscar', ['q' => 'Água']))
            ->json();

        expect($data)->toBeEmpty();
    });

    it('analista pode buscar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('financeiro.lancamentos.buscar', ['q' => 'x']))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('financeiro.lancamentos.buscar', ['q' => 'x']))
            ->assertUnauthorized();
    });
});
