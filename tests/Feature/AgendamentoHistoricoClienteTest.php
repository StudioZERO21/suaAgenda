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
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia HistCli', 'slug' => 'barbearia-histcli',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'P', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Felipe', 'phone' => '11999990044']);

    $this->agendamento = Agendamento::create([
        'company_id' => $this->company->id,
        'profissional_id' => $this->prof->id,
        'cliente_id' => $this->cliente->id,
        'servico_id' => $this->servico->id,
        'data_hora' => now(),
        'duracao' => 30,
        'status' => 'confirmado',
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
});

describe('agendamento_historico_cliente', function () {
    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.historico-cliente', $this->agendamento))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['cliente_id', 'total', 'items']);
    });

    it('retorna histórico excluindo o agendamento atual', function () {
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->subDays(10),
            'duracao' => 30,
            'status' => 'finalizado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.historico-cliente', $this->agendamento))
            ->json();

        expect($data['total'])->toBe(1);
        $ids = collect($data['items'])->pluck('id')->all();
        expect($ids)->not->toContain($this->agendamento->id);
    });

    it('retorna vazio quando sem histórico', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.historico-cliente', $this->agendamento))
            ->json();

        expect($data['total'])->toBe(0);
        expect($data['items'])->toBeEmpty();
    });

    it('retorna estrutura de item correta', function () {
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->subDays(5),
            'duracao' => 30,
            'status' => 'finalizado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.historico-cliente', $this->agendamento))
            ->json();

        expect($data['items'][0])->toHaveKeys(['id', 'data_hora', 'servico_nome', 'profissional_nome', 'status', 'valor', 'nota']);
    });

    it('não acessa agendamento de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-histcli', 'plano' => 'trial', 'ativo' => true]);
        $agOutra = Agendamento::create([
            'company_id' => $outra->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now(),
            'duracao' => 30,
            'status' => 'pendente',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('agendamentos.historico-cliente', $agOutra))
            ->assertForbidden();
    });

    it('analista pode ver histórico', function () {
        $this->actingAs($this->analista)
            ->getJson(route('agendamentos.historico-cliente', $this->agendamento))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('agendamentos.historico-cliente', $this->agendamento))
            ->assertUnauthorized();
    });
});
