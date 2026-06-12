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
        'name' => 'Barbearia ClNome', 'slug' => 'barbearia-clnome',
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
        'name' => 'João Silva',
        'ativo' => true,
    ]);
});

describe('cliente_nome', function () {
    it('admin atualiza nome do cliente', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('clientes.nome', $this->cliente), ['nome' => 'João Pereira'])
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['nome', 'updated_at']);
        expect($data['nome'])->toBe('João Pereira');
        expect($this->cliente->fresh()->name)->toBe('João Pereira');
    });

    it('gestor atualiza nome do cliente', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('clientes.nome', $this->cliente), ['nome' => 'Novo Nome'])
            ->assertOk();
    });

    it('analista não pode atualizar nome', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('clientes.nome', $this->cliente), ['nome' => 'Hack'])
            ->assertForbidden();
    });

    it('nome vazio retorna 422', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('clientes.nome', $this->cliente), ['nome' => ''])
            ->assertUnprocessable();
    });

    it('nome acima de 120 caracteres retorna 422', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('clientes.nome', $this->cliente), ['nome' => str_repeat('A', 121)])
            ->assertUnprocessable();
    });

    it('não pode atualizar cliente de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-clnome', 'plano' => 'trial', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Z', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->patchJson(route('clientes.nome', $cliOutra), ['nome' => 'Hack'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('clientes.nome', $this->cliente), ['nome' => 'X'])
            ->assertUnauthorized();
    });
});
