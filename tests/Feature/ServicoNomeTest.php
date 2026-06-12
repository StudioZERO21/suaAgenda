<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia SN', 'slug' => 'barbearia-sn',
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
        'nome' => 'Corte',
        'preco' => 50.0,
        'duracao_minutos' => 30,
        'ativo' => true,
    ]);
});

describe('servico_nome', function () {
    it('admin pode atualizar nome do serviço', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('servicos.nome', $this->servico), ['nome' => 'Corte + Barba'])
            ->assertOk()
            ->assertJsonStructure(['nome', 'updated_at'])
            ->json();

        expect($data['nome'])->toBe('Corte + Barba');
        expect($this->servico->fresh()->nome)->toBe('Corte + Barba');
    });

    it('gestor pode atualizar nome', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('servicos.nome', $this->servico), ['nome' => 'Degradê'])
            ->assertOk();
    });

    it('analista não pode atualizar nome', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('servicos.nome', $this->servico), ['nome' => 'Teste'])
            ->assertForbidden();
    });

    it('nome vazio é rejeitado', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('servicos.nome', $this->servico), ['nome' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nome']);
    });

    it('nome acima de 100 chars é rejeitado', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('servicos.nome', $this->servico), ['nome' => str_repeat('x', 101)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nome']);
    });

    it('não pode atualizar serviço de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-sn', 'plano' => 'trial', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'Y', 'preco' => 20.0, 'duracao_minutos' => 15, 'ativo' => true]);

        $this->actingAs($this->admin)
            ->patchJson(route('servicos.nome', $servOutra), ['nome' => 'Hack'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('servicos.nome', $this->servico), ['nome' => 'Teste'])
            ->assertUnauthorized();
    });
});
