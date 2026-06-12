<?php

declare(strict_types=1);

use App\Models\Cliente;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Nasc', 'slug' => 'barbearia-nasc',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id,
        'name' => 'João Silva', 'ativo' => true,
    ]);
});

describe('cliente_nascimento', function () {
    it('admin pode atualizar data de nascimento', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('clientes.nascimento', $this->cliente), ['data_nasc' => '1990-05-15'])
            ->assertOk()
            ->assertJsonStructure(['data_nasc', 'updated_at'])
            ->json();

        expect($data['data_nasc'])->toBe('1990-05-15');
        expect($this->cliente->fresh()->data_nasc->format('Y-m-d'))->toBe('1990-05-15');
    });

    it('gestor pode atualizar data de nascimento', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('clientes.nascimento', $this->cliente), ['data_nasc' => '1985-03-22'])
            ->assertOk();
    });

    it('analista não pode atualizar data de nascimento', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('clientes.nascimento', $this->cliente), ['data_nasc' => '1990-01-01'])
            ->assertForbidden();
    });

    it('aceita data nula para limpar campo', function () {
        $this->cliente->update(['data_nasc' => '1990-05-15']);

        $data = $this->actingAs($this->admin)
            ->patchJson(route('clientes.nascimento', $this->cliente), ['data_nasc' => null])
            ->assertOk()
            ->json();

        expect($data['data_nasc'])->toBeNull();
    });

    it('rejeita data no futuro', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('clientes.nascimento', $this->cliente), ['data_nasc' => now()->addYear()->format('Y-m-d')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['data_nasc']);
    });

    it('não pode atualizar cliente de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-nasc', 'plano' => 'trial', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->patchJson(route('clientes.nascimento', $cliOutra), ['data_nasc' => '1990-01-01'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('clientes.nascimento', $this->cliente), ['data_nasc' => '1990-01-01'])
            ->assertUnauthorized();
    });
});
