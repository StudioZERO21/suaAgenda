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
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia FLShow', 'slug' => 'barbearia-flshow',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->lancamento = Lancamento::create([
        'company_id' => $this->company->id,
        'tipo' => 'receita',
        'descricao' => 'Corte de cabelo',
        'categoria' => 'Serviços',
        'valor' => 75.00,
        'data' => now()->toDateString(),
        'status' => 'pago',
        'metodo_pagamento' => 'pix',
    ]);
});

describe('financeiro_lancamento_show', function () {
    it('admin acessa detalhe do lancamento', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.lancamentos.show', $this->lancamento))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['id', 'data', 'cliente', 'servico', 'valor', 'status', 'tipo', 'metodo']);
        expect($data['id'])->toBe($this->lancamento->id);
        expect((float) $data['valor'])->toBe(75.0);
        expect($data['status'])->toBe('pago');
        expect($data['tipo'])->toBe('receita');
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('financeiro.lancamentos.show', $this->lancamento))
            ->assertOk();
    });

    it('não pode acessar lançamento de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-flshow', 'plano' => 'trial', 'ativo' => true]);
        $lancOutra = Lancamento::create([
            'company_id' => $outra->id,
            'tipo' => 'despesa',
            'descricao' => 'Aluguel',
            'valor' => 500.00,
            'data' => now()->toDateString(),
            'status' => 'pendente',
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('financeiro.lancamentos.show', $lancOutra))
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('financeiro.lancamentos.show', $this->lancamento))
            ->assertUnauthorized();
    });
});
