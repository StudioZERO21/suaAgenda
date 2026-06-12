<?php

declare(strict_types=1);

use App\Models\Cargo;
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
        'name' => 'Barbearia CCor', 'slug' => 'barbearia-ccor',
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
        'nome' => 'Barbeiro', 'nivel' => 'junior', 'cor' => '#6b7280',
    ]);
});

describe('cargo_cor', function () {
    it('admin pode atualizar cor do cargo', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('cargos.cor', $this->cargo), ['cor' => '#ff5733'])
            ->assertOk()
            ->assertJsonStructure(['cor', 'updated_at'])
            ->json();

        expect($data['cor'])->toBe('#ff5733');
        expect($this->cargo->fresh()->cor)->toBe('#ff5733');
    });

    it('gestor pode atualizar cor', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('cargos.cor', $this->cargo), ['cor' => '#123456'])
            ->assertOk();
    });

    it('analista pode atualizar cor (abort_if apenas)', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('cargos.cor', $this->cargo), ['cor' => '#abcdef'])
            ->assertOk();
    });

    it('cor inválida é rejeitada (sem #)', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('cargos.cor', $this->cargo), ['cor' => 'ff5733'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cor']);
    });

    it('cor inválida é rejeitada (formato incorreto)', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('cargos.cor', $this->cargo), ['cor' => '#xyz'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cor']);
    });

    it('não pode atualizar cargo de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-ccor', 'plano' => 'trial', 'ativo' => true]);
        $cargoOutra = Cargo::create(['company_id' => $outra->id, 'nome' => 'X', 'nivel' => 'junior', 'cor' => '#000000']);

        $this->actingAs($this->admin)
            ->patchJson(route('cargos.cor', $cargoOutra), ['cor' => '#ff0000'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('cargos.cor', $this->cargo), ['cor' => '#ff5733'])
            ->assertUnauthorized();
    });
});
