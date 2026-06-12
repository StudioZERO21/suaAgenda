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
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia FStat', 'slug' => 'barbearia-fstat',
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
        'tipo' => 'receita',
        'descricao' => 'Serviço X',
        'valor' => 100,
        'data' => now()->toDateString(),
        'status' => 'pendente',
    ]);
});

describe('financeiro_status_lancamento', function () {
    it('admin atualiza status para pago', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('financeiro.lancamentos.status', $this->lancamento), ['status' => 'pago'])
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['status', 'updated_at']);
        expect($data['status'])->toBe('pago');
        expect($this->lancamento->fresh()->status)->toBe('pago');
    });

    it('gestor atualiza status para cancelado', function () {
        $data = $this->actingAs($this->gestor)
            ->patchJson(route('financeiro.lancamentos.status', $this->lancamento), ['status' => 'cancelado'])
            ->assertOk()
            ->json();

        expect($data['status'])->toBe('cancelado');
    });

    it('analista não pode atualizar status', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('financeiro.lancamentos.status', $this->lancamento), ['status' => 'pago'])
            ->assertForbidden();
    });

    it('status inválido retorna 422', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('financeiro.lancamentos.status', $this->lancamento), ['status' => 'aprovado'])
            ->assertUnprocessable();
    });

    it('não pode atualizar lancamento de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-fstat', 'plano' => 'trial', 'ativo' => true]);
        $lancOutra = Lancamento::create([
            'company_id' => $outra->id, 'tipo' => 'receita', 'descricao' => 'X',
            'valor' => 50, 'data' => now()->toDateString(), 'status' => 'pendente',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('financeiro.lancamentos.status', $lancOutra), ['status' => 'pago'])
            ->assertNotFound();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('financeiro.lancamentos.status', $this->lancamento), ['status' => 'pago'])
            ->assertUnauthorized();
    });

    it('status é obrigatório', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('financeiro.lancamentos.status', $this->lancamento), [])
            ->assertUnprocessable();
    });
});
