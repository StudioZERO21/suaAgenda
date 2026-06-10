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

    $this->company = Company::create([
        'name' => 'Barbearia AgBuscar', 'slug' => 'barbearia-agbuscar',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte Clássico',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João Silva', 'phone' => '11999990001']);

    $this->agendamento = Agendamento::create([
        'company_id' => $this->company->id,
        'cliente_id' => $this->cliente->id,
        'profissional_id' => $this->prof->id,
        'servico_id' => $this->servico->id,
        'data_hora' => now()->subDays(2),
        'duracao' => 30,
        'status' => 'finalizado',
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
});

describe('agendamento_buscar', function () {
    it('retorna vazio quando q é vazio', function () {
        $this->actingAs($this->user)
            ->getJson(route('agendamentos.buscar'))
            ->assertOk()
            ->assertJson([]);
    });

    it('retorna agendamentos por nome do cliente', function () {
        $data = $this->actingAs($this->user)
            ->getJson(route('agendamentos.buscar', ['q' => 'João']))
            ->assertOk()
            ->json();

        expect(count($data))->toBe(1);
        expect($data[0]['cliente_nome'])->toBe('João Silva');
    });

    it('retorna agendamentos por nome do serviço', function () {
        $data = $this->actingAs($this->user)
            ->getJson(route('agendamentos.buscar', ['q' => 'Corte']))
            ->assertOk()
            ->json();

        expect(count($data))->toBe(1);
        expect($data[0]['servico_nome'])->toBe('Corte Clássico');
    });

    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->user)
            ->getJson(route('agendamentos.buscar', ['q' => 'João']))
            ->json();

        expect($data[0])->toHaveKeys(['id', 'data_hora', 'cliente_nome', 'servico_nome', 'profissional_nome', 'status', 'valor']);
    });

    it('não retorna agendamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-agbuscar', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $svcOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 30.0, 'cor' => '#000', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'João Outra', 'phone' => '99999999999']);
        Agendamento::create([
            'company_id' => $outra->id, 'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id, 'servico_id' => $svcOutra->id,
            'data_hora' => now()->subDays(1), 'duracao' => 30,
            'status' => 'finalizado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->user)
            ->getJson(route('agendamentos.buscar', ['q' => 'Outra']))
            ->json();

        expect($data)->toBeEmpty();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('agendamentos.buscar', ['q' => 'João']))
            ->assertUnauthorized();
    });
});
