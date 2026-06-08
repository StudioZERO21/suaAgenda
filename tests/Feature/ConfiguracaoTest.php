<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor',        'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista',      'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Teste',
        'slug' => 'empresa-teste',
        'plano' => 'trial',
        'whatsapp' => '(11) 99999-0000',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('configuracoes', function () {
    it('admin pode ver configurações', function () {
        $this->actingAs($this->admin)
            ->get(route('configuracoes'))
            ->assertOk()
            ->assertSee($this->company->name);
    });

    it('gestor pode ver configurações', function () {
        $this->actingAs($this->gestor)
            ->get(route('configuracoes'))
            ->assertOk();
    });

    it('analista pode ver configurações', function () {
        $this->actingAs($this->analista)
            ->get(route('configuracoes'))
            ->assertOk();
    });

    it('admin pode atualizar nome e whatsapp', function () {
        $this->actingAs($this->admin)
            ->put(route('configuracoes.update'), [
                'name' => 'Barbearia Nova',
                'whatsapp' => '(11) 88888-7777',
                'lgpd_consent' => '0',
            ])
            ->assertRedirect(route('configuracoes'));

        expect($this->company->fresh()->name)->toBe('Barbearia Nova');
        expect($this->company->fresh()->whatsapp)->toBe('(11) 88888-7777');
    });

    it('admin pode ativar lgpd_consent', function () {
        $this->actingAs($this->admin)
            ->put(route('configuracoes.update'), [
                'name' => $this->company->name,
                'lgpd_consent' => '1',
            ])
            ->assertRedirect(route('configuracoes'));

        expect($this->company->fresh()->lgpd_consent)->toBeTrue();
    });

    it('gestor não pode atualizar configurações', function () {
        $this->actingAs($this->gestor)
            ->put(route('configuracoes.update'), [
                'name' => 'Hack',
                'whatsapp' => '(00) 00000-0000',
            ])
            ->assertForbidden();
    });

    it('analista não pode atualizar configurações', function () {
        $this->actingAs($this->analista)
            ->put(route('configuracoes.update'), [
                'name' => 'Hack',
                'whatsapp' => '(00) 00000-0000',
            ])
            ->assertForbidden();
    });

    it('nome é obrigatório', function () {
        $this->actingAs($this->admin)
            ->put(route('configuracoes.update'), [
                'name' => '',
                'whatsapp' => '',
            ])
            ->assertSessionHasErrors('name');
    });

    it('isolamento: usuário sem empresa não acessa configurações', function () {
        $semEmpresa = User::factory()->create(['empresa_id' => null]);

        $this->actingAs($semEmpresa)
            ->get(route('configuracoes'))
            ->assertStatus(404);
    });
});
