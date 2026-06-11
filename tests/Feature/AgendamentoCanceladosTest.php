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
        'name' => 'Barbearia Cancel', 'slug' => 'barbearia-cancel',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Pedro', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Marcos', 'phone' => '11999990011']);
});

describe('agendamentos_cancelados', function () {
    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.cancelados'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['total', 'items']);
    });

    it('retorna apenas cancelados recentes', function () {
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->subDays(5),
            'duracao' => 30,
            'valor' => 50.0,
            'status' => 'cancelado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->subDays(5),
            'duracao' => 30,
            'status' => 'confirmado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.cancelados'))
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['items'][0])->toHaveKeys(['id', 'data_hora', 'cliente_nome', 'servico_nome', 'profissional_nome', 'valor', 'duracao']);
    });

    it('não retorna cancelados fora do período', function () {
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->subDays(60),
            'duracao' => 30,
            'status' => 'cancelado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.cancelados'))
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('não retorna cancelados de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-cancel', 'plano' => 'trial', 'ativo' => true]);
        Agendamento::create([
            'company_id' => $outra->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->subDay(),
            'duracao' => 30,
            'status' => 'cancelado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.cancelados'))
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode ver cancelados', function () {
        $this->actingAs($this->analista)
            ->getJson(route('agendamentos.cancelados'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('agendamentos.cancelados'))
            ->assertUnauthorized();
    });
});
