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
        'name' => 'Barbearia RelCli', 'slug' => 'barbearia-relcli',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);
    $this->cli1 = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana', 'phone' => '11999990001']);
    $this->cli2 = Cliente::create(['company_id' => $this->company->id, 'name' => 'Bruno', 'phone' => '11999990002']);
});

function makeRelCli($self, Cliente $cliente, string $status = 'finalizado', float $valor = 50.0, int $diasAtras = 5): Agendamento
{
    return Agendamento::create([
        'company_id' => $self->company->id,
        'cliente_id' => $cliente->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $self->servico->id,
        'data_hora' => now()->subDays($diasAtras),
        'duracao' => 30,
        'status' => $status,
        'valor' => $valor,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('relatorio_clientes', function () {
    it('retorna estrutura correta', function () {
        makeRelCli($this, $this->cli1);

        $data = $this->actingAs($this->user)
            ->getJson(route('relatorios.clientes'))
            ->assertOk()
            ->json();

        expect($data[0])->toHaveKeys(['id', 'name', 'phone', 'total_agendamentos', 'finalizados', 'receita_total', 'ticket_medio']);
    });

    it('ordena por receita descendente', function () {
        makeRelCli($this, $this->cli1, 'finalizado', 200.0);
        makeRelCli($this, $this->cli2, 'finalizado', 100.0);

        $data = $this->actingAs($this->user)
            ->getJson(route('relatorios.clientes'))
            ->assertOk()
            ->json();

        expect($data[0]['name'])->toBe('Ana');
        expect($data[1]['name'])->toBe('Bruno');
    });

    it('exclui agendamentos cancelados', function () {
        makeRelCli($this, $this->cli1, 'cancelado', 300.0);
        makeRelCli($this, $this->cli2, 'finalizado', 100.0);

        $data = $this->actingAs($this->user)
            ->getJson(route('relatorios.clientes'))
            ->json();

        expect(count($data))->toBe(1);
        expect($data[0]['name'])->toBe('Bruno');
    });

    it('respeita limite customizado', function () {
        makeRelCli($this, $this->cli1);
        makeRelCli($this, $this->cli2);

        $data = $this->actingAs($this->user)
            ->getJson(route('relatorios.clientes', ['limite' => 1]))
            ->json();

        expect(count($data))->toBe(1);
    });

    it('não expõe dados de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-relcli', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $svcOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'S', 'duracao_minutos' => 30, 'preco' => 30.0, 'cor' => '#000', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Z', 'phone' => '99999999999']);
        Agendamento::create([
            'company_id' => $outra->id, 'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id, 'servico_id' => $svcOutra->id,
            'data_hora' => now()->subDays(2), 'duracao' => 30,
            'status' => 'finalizado', 'valor' => 500.0,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->user)
            ->getJson(route('relatorios.clientes'))
            ->json();

        expect($data)->toBeEmpty();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('relatorios.clientes'))
            ->assertUnauthorized();
    });
});
