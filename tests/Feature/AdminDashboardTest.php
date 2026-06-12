<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web', 'company_id' => null]);
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web', 'company_id' => null]);

    $this->company = Company::create(['name' => 'Empresa X', 'slug' => 'empresa-x', 'plano' => 'trial', 'plan_slug' => 'starter', 'ativo' => true]);

    $this->super = User::create([
        'name' => 'Super', 'email' => 'super@admin.test',
        'password' => bcrypt('secret123'), 'ativo' => true,
    ]);
    $this->super->assignRole('super_admin');

    $this->admin = User::create([
        'name' => 'Admin', 'email' => 'admin@admin.test',
        'password' => bcrypt('secret123'), 'empresa_id' => $this->company->id, 'ativo' => true,
    ]);
    $this->admin->assignRole('admin_empresa');
});

describe('admin_dashboard', function () {
    it('super_admin é redirecionado do dashboard comum para o painel do sistema', function () {
        $this->actingAs($this->super)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    });

    it('exibe o painel global com métricas', function () {
        $this->actingAs($this->super)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertViewHas('totalEmpresas', 1)
            ->assertSee('Painel do Sistema');
    });

    it('admin_empresa não acessa o painel do sistema', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    });

    it('lista empresas com busca', function () {
        Company::create(['name' => 'Salao Y', 'slug' => 'salao-y', 'plano' => 'trial', 'ativo' => true]);

        $this->actingAs($this->super)
            ->get(route('admin.empresas.index', ['q' => 'Salao']))
            ->assertOk()
            ->assertSee('Salao Y')
            ->assertDontSee('Empresa X');
    });

    it('mostra detalhe da empresa', function () {
        $this->actingAs($this->super)
            ->get(route('admin.empresas.show', $this->company))
            ->assertOk()
            ->assertSee('Empresa X');
    });

    it('alterna o status ativo da empresa', function () {
        $this->actingAs($this->super)
            ->patchJson(route('admin.empresas.toggle', $this->company))
            ->assertOk()
            ->assertJson(['success' => true, 'ativo' => false]);

        expect($this->company->fresh()->ativo)->toBeFalse();
    });

    it('admin_empresa não pode alternar status de empresa', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('admin.empresas.toggle', $this->company))
            ->assertForbidden();
    });
});
