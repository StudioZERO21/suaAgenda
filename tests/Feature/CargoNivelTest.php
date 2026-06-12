<?php

declare(strict_types=1);

use App\Models\Cargo;
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
        'name' => 'Barbearia CargoNivel', 'slug' => 'barbearia-cargo-nivel',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cargo = Cargo::create([
        'company_id' => $this->company->id,
        'nome' => 'Barbeiro',
        'nivel' => 'Junior',
        'cor' => '#333333',
    ]);
});

describe('cargo_nivel', function () {
    it('admin atualiza nível do cargo', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('cargos.nivel', $this->cargo), ['nivel' => 'Senior'])
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['nivel', 'updated_at']);
        expect($data['nivel'])->toBe('Senior');
        expect($this->cargo->fresh()->nivel)->toBe('Senior');
    });

    it('gestor pode atualizar nível', function () {
        $data = $this->actingAs($this->gestor)
            ->patchJson(route('cargos.nivel', $this->cargo), ['nivel' => 'Pleno'])
            ->assertOk()
            ->json();

        expect($data['nivel'])->toBe('Pleno');
    });

    it('analista pode atualizar nível', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('cargos.nivel', $this->cargo), ['nivel' => 'Junior'])
            ->assertOk();
    });

    it('nivel é obrigatório', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('cargos.nivel', $this->cargo), ['nivel' => ''])
            ->assertUnprocessable();
    });

    it('nivel não pode exceder 50 caracteres', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('cargos.nivel', $this->cargo), ['nivel' => str_repeat('x', 51)])
            ->assertUnprocessable();
    });

    it('não pode atualizar cargo de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-cargo-nivel', 'plano' => 'trial', 'ativo' => true]);
        $cargoOutra = Cargo::create([
            'company_id' => $outra->id, 'nome' => 'X', 'nivel' => 'A', 'cor' => '#000',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('cargos.nivel', $cargoOutra), ['nivel' => 'Senior'])
            ->assertNotFound();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('cargos.nivel', $this->cargo), ['nivel' => 'Senior'])
            ->assertUnauthorized();
    });
});
