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
        'name' => 'Barbearia RAC', 'slug' => 'barbearia-rac',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);
    $this->cliente1 = Cliente::create(['company_id' => $this->company->id, 'name' => 'João Original', 'ativo' => true]);
    $this->cliente2 = Cliente::create(['company_id' => $this->company->id, 'name' => 'Pedro Novo', 'ativo' => true]);

    $this->agendamento = Agendamento::create([
        'company_id' => $this->company->id,
        'cliente_id' => $this->cliente1->id,
        'profissional_id' => $this->prof->id,
        'servico_id' => $this->servico->id,
        'data_hora' => now()->addDay()->setHour(10)->setMinute(0)->setSecond(0)->toDateTimeString(),
        'duracao' => 30, 'valor' => 50.0, 'status' => 'pendente',
    ]);
});

describe('agendamento_reassignar_cliente', function () {
    it('admin pode trocar cliente do agendamento', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.cliente', $this->agendamento), [
                'cliente_id' => $this->cliente2->id,
            ])
            ->assertOk()
            ->assertJsonStructure(['cliente_id', 'cliente_nome', 'updated_at'])
            ->json();

        expect($data['cliente_id'])->toBe($this->cliente2->id);
        expect($data['cliente_nome'])->toBe('Pedro Novo');
        expect($this->agendamento->fresh()->cliente_id)->toBe($this->cliente2->id);
    });

    it('gestor pode trocar cliente', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('agendamentos.cliente', $this->agendamento), [
                'cliente_id' => $this->cliente2->id,
            ])
            ->assertOk();
    });

    it('analista não pode trocar cliente', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('agendamentos.cliente', $this->agendamento), [
                'cliente_id' => $this->cliente2->id,
            ])
            ->assertForbidden();
    });

    it('rejeita cliente de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-rac', 'plano' => 'trial', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Z', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.cliente', $this->agendamento), [
                'cliente_id' => $cliOutra->id,
            ])
            ->assertStatus(404);
    });

    it('rejeita cliente inativo', function () {
        $inativo = Cliente::create(['company_id' => $this->company->id, 'name' => 'Inativo', 'ativo' => false]);

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.cliente', $this->agendamento), [
                'cliente_id' => $inativo->id,
            ])
            ->assertStatus(404);
    });

    it('rejeita id inválido', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.cliente', $this->agendamento), [
                'cliente_id' => 'nao-existe',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cliente_id']);
    });

    it('não pode alterar agendamento de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra2', 'slug' => 'outra2-rac', 'plano' => 'trial', 'ativo' => true]);
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
            ->patchJson(route('agendamentos.cliente', $agOutra), [
                'cliente_id' => $this->cliente2->id,
            ])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('agendamentos.cliente', $this->agendamento), [
            'cliente_id' => $this->cliente2->id,
        ])
            ->assertUnauthorized();
    });
});
