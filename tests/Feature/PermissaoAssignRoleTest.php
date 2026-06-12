<?php

declare(strict_types=1);

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
        'name' => 'Barbearia Role', 'slug' => 'barbearia-role',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->target = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->target->assignRole('analista');
});

describe('permissao_assign_role', function () {
    it('admin pode alterar role de outro usuário da empresa', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('permissoes.users.role', $this->target), ['role' => 'gestor'])
            ->assertOk()
            ->json();

        expect($data['success'])->toBeTrue();
        expect($data['role'])->toBe('gestor');
        expect($this->target->fresh()->hasRole('gestor'))->toBeTrue();
    });

    it('admin não pode alterar a própria role', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('permissoes.users.role', $this->admin), ['role' => 'analista'])
            ->assertForbidden();
    });

    it('não pode alterar role de usuário de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-role', 'plano' => 'trial', 'ativo' => true]);
        $userOutra = User::factory()->create(['empresa_id' => $outra->id]);
        $userOutra->assignRole('analista');

        $this->actingAs($this->admin)
            ->patchJson(route('permissoes.users.role', $userOutra), ['role' => 'gestor'])
            ->assertForbidden();
    });

    it('gestor não pode alterar roles', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('permissoes.users.role', $this->target), ['role' => 'gestor'])
            ->assertForbidden();
    });

    it('rejeita role inválida', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('permissoes.users.role', $this->target), ['role' => 'super_admin'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    });

    it('aceita role analista', function () {
        $this->target->syncRoles(['gestor']);

        $this->actingAs($this->admin)
            ->patchJson(route('permissoes.users.role', $this->target), ['role' => 'analista'])
            ->assertOk();

        expect($this->target->fresh()->hasRole('analista'))->toBeTrue();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('permissoes.users.role', $this->target), ['role' => 'gestor'])
            ->assertUnauthorized();
    });
});
