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
        'name' => 'Barbearia ES', 'slug' => 'barbearia-es',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('financeiro_evolucao_saldo', function () {
    it('retorna estrutura correta sem lançamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.evolucao-saldo'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['meses', 'serie']);
        expect($data['meses'])->toBe(6);
        expect($data['serie'])->toHaveCount(6);
    });

    it('cada item da série tem campos corretos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.evolucao-saldo'))
            ->assertOk()
            ->json();

        $item = $data['serie'][0];
        expect($item)->toHaveKeys(['mes', 'ano', 'mes_fmt', 'receita_lancamentos', 'receita_agendamentos', 'receita_total', 'despesa', 'saldo']);
    });

    it('respeita parâmetro meses', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.evolucao-saldo', ['meses' => 4]))
            ->assertOk()
            ->json();

        expect($data['meses'])->toBe(4);
        expect($data['serie'])->toHaveCount(4);
    });

    it('calcula saldo corretamente com lançamentos pagos', function () {
        Lancamento::create([
            'company_id' => $this->company->id,
            'tipo' => 'receita',
            'valor' => 500,
            'status' => 'pago',
            'descricao' => 'Receita ES',
            'data' => now()->startOfMonth()->format('Y-m-d'),
            'metodo_pagamento' => 'dinheiro',
        ]);

        Lancamento::create([
            'company_id' => $this->company->id,
            'tipo' => 'despesa',
            'valor' => 200,
            'status' => 'pago',
            'descricao' => 'Despesa ES',
            'data' => now()->startOfMonth()->format('Y-m-d'),
            'metodo_pagamento' => 'dinheiro',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.evolucao-saldo', ['meses' => 2]))
            ->assertOk()
            ->json();

        // meses=2 gives serie[0]=last month, serie[1]=this month
        $mesAtual = $data['serie'][1];
        expect((float) $mesAtual['receita_lancamentos'])->toBe(500.0);
        expect((float) $mesAtual['despesa'])->toBe(200.0);
        expect((float) $mesAtual['saldo'])->toBe(300.0);
    });

    it('ignora lancamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra ES', 'slug' => 'outra-es', 'plano' => 'trial', 'ativo' => true]);
        Lancamento::create([
            'company_id' => $outra->id,
            'tipo' => 'receita',
            'valor' => 1000,
            'status' => 'pago',
            'descricao' => 'Rec Outra',
            'data' => now()->format('Y-m-d'),
            'metodo_pagamento' => 'dinheiro',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.evolucao-saldo', ['meses' => 1]))
            ->assertOk()
            ->json();

        expect((float) $data['serie'][0]['receita_lancamentos'])->toBe(0.0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('financeiro.evolucao-saldo'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('financeiro.evolucao-saldo'))
            ->assertUnauthorized();
    });
});
