<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Duracao', 'slug' => 'barbearia-duracao',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true,
    ]);
});

describe('servico_duracao', function () {
    it('admin pode atualizar duração do serviço', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('servicos.duracao', $this->servico), ['duracao_minutos' => 45])
            ->assertOk()
            ->assertJsonStructure(['duracao_minutos', 'updated_at'])
            ->json();

        expect($data['duracao_minutos'])->toBe(45);
        expect($this->servico->fresh()->duracao_minutos)->toBe(45);
    });

    it('gestor pode atualizar duração', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('servicos.duracao', $this->servico), ['duracao_minutos' => 60])
            ->assertOk();
    });

    it('analista não pode atualizar duração', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('servicos.duracao', $this->servico), ['duracao_minutos' => 60])
            ->assertForbidden();
    });

    it('rejeita duração menor que 5 minutos', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('servicos.duracao', $this->servico), ['duracao_minutos' => 4])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['duracao_minutos']);
    });

    it('rejeita duração maior que 480 minutos', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('servicos.duracao', $this->servico), ['duracao_minutos' => 481])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['duracao_minutos']);
    });

    it('não pode atualizar serviço de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-dur', 'plano' => 'trial', 'ativo' => true]);
        $servOutra = Servico::create([
            'company_id' => $outra->id, 'nome' => 'X', 'preco' => 30.0, 'duracao_minutos' => 30, 'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('servicos.duracao', $servOutra), ['duracao_minutos' => 45])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('servicos.duracao', $this->servico), ['duracao_minutos' => 30])
            ->assertUnauthorized();
    });
});
