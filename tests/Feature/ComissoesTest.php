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
    Role::firstOrCreate(['name' => 'gestor',        'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista',      'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Teste',
        'slug' => 'empresa-teste',
        'plano' => 'trial',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');
});

describe('comissão no model profissional', function () {
    it('armazena e recupera comissao_pct', function () {
        $profissional = Profissional::create([
            'company_id' => $this->company->id,
            'name' => 'João',
            'ativo' => true,
            'comissao_pct' => 30.0,
        ]);

        expect($profissional->fresh()->comissao_pct)->toBe('30.00');
    });

    it('formata comissão com símbolo de porcentagem', function () {
        $profissional = Profissional::create([
            'company_id' => $this->company->id,
            'name' => 'Maria',
            'ativo' => true,
            'comissao_pct' => 15.5,
        ]);

        expect($profissional->comissaoFormatada())->toBe('15,5%');
    });

    it('retorna traço quando comissão é nula', function () {
        $profissional = Profissional::create([
            'company_id' => $this->company->id,
            'name' => 'Pedro',
            'ativo' => true,
        ]);

        expect($profissional->comissaoFormatada())->toBe('—');
    });
});

describe('comissão nos formulários de profissional', function () {
    it('cria profissional com comissão via form', function () {
        $this->actingAs($this->admin)
            ->post(route('profissionais.store'), [
                'name' => 'Novo Profissional',
                'especialidade' => 'Barbeiro',
                'comissao_pct' => 20,
                'ativo' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('profissionais', [
            'name' => 'Novo Profissional',
            'comissao_pct' => 20.00,
        ]);
    });

    it('cria profissional sem comissão (campo opcional)', function () {
        $this->actingAs($this->admin)
            ->post(route('profissionais.store'), [
                'name' => 'Sem Comissão',
                'ativo' => true,
            ])
            ->assertRedirect();

        $profissional = Profissional::where('name', 'Sem Comissão')->first();
        expect($profissional->comissao_pct)->toBeNull();
    });

    it('rejeita comissão acima de 100', function () {
        $this->actingAs($this->admin)
            ->post(route('profissionais.store'), [
                'name' => 'Inválido',
                'comissao_pct' => 101,
                'ativo' => true,
            ])
            ->assertSessionHasErrors('comissao_pct');
    });

    it('rejeita comissão negativa', function () {
        $this->actingAs($this->admin)
            ->post(route('profissionais.store'), [
                'name' => 'Inválido',
                'comissao_pct' => -5,
                'ativo' => true,
            ])
            ->assertSessionHasErrors('comissao_pct');
    });

    it('atualiza comissão de profissional existente', function () {
        $profissional = Profissional::create([
            'company_id' => $this->company->id,
            'name' => 'Atualizar',
            'ativo' => true,
            'comissao_pct' => 10,
        ]);

        $this->actingAs($this->admin)
            ->put(route('profissionais.update', $profissional), [
                'name' => 'Atualizar',
                'ativo' => true,
                'comissao_pct' => 25.5,
            ])
            ->assertRedirect();

        expect($profissional->fresh()->comissao_pct)->toBe('25.50');
    });
});
