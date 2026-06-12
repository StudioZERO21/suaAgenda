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
        'name' => 'Barbearia LancStatus', 'slug' => 'barbearia-ls',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->lancamento = Lancamento::create([
        'company_id' => $this->company->id,
        'tipo' => 'receita', 'descricao' => 'Corte', 'categoria' => 'Serviço',
        'valor' => 50.0, 'data' => today()->format('Y-m-d'),
        'status' => 'pendente', 'metodo_pagamento' => 'dinheiro',
    ]);
});

describe('lancamento_status', function () {
    it('admin pode atualizar status para pago', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('financeiro.lancamentos.status', $this->lancamento), ['status' => 'pago'])
            ->assertOk()
            ->assertJsonStructure(['status', 'updated_at'])
            ->json();

        expect($data['status'])->toBe('pago');
        expect($this->lancamento->fresh()->status)->toBe('pago');
    });

    it('gestor pode atualizar status', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('financeiro.lancamentos.status', $this->lancamento), ['status' => 'cancelado'])
            ->assertOk();
    });

    it('analista não pode atualizar status', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('financeiro.lancamentos.status', $this->lancamento), ['status' => 'pago'])
            ->assertForbidden();
    });

    it('rejeita status inválido', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('financeiro.lancamentos.status', $this->lancamento), ['status' => 'arquivado'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    });

    it('não pode atualizar lançamento de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-ls', 'plano' => 'trial', 'ativo' => true]);
        $lancOutra = Lancamento::create([
            'company_id' => $outra->id, 'tipo' => 'receita', 'descricao' => 'X',
            'valor' => 10.0, 'data' => today()->format('Y-m-d'),
            'status' => 'pendente', 'metodo_pagamento' => 'dinheiro',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('financeiro.lancamentos.status', $lancOutra), ['status' => 'pago'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('financeiro.lancamentos.status', $this->lancamento), ['status' => 'pago'])
            ->assertUnauthorized();
    });
});
