<?php

declare(strict_types=1);

use App\Models\Cargo;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia PCargo', 'slug' => 'barbearia-pcargo',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cargo = Cargo::create([
        'company_id' => $this->company->id,
        'nome' => 'Barbeiro',
        'nivel' => 'analista',
        'cor' => '#6b7280',
    ]);

    $this->prof = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Carlos',
        'ativo' => true,
    ]);
});

describe('profissional_cargo', function () {
    it('admin atribui cargo ao profissional', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('profissionais.cargo', $this->prof), ['cargo_id' => $this->cargo->id])
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['cargo_id', 'cargo_nome', 'updated_at']);
        expect($data['cargo_id'])->toBe($this->cargo->id);
        expect($data['cargo_nome'])->toBe('Barbeiro');
        expect($this->prof->fresh()->cargo_id)->toBe($this->cargo->id);
    });

    it('cargo_id nulo remove o cargo do profissional', function () {
        $this->prof->update(['cargo_id' => $this->cargo->id]);

        $data = $this->actingAs($this->admin)
            ->patchJson(route('profissionais.cargo', $this->prof), ['cargo_id' => null])
            ->assertOk()
            ->json();

        expect($data['cargo_id'])->toBeNull();
        expect($this->prof->fresh()->cargo_id)->toBeNull();
    });

    it('cargo de outra empresa retorna 404', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-pcargo', 'plano' => 'trial', 'ativo' => true]);
        $cargoOutra = Cargo::create(['company_id' => $outra->id, 'nome' => 'Hack', 'nivel' => 'analista', 'cor' => '#000']);

        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.cargo', $this->prof), ['cargo_id' => $cargoOutra->id])
            ->assertNotFound();
    });

    it('analista não pode atualizar cargo', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('profissionais.cargo', $this->prof), ['cargo_id' => $this->cargo->id])
            ->assertForbidden();
    });

    it('não pode atualizar profissional de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra2', 'slug' => 'outra2-pcargo', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.cargo', $profOutra), ['cargo_id' => $this->cargo->id])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('profissionais.cargo', $this->prof), ['cargo_id' => $this->cargo->id])
            ->assertUnauthorized();
    });
});
