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
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Agenda', 'slug' => 'barbearia-agenda',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'P', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'S', 'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'C', 'phone' => '11999990001']);
});

describe('agendamento_agenda', function () {
    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.agenda'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['dias', 'total', 'dias_com_agendamentos', 'agenda']);
    });

    it('retorna agendamentos futuros pendentes e confirmados', function () {
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->addDay(),
            'duracao' => 30,
            'status' => 'confirmado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->addDay(),
            'duracao' => 30,
            'status' => 'cancelado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.agenda'))
            ->json();

        expect($data['total'])->toBe(1);
    });

    it('agrupa por data', function () {
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->addDay()->setTime(9, 0),
            'duracao' => 30,
            'status' => 'confirmado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->addDays(2)->setTime(10, 0),
            'duracao' => 30,
            'status' => 'pendente',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.agenda'))
            ->json();

        expect($data['dias_com_agendamentos'])->toBe(2);
        expect($data['agenda'][0])->toHaveKeys(['data', 'total', 'items']);
        expect($data['agenda'][0]['items'][0])->toHaveKeys(['id', 'hora', 'cliente_nome', 'servico_nome', 'profissional_nome', 'status', 'duracao']);
    });

    it('não retorna agendamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-agenda', 'plano' => 'trial', 'ativo' => true]);
        Agendamento::create([
            'company_id' => $outra->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->addDay(),
            'duracao' => 30,
            'status' => 'confirmado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.agenda'))
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode ver agenda', function () {
        $this->actingAs($this->analista)
            ->getJson(route('agendamentos.agenda'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('agendamentos.agenda'))
            ->assertUnauthorized();
    });
});
