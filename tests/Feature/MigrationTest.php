<?php
use Illuminate\Support\Facades\Schema;
describe('tabelas do banco', function () {
    $tabelas = [        'users',
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
        'role_has_permissions',];
    foreach ($tabelas as $t) { it("tabela '$t' existe", fn() => expect(Schema::hasTable($t))->toBeTrue()); }
});
describe('colunas da tabela users', function () {
    $cols = ['id','name','email','empresa_id','ativo','deleted_at','remember_token','created_at','updated_at'];
    foreach ($cols as $c) { it("users.$c existe", fn() => expect(Schema::hasColumn('users',$c))->toBeTrue()); }
});
it('nao ha migrations pendentes', function () { expect(shell_exec('php artisan migrate:status 2>&1'))->not->toContain('Pending'); });
it('roles basicos do spatie existem', function () {
    $roles = \Spatie\Permission\Models\Role::pluck('name')->toArray();
    expect($roles)->toContain('super_admin')->toContain('admin_empresa');
});