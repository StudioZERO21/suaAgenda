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
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia LGPD', 'slug' => 'barbearia-lgpd',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id,
        'name' => 'Claudia',
        'phone' => '11999990033',
        'lgpd_consent' => false,
    ]);
});

describe('cliente_lgpd', function () {
    it('atualiza consentimento para true', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('clientes.lgpd', $this->cliente), ['consent' => true])
            ->assertOk()
            ->json();

        expect($data['lgpd_consent'])->toBeTrue();
        expect($this->cliente->fresh()->lgpd_consent)->toBeTrue();
    });

    it('atualiza consentimento para false', function () {
        $this->cliente->update(['lgpd_consent' => true]);

        $data = $this->actingAs($this->admin)
            ->patchJson(route('clientes.lgpd', $this->cliente), ['consent' => false])
            ->assertOk()
            ->json();

        expect($data['lgpd_consent'])->toBeFalse();
    });

    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('clientes.lgpd', $this->cliente), ['consent' => true])
            ->json();

        expect($data)->toHaveKeys(['lgpd_consent', 'updated_at']);
    });

    it('consent é obrigatório', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('clientes.lgpd', $this->cliente), [])
            ->assertUnprocessable();
    });

    it('analista não pode atualizar LGPD', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('clientes.lgpd', $this->cliente), ['consent' => true])
            ->assertForbidden();
    });

    it('não atualiza LGPD de cliente de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-lgpd', 'plano' => 'trial', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'X', 'phone' => '11000000099']);

        $this->actingAs($this->admin)
            ->patchJson(route('clientes.lgpd', $cliOutra), ['consent' => true])
            ->assertForbidden();
    });

    it('unauthenticated é redirecionado', function () {
        $this->patchJson(route('clientes.lgpd', $this->cliente), ['consent' => true])
            ->assertUnauthorized();
    });
});
