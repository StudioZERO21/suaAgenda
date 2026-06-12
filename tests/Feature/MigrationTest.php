<?php

declare(strict_types=1);

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

describe('tabelas do banco', function () {
    $tabelas = [
        'users',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
        'companies',
        'profissionais',
        'clientes',
        'agendamentos',
    ];

    foreach ($tabelas as $t) {
        it("tabela '$t' existe", fn () => expect(Schema::hasTable($t))->toBeTrue());
    }
});

describe('colunas da tabela users', function () {
    $cols = ['id', 'name', 'email', 'empresa_id', 'ativo', 'deleted_at', 'remember_token', 'created_at', 'updated_at'];

    foreach ($cols as $c) {
        it("users.$c existe", fn () => expect(Schema::hasColumn('users', $c))->toBeTrue());
    }
});

describe('colunas da tabela companies', function () {
    $cols = ['id', 'name', 'slug', 'plano', 'lgpd_consent', 'trial_ends_at', 'ativo', 'deleted_at'];

    foreach ($cols as $c) {
        it("companies.$c existe", fn () => expect(Schema::hasColumn('companies', $c))->toBeTrue());
    }
});

describe('colunas da tabela agendamentos', function () {
    $cols = ['id', 'company_id', 'profissional_id', 'cliente_id', 'data_hora', 'duracao', 'status', 'deleted_at'];

    foreach ($cols as $c) {
        it("agendamentos.$c existe", fn () => expect(Schema::hasColumn('agendamentos', $c))->toBeTrue());
    }
});

it('roles basicos do spatie existem após seed', function () {
    foreach (['super_admin', 'admin_empresa', 'gestor', 'analista'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }

    $roles = Role::pluck('name')->toArray();
    expect($roles)
        ->toContain('super_admin')
        ->toContain('admin_empresa')
        ->toContain('gestor')
        ->toContain('analista');
});
