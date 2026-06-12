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
        'name' => 'Barbearia AgHoje', 'slug' => 'barbearia-aghoje',
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

function makeHojeAg($self, string $status = 'confirmado', bool $hoje = true): Agendamento
{
    return Agendamento::create([
        'company_id' => $self->company->id,
        'cliente_id' => $self->cliente->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $self->servico->id,
        'data_hora' => $hoje ? today()->setTime(10, 0) : now()->subDays(2)->setTime(10, 0),
        'duracao' => 30,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('agendamentos_hoje', function () {
    it('retorna estrutura correta', function () {
        makeHojeAg($this);

        $this->actingAs($this->user)
            ->getJson(route('agendamentos.hoje'))
            ->assertOk()
            ->assertJsonStructure([
                'total',
                'items' => [['id', 'data_hora', 'cliente_nome', 'servico_nome', 'profissional_nome', 'status', 'valor', 'duracao']],
            ]);
    });

    it('retorna somente agendamentos de hoje', function () {
        makeHojeAg($this, 'confirmado', true);
        makeHojeAg($this, 'confirmado', false);

        $data = $this->actingAs($this->user)
            ->getJson(route('agendamentos.hoje'))
            ->json();

        expect($data['total'])->toBe(1);
    });

    it('filtra por status quando fornecido', function () {
        makeHojeAg($this, 'confirmado');
        makeHojeAg($this, 'finalizado');
        makeHojeAg($this, 'cancelado');

        $data = $this->actingAs($this->user)
            ->getJson(route('agendamentos.hoje', ['status' => 'confirmado']))
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['items'][0]['status'])->toBe('confirmado');
    });

    it('retorna todos os status sem filtro', function () {
        makeHojeAg($this, 'confirmado');
        makeHojeAg($this, 'finalizado');
        makeHojeAg($this, 'cancelado');

        $data = $this->actingAs($this->user)
            ->getJson(route('agendamentos.hoje'))
            ->json();

        expect($data['total'])->toBe(3);
    });

    it('não expõe dados de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-aghoje', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $svcOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'S', 'duracao_minutos' => 30, 'preco' => 30.0, 'cor' => '#000', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Z', 'phone' => '99999999999']);
        Agendamento::create([
            'company_id' => $outra->id, 'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id, 'servico_id' => $svcOutra->id,
            'data_hora' => today()->setTime(11, 0), 'duracao' => 30,
            'status' => 'confirmado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->user)
            ->getJson(route('agendamentos.hoje'))
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('agendamentos.hoje'))
            ->assertUnauthorized();
    });
});
