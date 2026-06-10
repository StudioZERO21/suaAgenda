<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Profissional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia ProfToggle', 'slug' => 'barbearia-proftogg',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true,
    ]);
});

describe('profissional_toggle', function () {
    it('admin desativa profissional ativo', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.toggle', $this->profissional))
            ->assertOk()
            ->assertJson(['ativo' => false]);

        expect($this->profissional->fresh()->ativo)->toBeFalse();
    });

    it('admin reativa profissional inativo', function () {
        $this->profissional->update(['ativo' => false]);

        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.toggle', $this->profissional))
            ->assertOk()
            ->assertJson(['ativo' => true]);

        expect($this->profissional->fresh()->ativo)->toBeTrue();
    });

    it('analista não pode fazer toggle', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('profissionais.toggle', $this->profissional))
            ->assertForbidden();

        expect($this->profissional->fresh()->ativo)->toBeTrue();
    });

    it('não pode fazer toggle de profissional de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-proftogg', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.toggle', $profOutra))
            ->assertForbidden();

        expect($profOutra->fresh()->ativo)->toBeTrue();
    });

    it('unauthenticated é redirecionado', function () {
        $this->patchJson(route('profissionais.toggle', $this->profissional))
            ->assertUnauthorized();
    });
});
