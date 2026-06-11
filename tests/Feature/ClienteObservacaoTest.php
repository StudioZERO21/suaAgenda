<?php

declare(strict_types=1);

use App\Models\Cliente;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Obs', 'slug' => 'barbearia-obs',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id, 'name' => 'Pedro', 'phone' => '11999990022',
    ]);
});

describe('cliente_observacao', function () {
    it('admin pode salvar observação', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('clientes.observacao', $this->cliente), [
                'observacao' => 'Cliente prefere corte degradê.',
            ])
            ->assertOk()
            ->assertJsonStructure(['observacao', 'updated_at']);

        expect($this->cliente->fresh()->observacao)->toBe('Cliente prefere corte degradê.');
    });

    it('gestor pode salvar observação', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('clientes.observacao', $this->cliente), ['observacao' => 'Nota gestor'])
            ->assertOk();
    });

    it('pode limpar observação com null', function () {
        $this->cliente->update(['observacao' => 'Observação existente']);

        $this->actingAs($this->admin)
            ->patchJson(route('clientes.observacao', $this->cliente), ['observacao' => null])
            ->assertOk()
            ->assertJson(['observacao' => null]);

        expect($this->cliente->fresh()->observacao)->toBeNull();
    });

    it('rejeita observação maior que 1000 caracteres', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('clientes.observacao', $this->cliente), [
                'observacao' => str_repeat('x', 1001),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['observacao']);
    });

    it('não pode editar cliente de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-obs', 'plano' => 'trial', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'X', 'phone' => '99999999999']);

        $this->actingAs($this->admin)
            ->patchJson(route('clientes.observacao', $cliOutra), ['observacao' => 'Hack'])
            ->assertForbidden();
    });

    it('analista não pode editar observação', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('clientes.observacao', $this->cliente), ['observacao' => 'Tentativa'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('clientes.observacao', $this->cliente), ['observacao' => 'Anon'])
            ->assertUnauthorized();
    });
});
