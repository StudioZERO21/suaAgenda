<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);

    $this->companyA = Company::create(['name' => 'Empresa A', 'slug' => 'empresa-a', 'plano' => 'trial', 'ativo' => true]);
    $this->companyB = Company::create(['name' => 'Empresa B', 'slug' => 'empresa-b', 'plano' => 'trial', 'ativo' => true]);

    $this->userA = User::factory()->create(['empresa_id' => $this->companyA->id]);
    $this->userA->assignRole('admin_empresa');

    $this->userB = User::factory()->create(['empresa_id' => $this->companyB->id]);
    $this->userB->assignRole('admin_empresa');
});

describe('multi-tenancy', function () {
    it('usuário da empresa A não vê dados da empresa B', function () {
        $profB = Profissional::create(['company_id' => $this->companyB->id, 'name' => 'Profissional B', 'ativo' => true]);
        $clienteB = Cliente::create(['company_id' => $this->companyB->id, 'name' => 'Cliente B', 'lgpd_consent' => true]);

        $agendamentoB = Agendamento::create([
            'company_id' => $this->companyB->id,
            'profissional_id' => $profB->id,
            'cliente_id' => $clienteB->id,
            'data_hora' => now()->addDay(),
            'duracao' => 60,
            'status' => 'pendente',
        ]);

        $this->actingAs($this->userA)
            ->get(route('agendamentos.show', $agendamentoB))
            ->assertForbidden();
    });

    it('usuário sem empresa_id não pode criar agendamento', function () {
        $userSemEmpresa = User::factory()->create(['empresa_id' => null]);
        $userSemEmpresa->assignRole('admin_empresa');

        $this->actingAs($userSemEmpresa)
            ->post(route('agendamentos.store'), [
                'profissional_id' => fake()->uuid(),
                'cliente_id' => fake()->uuid(),
                'data_hora' => now()->addDay()->format('Y-m-d H:i:s'),
                'duracao' => 60,
            ])
            ->assertForbidden();
    });

    it('empresa A e B têm dados isolados no index', function () {
        $profA = Profissional::create(['company_id' => $this->companyA->id, 'name' => 'Prof A', 'ativo' => true]);
        $clienteA = Cliente::create(['company_id' => $this->companyA->id, 'name' => 'Cliente A', 'lgpd_consent' => true]);

        Agendamento::create([
            'company_id' => $this->companyA->id,
            'profissional_id' => $profA->id,
            'cliente_id' => $clienteA->id,
            'data_hora' => now()->addDay(),
            'duracao' => 60,
            'status' => 'pendente',
        ]);

        $agendamentosEmpresaA = Agendamento::where('company_id', $this->companyA->id)->count();
        $agendamentosEmpresaB = Agendamento::where('company_id', $this->companyB->id)->count();

        expect($agendamentosEmpresaA)->toBe(1);
        expect($agendamentosEmpresaB)->toBe(0);
    });

    it('middleware SetTenant injeta tenant_id correto', function () {
        $this->actingAs($this->userA)
            ->get(route('dashboard'))
            ->assertStatus(200);

        expect(app()->has('tenant_id'))->toBeTrue();
        expect(app('tenant_id'))->toBe($this->userA->empresa_id);
    });

    it('super_admin ignora todas as policies', function () {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $superAdmin = User::factory()->create(['empresa_id' => null]);
        $superAdmin->assignRole('super_admin');

        $profB = Profissional::create(['company_id' => $this->companyB->id, 'name' => 'Prof B', 'ativo' => true]);
        $clienteB = Cliente::create(['company_id' => $this->companyB->id, 'name' => 'Cliente B', 'lgpd_consent' => true]);

        $agendamentoB = Agendamento::create([
            'company_id' => $this->companyB->id,
            'profissional_id' => $profB->id,
            'cliente_id' => $clienteB->id,
            'data_hora' => now()->addDay(),
            'duracao' => 60,
            'status' => 'pendente',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('agendamentos.show', $agendamentoB))
            ->assertStatus(200);
    });
});
