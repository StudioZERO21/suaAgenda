<?php

declare(strict_types=1);

use App\Models\Cargo;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['admin_empresa', 'gestor', 'analista'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web', 'company_id' => null]);
    }

    $this->company = Company::create([
        'name' => 'Empresa Grupos', 'slug' => 'empresa-grupos',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->seed(PermissionSeeder::class);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->grupoRecepcao = Role::where('company_id', $this->company->id)
        ->where('name', 'Recepção')
        ->first();

    $this->grupoProfissional = Role::where('company_id', $this->company->id)
        ->where('name', 'Profissional')
        ->first();
});

afterEach(function () {
    setPermissionsTeamId(null);
});

describe('assign_user_grupos', function () {
    it('admin pode atribuir um ou mais grupos ACL ao funcionario', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('permissoes.users.grupos', $this->gestor), [
                'grupo_ids' => [$this->grupoRecepcao->id, $this->grupoProfissional->id],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $fresh = $this->gestor->fresh(['roles']);
        expect($fresh->acl_manual)->toBeTrue();
        expect($fresh->roles->whereNotNull('company_id')->pluck('id')->sort()->values()->all())
            ->toBe(collect([$this->grupoRecepcao->id, $this->grupoProfissional->id])->sort()->values()->all());
    });

    it('admin pode remover todos os grupos ACL', function () {
        setPermissionsTeamId($this->company->id);
        $this->gestor->assignRole($this->grupoRecepcao);
        $this->gestor->update(['acl_manual' => true]);

        $this->actingAs($this->admin)
            ->patchJson(route('permissoes.users.grupos', $this->gestor), ['grupo_ids' => []])
            ->assertOk();

        expect($this->gestor->fresh()->roles->whereNotNull('company_id'))->toBeEmpty();
    });

    it('nao altera grupos de admin da empresa', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('permissoes.users.grupos', $this->admin), [
                'grupo_ids' => [$this->grupoRecepcao->id],
            ])
            ->assertStatus(422);
    });

    it('rejeita grupo de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-grupos', 'plano' => 'trial', 'ativo' => true]);
        $this->seed(PermissionSeeder::class);
        $grupoOutra = Role::where('company_id', $outra->id)->where('name', 'Recepção')->first();

        $this->actingAs($this->admin)
            ->patchJson(route('permissoes.users.grupos', $this->gestor), [
                'grupo_ids' => [$grupoOutra->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['grupo_ids.0']);
    });

    it('gestor sem cfg_perms nao pode atribuir grupos', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('permissoes.users.grupos', $this->gestor), [
                'grupo_ids' => [$this->grupoRecepcao->id],
            ])
            ->assertForbidden();
    });
});

describe('sync_user_grupo_from_cargo', function () {
    it('sincroniza grupo ACL com cargo do profissional e desativa modo manual', function () {
        $grupoCargo = Role::where('company_id', $this->company->id)
            ->where('name', 'Estagiário')
            ->first();

        $cargo = Cargo::create([
            'company_id' => $this->company->id,
            'nome' => 'Auxiliar',
            'cor' => '#6366f1',
            'nivel' => 'operacional',
            'grupo_acesso_id' => $grupoCargo->id,
        ]);

        $prof = Profissional::create([
            'company_id' => $this->company->id,
            'name' => 'João',
            'ativo' => true,
            'cargo_id' => $cargo->id,
        ]);

        $this->gestor->update([
            'profissional_id' => $prof->id,
            'acl_manual' => true,
        ]);

        setPermissionsTeamId($this->company->id);
        $this->gestor->roles()->detach(Role::where('company_id', $this->company->id)->pluck('id'));
        $this->gestor->assignRole($this->grupoRecepcao);

        $this->actingAs($this->admin)
            ->postJson(route('permissoes.users.grupos.cargo', $this->gestor))
            ->assertOk()
            ->assertJsonPath('funcionario.acl_manual', false);

        $fresh = $this->gestor->fresh(['roles']);
        expect($fresh->acl_manual)->toBeFalse();
        expect($fresh->roles->whereNotNull('company_id')->pluck('id')->all())
            ->toBe([$grupoCargo->id]);
    });

    it('nao sobrescreve grupos quando acl_manual ao vincular profissional', function () {
        $grupoCargo = Role::where('company_id', $this->company->id)
            ->where('name', 'Profissional')
            ->first();

        $cargo = Cargo::create([
            'company_id' => $this->company->id,
            'nome' => 'Barbeiro',
            'cor' => '#10b981',
            'nivel' => 'operacional',
            'grupo_acesso_id' => $grupoCargo->id,
        ]);

        $prof = Profissional::create([
            'company_id' => $this->company->id,
            'name' => 'Carlos',
            'ativo' => true,
            'cargo_id' => $cargo->id,
        ]);

        setPermissionsTeamId($this->company->id);
        $this->gestor->roles()->detach(Role::where('company_id', $this->company->id)->pluck('id'));
        $this->gestor->assignRole($this->grupoRecepcao);
        $this->gestor->update(['acl_manual' => true]);

        $this->actingAs($this->admin)
            ->patchJson(route('permissoes.users.profissional', $this->gestor), [
                'profissional_id' => $prof->id,
            ])
            ->assertOk();

        $fresh = $this->gestor->fresh(['roles']);
        expect($fresh->acl_manual)->toBeTrue();
        expect($fresh->roles->whereNotNull('company_id')->pluck('id')->all())
            ->toBe([$this->grupoRecepcao->id]);
    });
});
