<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Lancamento;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia FinResumo', 'slug' => 'barbearia-finresumo',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'phone' => '11999990001']);
});

function makeFinAg($self, string $status = 'finalizado', float $valor = 100.0): Agendamento
{
    return Agendamento::create([
        'company_id' => $self->company->id,
        'cliente_id' => $self->cliente->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $self->servico->id,
        'data_hora' => today()->setTime(10, 0),
        'duracao' => 30,
        'status' => $status,
        'valor' => $valor,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

function makeFinLanc($self, string $tipo = 'receita', float $valor = 50.0, string $status = 'pago'): Lancamento
{
    return Lancamento::create([
        'company_id' => $self->company->id,
        'tipo' => $tipo,
        'descricao' => 'Lançamento teste',
        'valor' => $valor,
        'data' => today()->format('Y-m-d'),
        'status' => $status,
        'categoria' => 'teste',
    ]);
}

describe('financeiro_resumo', function () {
    it('retorna estrutura correta', function () {
        $this->actingAs($this->user)
            ->getJson(route('financeiro.resumo'))
            ->assertOk()
            ->assertJsonStructure([
                'periodo', 'receita_agendamentos', 'receita_lancamentos',
                'receita_bruta', 'despesas', 'lucro_liquido',
                'total_finalizados', 'ticket_medio',
            ]);
    });

    it('calcula receita de agendamentos corretamente', function () {
        makeFinAg($this, 'finalizado', 100.0);
        makeFinAg($this, 'finalizado', 200.0);
        makeFinAg($this, 'cancelado', 300.0);

        $data = $this->actingAs($this->user)
            ->getJson(route('financeiro.resumo'))
            ->json();

        expect((float) $data['receita_agendamentos'])->toBe(300.0);
        expect($data['total_finalizados'])->toBe(2);
    });

    it('calcula despesas e lucro líquido corretamente', function () {
        makeFinAg($this, 'finalizado', 200.0);
        makeFinLanc($this, 'despesa', 50.0);

        $data = $this->actingAs($this->user)
            ->getJson(route('financeiro.resumo'))
            ->json();

        expect((float) $data['despesas'])->toBe(50.0);
        expect((float) $data['lucro_liquido'])->toBe(150.0);
    });

    it('retorna zeros quando não há movimentação', function () {
        $data = $this->actingAs($this->user)
            ->getJson(route('financeiro.resumo'))
            ->json();

        expect((float) $data['receita_bruta'])->toBe(0.0);
        expect((float) $data['despesas'])->toBe(0.0);
        expect((float) $data['lucro_liquido'])->toBe(0.0);
        expect($data['total_finalizados'])->toBe(0);
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('financeiro.resumo'))
            ->assertUnauthorized();
    });
});
