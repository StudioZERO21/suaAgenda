<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
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
        'name' => 'Barbearia Dur', 'slug' => 'barbearia-dur',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);
    $cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'ativo' => true]);

    $this->agendamento = Agendamento::create([
        'company_id' => $this->company->id, 'cliente_id' => $cliente->id,
        'profissional_id' => $prof->id, 'servico_id' => $servico->id,
        'data_hora' => now()->addDay()->setHour(10)->setMinute(0)->setSecond(0)->toDateTimeString(),
        'duracao' => 30, 'valor' => 50.0, 'status' => 'pendente',
    ]);
});

describe('agendamento_duracao', function () {
    it('admin pode atualizar duração do agendamento', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.duracao', $this->agendamento), ['duracao' => 45])
            ->assertOk()
            ->assertJsonStructure(['duracao', 'updated_at'])
            ->json();

        expect($data['duracao'])->toBe(45);
        expect($this->agendamento->fresh()->duracao)->toBe(45);
    });

    it('gestor pode atualizar duração', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('agendamentos.duracao', $this->agendamento), ['duracao' => 60])
            ->assertOk();
    });

    it('analista não pode atualizar duração', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('agendamentos.duracao', $this->agendamento), ['duracao' => 60])
            ->assertForbidden();
    });

    it('rejeita duração menor que 5 minutos', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.duracao', $this->agendamento), ['duracao' => 4])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['duracao']);
    });

    it('rejeita duração maior que 480 minutos', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.duracao', $this->agendamento), ['duracao' => 481])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['duracao']);
    });

    it('não pode atualizar agendamento de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-dur', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'X', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'ativo' => true]);
        $agOutra = Agendamento::create([
            'company_id' => $outra->id, 'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id, 'servico_id' => $servOutra->id,
            'data_hora' => now()->addDay()->toDateTimeString(),
            'duracao' => 30, 'valor' => 50.0, 'status' => 'pendente',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.duracao', $agOutra), ['duracao' => 45])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('agendamentos.duracao', $this->agendamento), ['duracao' => 45])
            ->assertUnauthorized();
    });
});
