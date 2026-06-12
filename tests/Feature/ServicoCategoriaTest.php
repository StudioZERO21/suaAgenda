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
        'name' => 'Barbearia Cat', 'slug' => 'barbearia-cat',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->servico = Servico::create([
        'company_id' => $this->company->id,
        'nome' => 'Corte', 'preco' => 45.0,
        'duracao_minutos' => 30, 'ativo' => true,
    ]);
});

describe('servico_categoria', function () {
    it('admin pode atualizar categoria do serviço', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('servicos.categoria', $this->servico), ['categoria' => 'Cabelo'])
            ->assertOk()
            ->assertJsonStructure(['categoria', 'updated_at'])
            ->json();

        expect($data['categoria'])->toBe('Cabelo');
        expect($this->servico->fresh()->categoria)->toBe('Cabelo');
    });

    it('gestor pode atualizar categoria', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('servicos.categoria', $this->servico), ['categoria' => 'Barba'])
            ->assertOk();
    });

    it('analista não pode atualizar categoria', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('servicos.categoria', $this->servico), ['categoria' => 'X'])
            ->assertForbidden();
    });

    it('rejeita categoria vazia', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('servicos.categoria', $this->servico), ['categoria' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['categoria']);
    });

    it('rejeita categoria maior que 60 caracteres', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('servicos.categoria', $this->servico), ['categoria' => str_repeat('x', 61)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['categoria']);
    });

    it('não pode atualizar serviço de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-cat', 'plano' => 'trial', 'ativo' => true]);
        $servOutra = Servico::create([
            'company_id' => $outra->id, 'nome' => 'Y', 'preco' => 30.0, 'duracao_minutos' => 30, 'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('servicos.categoria', $servOutra), ['categoria' => 'X'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('servicos.categoria', $this->servico), ['categoria' => 'X'])
            ->assertUnauthorized();
    });
});
