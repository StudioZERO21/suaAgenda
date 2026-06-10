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
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia CliToggle', 'slug' => 'barbearia-clitogg',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id, 'name' => 'João', 'phone' => '11999990001', 'ativo' => true,
    ]);
});

describe('cliente_toggle', function () {
    it('admin desativa cliente ativo', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('clientes.toggle', $this->cliente))
            ->assertOk()
            ->assertJson(['ativo' => false]);

        expect($this->cliente->fresh()->ativo)->toBeFalse();
    });

    it('admin reativa cliente inativo', function () {
        $this->cliente->update(['ativo' => false]);

        $this->actingAs($this->admin)
            ->patchJson(route('clientes.toggle', $this->cliente))
            ->assertOk()
            ->assertJson(['ativo' => true]);

        expect($this->cliente->fresh()->ativo)->toBeTrue();
    });

    it('analista não pode fazer toggle', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('clientes.toggle', $this->cliente))
            ->assertForbidden();

        expect($this->cliente->fresh()->ativo)->toBeTrue();
    });

    it('não pode fazer toggle de cliente de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-clitogg', 'plano' => 'trial', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'X', 'phone' => '99999999999', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->patchJson(route('clientes.toggle', $cliOutra))
            ->assertForbidden();

        expect($cliOutra->fresh()->ativo)->toBeTrue();
    });

    it('unauthenticated é redirecionado', function () {
        $this->patchJson(route('clientes.toggle', $this->cliente))
            ->assertUnauthorized();
    });
});
