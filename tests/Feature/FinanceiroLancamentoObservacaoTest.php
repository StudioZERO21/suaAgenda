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
        'name' => 'Barbearia FLObs', 'slug' => 'barbearia-flobs',
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
        'descricao' => 'Corte',
        'valor' => 50.00,
        'data' => now()->toDateString(),
        'status' => 'pago',
    ]);
});

describe('financeiro_lancamento_observacao', function () {
    it('admin atualiza observação do lançamento', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('financeiro.lancamentos.observacao', $this->lancamento), ['observacao' => 'Cliente pediu nota'])
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['observacao', 'updated_at']);
        expect($data['observacao'])->toBe('Cliente pediu nota');
        expect($this->lancamento->fresh()->observacao)->toBe('Cliente pediu nota');
    });

    it('gestor atualiza observação', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('financeiro.lancamentos.observacao', $this->lancamento), ['observacao' => 'Obs gestor'])
            ->assertOk();
    });

    it('analista pode atualizar observação', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('financeiro.lancamentos.observacao', $this->lancamento), ['observacao' => 'Obs'])
            ->assertOk();
    });

    it('observação nula limpa o campo', function () {
        $this->lancamento->update(['observacao' => 'Texto anterior']);

        $data = $this->actingAs($this->admin)
            ->patchJson(route('financeiro.lancamentos.observacao', $this->lancamento), ['observacao' => null])
            ->assertOk()
            ->json();

        expect($data['observacao'])->toBe('');
    });

    it('observação acima de 1000 chars retorna 422', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('financeiro.lancamentos.observacao', $this->lancamento), ['observacao' => str_repeat('A', 1001)])
            ->assertUnprocessable();
    });

    it('não pode atualizar lançamento de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-flobs', 'plano' => 'trial', 'ativo' => true]);
        $lancOutra = Lancamento::create([
            'company_id' => $outra->id, 'tipo' => 'receita', 'descricao' => 'X', 'valor' => 10.00,
            'data' => now()->toDateString(), 'status' => 'pago',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('financeiro.lancamentos.observacao', $lancOutra), ['observacao' => 'Hack'])
            ->assertNotFound();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('financeiro.lancamentos.observacao', $this->lancamento), ['observacao' => 'X'])
            ->assertUnauthorized();
    });
});
