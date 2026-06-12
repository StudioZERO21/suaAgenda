<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Comissao', 'slug' => 'barbearia-comissao',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id, 'name' => 'Carlos',
        'comissao_pct' => 30.0, 'ativo' => true,
    ]);
});

describe('profissional_comissao', function () {
    it('admin pode atualizar comissão', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.comissao', $this->profissional), ['comissao_pct' => 40])
            ->assertOk()
            ->assertJsonStructure(['comissao_pct', 'updated_at']);

        expect((float) $this->profissional->fresh()->comissao_pct)->toBe(40.0);
    });

    it('gestor pode atualizar comissão', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('profissionais.comissao', $this->profissional), ['comissao_pct' => 35])
            ->assertOk();
    });

    it('rejeita comissão maior que 100', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.comissao', $this->profissional), ['comissao_pct' => 101])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['comissao_pct']);
    });

    it('rejeita comissão negativa', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.comissao', $this->profissional), ['comissao_pct' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['comissao_pct']);
    });

    it('aceita comissão zero', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.comissao', $this->profissional), ['comissao_pct' => 0])
            ->assertOk();

        expect((float) $this->profissional->fresh()->comissao_pct)->toBe(0.0);
    });

    it('não pode editar profissional de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-com', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.comissao', $profOutra), ['comissao_pct' => 50])
            ->assertForbidden();
    });

    it('analista não pode alterar comissão', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('profissionais.comissao', $this->profissional), ['comissao_pct' => 50])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('profissionais.comissao', $this->profissional), ['comissao_pct' => 50])
            ->assertUnauthorized();
    });
});
