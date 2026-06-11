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
        'name' => 'Barbearia Contato CLI', 'slug' => 'barbearia-ct-cli',
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
        'name' => 'João', 'phone' => '11999990000',
        'email' => 'joao@example.com', 'ativo' => true,
    ]);
});

describe('cliente_contato', function () {
    it('admin pode atualizar contato do cliente', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('clientes.contato', $this->cliente), [
                'phone' => '11988881111',
                'email' => 'novo@example.com',
            ])
            ->assertOk()
            ->assertJsonStructure(['name', 'phone', 'email', 'updated_at'])
            ->json();

        expect($data['phone'])->toBe('11988881111');
        expect($data['email'])->toBe('novo@example.com');
        expect($this->cliente->fresh()->phone)->toBe('11988881111');
    });

    it('gestor pode atualizar contato', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('clientes.contato', $this->cliente), ['phone' => '11977770000'])
            ->assertOk();
    });

    it('analista não pode atualizar contato', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('clientes.contato', $this->cliente), ['phone' => '11966660000'])
            ->assertForbidden();
    });

    it('pode atualizar apenas o nome', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('clientes.contato', $this->cliente), ['name' => 'José'])
            ->assertOk()
            ->json();

        expect($data['name'])->toBe('José');
        expect($this->cliente->fresh()->name)->toBe('José');
    });

    it('rejeita email inválido', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('clientes.contato', $this->cliente), ['email' => 'nao-e-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('não pode atualizar cliente de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-ct-cl', 'plano' => 'trial', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->patchJson(route('clientes.contato', $cliOutra), ['phone' => '99999'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('clientes.contato', $this->cliente), ['phone' => '99999'])
            ->assertUnauthorized();
    });
});
