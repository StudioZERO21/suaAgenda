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
        'name' => 'Barbearia Adm', 'slug' => 'barbearia-adm',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true,
    ]);
});

describe('profissional_admissao', function () {
    it('admin pode atualizar data de admissão', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('profissionais.admissao', $this->profissional), ['admissao' => '2023-01-15'])
            ->assertOk()
            ->assertJsonStructure(['admissao', 'updated_at'])
            ->json();

        expect($data['admissao'])->toBe('2023-01-15');
        expect($this->profissional->fresh()->admissao->format('Y-m-d'))->toBe('2023-01-15');
    });

    it('gestor pode atualizar data de admissão', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('profissionais.admissao', $this->profissional), ['admissao' => '2022-06-01'])
            ->assertOk();
    });

    it('analista não pode atualizar data de admissão', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('profissionais.admissao', $this->profissional), ['admissao' => '2020-01-01'])
            ->assertForbidden();
    });

    it('aceita data de admissão nula para limpar', function () {
        $this->profissional->update(['admissao' => '2023-01-15']);

        $data = $this->actingAs($this->admin)
            ->patchJson(route('profissionais.admissao', $this->profissional), ['admissao' => null])
            ->assertOk()
            ->json();

        expect($data['admissao'])->toBeNull();
    });

    it('rejeita data de admissão no futuro', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.admissao', $this->profissional), ['admissao' => now()->addYear()->format('Y-m-d')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['admissao']);
    });

    it('não pode atualizar profissional de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-adm', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.admissao', $profOutra), ['admissao' => '2020-01-01'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('profissionais.admissao', $this->profissional), ['admissao' => '2020-01-01'])
            ->assertUnauthorized();
    });
});
