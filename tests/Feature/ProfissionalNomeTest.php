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
        'name' => 'Barbearia PN', 'slug' => 'barbearia-pn',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Carlos Silva',
        'ativo' => true,
    ]);
});

describe('profissional_nome', function () {
    it('admin pode atualizar nome do profissional', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('profissionais.nome', $this->prof), ['name' => 'Carlos Santos'])
            ->assertOk()
            ->assertJsonStructure(['name', 'updated_at'])
            ->json();

        expect($data['name'])->toBe('Carlos Santos');
        expect($this->prof->fresh()->name)->toBe('Carlos Santos');
    });

    it('gestor pode atualizar nome', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('profissionais.nome', $this->prof), ['name' => 'Carlos Novo'])
            ->assertOk();
    });

    it('analista não pode atualizar nome', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('profissionais.nome', $this->prof), ['name' => 'Teste'])
            ->assertForbidden();
    });

    it('nome vazio é rejeitado', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.nome', $this->prof), ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('nome acima de 100 chars é rejeitado', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.nome', $this->prof), ['name' => str_repeat('a', 101)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('não pode atualizar profissional de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-pn', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.nome', $profOutra), ['name' => 'Hack'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('profissionais.nome', $this->prof), ['name' => 'Teste'])
            ->assertUnauthorized();
    });
});
