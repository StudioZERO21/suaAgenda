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
        'name' => 'Barbearia Move', 'slug' => 'barbearia-mv',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create([
        'company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte', 'preco' => 50.0,
        'duracao_minutos' => 30, 'ativo' => true,
    ]);

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id, 'name' => 'João', 'ativo' => true,
    ]);

    $this->agendamento = Agendamento::create([
        'company_id' => $this->company->id,
        'cliente_id' => $this->cliente->id,
        'profissional_id' => $this->prof->id,
        'servico_id' => $this->servico->id,
        'data_hora' => now()->addDay()->setHour(10)->setMinute(0)->setSecond(0)->toDateTimeString(),
        'duracao' => 30,
        'valor' => 50.0,
        'status' => 'pendente',
    ]);
});

describe('agendamento_move', function () {
    it('admin pode mover agendamento para novo horário', function () {
        $novaData = now()->addDays(2)->setHour(14)->setMinute(0)->setSecond(0);

        $data = $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.move', $this->agendamento), [
                'data' => $novaData->format('Y-m-d'),
                'hora' => 14,
                'minuto' => 0,
            ])
            ->assertOk()
            ->assertJsonStructure(['message', 'data_hora', 'data', 'hora', 'minuto', 'hora_label'])
            ->json();

        expect($data['hora'])->toBe(14);
        expect($data['minuto'])->toBe(0);
    });

    it('gestor pode mover agendamento', function () {
        $novaData = now()->addDays(3)->setHour(9)->setMinute(30)->setSecond(0);

        $this->actingAs($this->gestor)
            ->patchJson(route('agendamentos.move', $this->agendamento), [
                'data' => $novaData->format('Y-m-d'),
                'hora' => 9,
                'minuto' => 30,
            ])
            ->assertOk();
    });

    it('analista não pode mover agendamento', function () {
        $novaData = now()->addDays(2)->setHour(11)->setMinute(0)->setSecond(0);

        $this->actingAs($this->analista)
            ->patchJson(route('agendamentos.move', $this->agendamento), [
                'data' => $novaData->format('Y-m-d'),
                'hora' => 11,
                'minuto' => 0,
            ])
            ->assertForbidden();
    });

    it('não pode mover agendamento cancelado', function () {
        $this->agendamento->update(['status' => 'cancelado']);

        $novaData = now()->addDays(2)->setHour(15)->setMinute(0)->setSecond(0);

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.move', $this->agendamento), [
                'data' => $novaData->format('Y-m-d'),
                'hora' => 15,
                'minuto' => 0,
            ])
            ->assertUnprocessable();
    });

    it('rejeita minuto inválido', function () {
        $novaData = now()->addDays(2)->setHour(10)->setMinute(0)->setSecond(0);

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.move', $this->agendamento), [
                'data' => $novaData->format('Y-m-d'),
                'hora' => 10,
                'minuto' => 15,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['minuto']);
    });

    it('não pode mover agendamento de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-mv', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'X', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'ativo' => true]);
        $agOutra = Agendamento::create([
            'company_id' => $outra->id, 'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id, 'servico_id' => $servOutra->id,
            'data_hora' => now()->addDay()->setHour(10)->setMinute(0)->setSecond(0)->toDateTimeString(),
            'duracao' => 30, 'valor' => 50.0, 'status' => 'pendente',
        ]);

        $novaData = now()->addDays(2)->setHour(12)->setMinute(0)->setSecond(0);

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.move', $agOutra), [
                'data' => $novaData->format('Y-m-d'),
                'hora' => 12,
                'minuto' => 0,
            ])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $novaData = now()->addDays(2)->setHour(10)->setMinute(0)->setSecond(0);

        $this->patchJson(route('agendamentos.move', $this->agendamento), [
            'data' => $novaData->format('Y-m-d'),
            'hora' => 10,
            'minuto' => 0,
        ])->assertUnauthorized();
    });
});
