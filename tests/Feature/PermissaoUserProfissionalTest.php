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
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa UserProf', 'slug' => 'empresa-userprof',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true,
    ]);
});

describe('assign_user_profissional', function () {
    it('admin pode vincular usuário a profissional', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('permissoes.users.profissional', $this->gestor), [
                'profissional_id' => $this->profissional->id,
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'profissional_id' => $this->profissional->id]);

        expect($this->gestor->fresh()->profissional_id)->toBe($this->profissional->id);
    });

    it('admin pode desvincular usuário de profissional (null)', function () {
        $this->gestor->update(['profissional_id' => $this->profissional->id]);

        $this->actingAs($this->admin)
            ->patchJson(route('permissoes.users.profissional', $this->gestor), [
                'profissional_id' => null,
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'profissional_id' => null]);

        expect($this->gestor->fresh()->profissional_id)->toBeNull();
    });

    it('não pode vincular a profissional de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-userprof', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->patchJson(route('permissoes.users.profissional', $this->gestor), [
                'profissional_id' => $profOutra->id,
            ])
            ->assertStatus(422);
    });

    it('não pode vincular usuário de outra empresa', function () {
        $outra = Company::create(['name' => 'OtraB', 'slug' => 'otrab-userprof', 'plano' => 'trial', 'ativo' => true]);
        $userOutra = User::factory()->create(['empresa_id' => $outra->id]);
        $userOutra->assignRole('analista');

        $this->actingAs($this->admin)
            ->patchJson(route('permissoes.users.profissional', $userOutra), [
                'profissional_id' => $this->profissional->id,
            ])
            ->assertForbidden();
    });

    it('gestor não pode vincular profissional a usuário', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('permissoes.users.profissional', $this->analista), [
                'profissional_id' => $this->profissional->id,
            ])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('permissoes.users.profissional', $this->gestor), [
            'profissional_id' => $this->profissional->id,
        ])->assertUnauthorized();
    });
});
