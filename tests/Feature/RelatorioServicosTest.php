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

    $this->company = Company::create([
        'name' => 'Barbearia Svc', 'slug' => 'barbearia-svc',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'phone' => '11999990001']);

    $this->corte = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);

    $this->barba = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Barba',
        'duracao_minutos' => 20, 'preco' => 30.00, 'cor' => '#d4a574', 'ativo' => true,
    ]);
});

function makeSvcAg($self, Servico $servico, string $status = 'finalizado', float $valor = 50.0, int $diasAtras = 5): void
{
    Agendamento::create([
        'company_id' => $self->company->id,
        'cliente_id' => $self->cliente->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $servico->id,
        'data_hora' => now()->subDays($diasAtras),
        'duracao' => 30,
        'status' => $status,
        'valor' => $valor,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('relatorio_servicos', function () {
    it('retorna estrutura correta', function () {
        makeSvcAg($this, $this->corte, 'finalizado', 50.0);

        $this->actingAs($this->user)
            ->getJson(route('relatorios.servicos'))
            ->assertOk()
            ->assertJsonStructure([['nome', 'cor', 'total_agendamentos', 'receita_total', 'ticket_medio']]);
    });

    it('retorna apenas agendamentos finalizados', function () {
        makeSvcAg($this, $this->corte, 'finalizado', 50.0);
        makeSvcAg($this, $this->corte, 'pendente', 50.0);
        makeSvcAg($this, $this->corte, 'cancelado', 50.0);

        $data = $this->actingAs($this->user)
            ->getJson(route('relatorios.servicos'))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(1);
        expect($data[0]['total_agendamentos'])->toBe(1);
        expect((float) $data[0]['receita_total'])->toBe(50.0);
    });

    it('ordena por receita decrescente', function () {
        makeSvcAg($this, $this->corte, 'finalizado', 50.0);
        makeSvcAg($this, $this->corte, 'finalizado', 50.0);
        makeSvcAg($this, $this->barba, 'finalizado', 200.0);

        $data = $this->actingAs($this->user)
            ->getJson(route('relatorios.servicos'))
            ->assertOk()
            ->json();

        expect($data[0]['nome'])->toBe('Barba');
        expect($data[1]['nome'])->toBe('Corte');
    });

    it('calcula ticket_medio corretamente', function () {
        makeSvcAg($this, $this->corte, 'finalizado', 80.0);
        makeSvcAg($this, $this->corte, 'finalizado', 120.0);

        $data = $this->actingAs($this->user)
            ->getJson(route('relatorios.servicos'))
            ->assertOk()
            ->json();

        expect($data[0]['total_agendamentos'])->toBe(2);
        expect((float) $data[0]['receita_total'])->toBe(200.0);
        expect((float) $data[0]['ticket_medio'])->toBe(100.0);
    });

    it('retorna lista vazia sem agendamentos finalizados', function () {
        $data = $this->actingAs($this->user)
            ->getJson(route('relatorios.servicos'))
            ->assertOk()
            ->json();

        expect($data)->toBeEmpty();
    });

    it('não expõe dados de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-svc', 'plano' => 'trial', 'ativo' => true]);
        $svcOutra = Servico::create([
            'company_id' => $outra->id, 'nome' => 'Serviço Intruso',
            'duracao_minutos' => 30, 'preco' => 999.0, 'cor' => '#ff0000', 'ativo' => true,
        ]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'X', 'phone' => '99999999999']);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'Y', 'ativo' => true]);

        Agendamento::create([
            'company_id' => $outra->id, 'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id, 'servico_id' => $svcOutra->id,
            'data_hora' => now()->subDays(3), 'duracao' => 30,
            'status' => 'finalizado', 'valor' => 999.0,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->user)
            ->getJson(route('relatorios.servicos'))
            ->assertOk()
            ->json();

        $nomes = array_column($data, 'nome');
        expect($nomes)->not->toContain('Serviço Intruso');
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('relatorios.servicos'))
            ->assertUnauthorized();
    });
});
