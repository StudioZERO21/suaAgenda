<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
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
        'name' => 'Barbearia RAS', 'slug' => 'barbearia-ras',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico1 = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);
    $this->servico2 = Servico::create(['company_id' => $this->company->id, 'nome' => 'Barba', 'preco' => 35.0, 'duracao_minutos' => 20, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'ativo' => true]);

    $this->agendamento = Agendamento::create([
        'company_id' => $this->company->id,
        'cliente_id' => $this->cliente->id,
        'profissional_id' => $this->prof->id,
        'servico_id' => $this->servico1->id,
        'data_hora' => now()->addDay()->setHour(10)->setMinute(0)->setSecond(0)->toDateTimeString(),
        'duracao' => 30,
        'valor' => 50.0,
        'status' => 'pendente',
    ]);
});

describe('agendamento_reassignar_servico', function () {
    it('admin pode trocar serviço do agendamento', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.servico', $this->agendamento), [
                'servico_id' => $this->servico2->id,
            ])
            ->assertOk()
            ->assertJsonStructure(['servico_id', 'servico_nome', 'servico_preco', 'updated_at'])
            ->json();

        expect($data['servico_id'])->toBe($this->servico2->id);
        expect($data['servico_nome'])->toBe('Barba');
        expect((float) $data['servico_preco'])->toBe(35.0);
        expect($this->agendamento->fresh()->servico_id)->toBe($this->servico2->id);
    });

    it('gestor pode trocar serviço', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('agendamentos.servico', $this->agendamento), [
                'servico_id' => $this->servico2->id,
            ])
            ->assertOk();
    });

    it('analista não pode trocar serviço', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('agendamentos.servico', $this->agendamento), [
                'servico_id' => $this->servico2->id,
            ])
            ->assertForbidden();
    });

    it('rejeita serviço de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-ras', 'plano' => 'trial', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'X', 'preco' => 30.0, 'duracao_minutos' => 30, 'ativo' => true]);

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.servico', $this->agendamento), [
                'servico_id' => $servOutra->id,
            ])
            ->assertStatus(404);
    });

    it('rejeita serviço inativo', function () {
        $inativo = Servico::create(['company_id' => $this->company->id, 'nome' => 'Inativo', 'preco' => 20.0, 'duracao_minutos' => 15, 'ativo' => false]);

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.servico', $this->agendamento), [
                'servico_id' => $inativo->id,
            ])
            ->assertStatus(404);
    });

    it('rejeita id inválido', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.servico', $this->agendamento), [
                'servico_id' => 'nao-existe',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['servico_id']);
    });

    it('não pode alterar serviço de agendamento de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra2', 'slug' => 'outra2-ras', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'Y', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'W', 'preco' => 20.0, 'duracao_minutos' => 15, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Z', 'ativo' => true]);
        $agOutra = Agendamento::create([
            'company_id' => $outra->id,
            'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id,
            'servico_id' => $servOutra->id,
            'data_hora' => now()->addDay()->setHour(11)->setMinute(0)->setSecond(0)->toDateTimeString(),
            'duracao' => 15, 'valor' => 20.0, 'status' => 'pendente',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.servico', $agOutra), [
                'servico_id' => $this->servico2->id,
            ])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('agendamentos.servico', $this->agendamento), [
            'servico_id' => $this->servico2->id,
        ])
            ->assertUnauthorized();
    });
});
