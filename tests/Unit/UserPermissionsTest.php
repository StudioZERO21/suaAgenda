<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Support\UserPermissions;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web', 'company_id' => null]);

    $this->company = Company::create([
        'name' => 'Empresa ACL', 'slug' => 'empresa-acl', 'plano' => 'trial', 'ativo' => true,
    ]);

    $this->seed(PermissionSeeder::class);
});

afterEach(function () {
    setPermissionsTeamId(null);
});

describe('UserPermissions', function () {
    it('prioriza grupo da empresa sobre papel analista', function () {
        $grupo = Role::where('company_id', $this->company->id)->where('name', 'Profissional')->first();

        $user = User::create([
            'name' => 'Func',
            'email' => 'func-acl@test.com',
            'password' => bcrypt('secret123'),
            'empresa_id' => $this->company->id,
            'ativo' => true,
        ]);
        $user->assignRole('analista');

        setPermissionsTeamId($this->company->id);
        $user->assignRole($grupo);
        $user->unsetRelation('roles');

        expect(UserPermissions::hasCompanyGrupo($user))->toBeTrue()
            ->and(UserPermissions::can($user, 'cal_own'))->toBeTrue()
            ->and(UserPermissions::can($user, 'fin_view'))->toBeFalse()
            ->and(UserPermissions::can($user, 'srv_view'))->toBeTrue()
            ->and($user->can('fin_view'))->toBeFalse()
            ->and($user->can('cfg_perms'))->toBeFalse();
    });
});
