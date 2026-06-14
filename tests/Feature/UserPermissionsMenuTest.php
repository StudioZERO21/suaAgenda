<?php

declare(strict_types=1);

use App\Models\Cargo;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\User;
use App\Support\NavMenu;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['super_admin', 'admin_empresa', 'gestor', 'analista'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web', 'company_id' => null]);
    }

    $this->company = Company::create([
        'name' => 'Empresa Menu', 'slug' => 'empresa-menu', 'plano' => 'trial', 'ativo' => true,
    ]);

    $this->seed(PermissionSeeder::class);
});

afterEach(function () {
    setPermissionsTeamId(null);
});

describe('user_permissions_menu', function () {
    it('funcionário com grupo ACL usa só permissions do grupo, não do papel analista', function () {
        $grupo = Role::where('company_id', $this->company->id)->where('name', 'Profissional')->first();

        $cargo = Cargo::create([
            'company_id' => $this->company->id,
            'nome' => 'Barbeiro',
            'nivel' => 'professional',
            'grupo_acesso_id' => $grupo->id,
        ]);

        $prof = Profissional::create([
            'company_id' => $this->company->id,
            'name' => 'João',
            'ativo' => true,
            'cargo_id' => $cargo->id,
        ]);

        $func = User::create([
            'name' => 'João Func',
            'email' => 'joao-menu@test.com',
            'password' => bcrypt('secret123'),
            'empresa_id' => $this->company->id,
            'profissional_id' => $prof->id,
            'ativo' => true,
        ]);
        $func->assignRole('analista');
        $func->assignRole($grupo);

        setPermissionsTeamId($this->company->id);
        $func->unsetRelation('roles');

        expect($func->can('fin_view'))->toBeFalse()
            ->and($func->can('srv_view'))->toBeTrue()
            ->and($func->can('cal_own'))->toBeTrue()
            ->and($func->can('cal_create'))->toBeFalse()
            ->and($func->can('cfg_perms'))->toBeFalse();

        $routes = array_column(NavMenu::itens($func), 'route');

        expect($routes)->toContain('dashboard')
            ->and($routes)->toContain('calendario')
            ->and($routes)->toContain('clientes.index')
            ->and($routes)->toContain('servicos.index')
            ->and($routes)->not->toContain('permissoes.index')
            ->and($routes)->not->toContain('configuracoes')
            ->and($routes)->toContain('financeiro');
    });

    it('analista sem grupo ACL mantém permissões padrão do papel', function () {
        $analista = User::create([
            'name' => 'Analista',
            'email' => 'analista-menu@test.com',
            'password' => bcrypt('secret123'),
            'empresa_id' => $this->company->id,
            'ativo' => true,
        ]);
        $analista->assignRole('analista');

        setPermissionsTeamId($this->company->id);
        $analista->unsetRelation('roles');

        expect($analista->can('fin_view'))->toBeTrue()
            ->and($analista->can('srv_view'))->toBeTrue();

        $routes = array_column(NavMenu::itens($analista), 'route');

        expect($routes)->toContain('servicos.index')
            ->and($routes)->toContain('produtos.index');
    });

    it('menu da página não exibe módulos sem permissão para funcionário restrito', function () {
        $grupo = Role::where('company_id', $this->company->id)->where('name', 'Profissional')->first();

        $func = User::create([
            'name' => 'Restrito',
            'email' => 'restrito-menu@test.com',
            'password' => bcrypt('secret123'),
            'empresa_id' => $this->company->id,
            'ativo' => true,
        ]);
        $func->assignRole('analista');
        $func->assignRole($grupo);

        setPermissionsTeamId($this->company->id);

        $this->actingAs($func)
            ->get(route('dashboard.funcionario'))
            ->assertOk()
            ->assertDontSee('href="'.route('permissoes.index').'"', false)
            ->assertDontSee('href="'.route('configuracoes').'"', false)
            ->assertDontSee('Novo Agendamento');
    });
});
