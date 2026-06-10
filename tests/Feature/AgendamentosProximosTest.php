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
        'name' => 'Barbearia Prox', 'slug' => 'barbearia-prox',
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

function makeProxAg($self, string $status, int $minutosAFrente = 60): Agendamento
{
    return Agendamento::create([
        'company_id' => $self->company->id,
        'cliente_id' => $self->cliente->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $self->servico->id,
        'data_hora' => now()->addMinutes($minutosAFrente),
        'duracao' => 30,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('agendamentos_proximos', function () {
    it('retorna estrutura correta', function () {
        makeProxAg($this, Agendamento::STATUS_CONFIRMADO);

        $this->actingAs($this->user)
            ->getJson(route('agendamentos.proximos'))
            ->assertOk()
            ->assertJsonStructure(['total', 'items' => [['id', 'data_hora', 'cliente_nome', 'servico_nome', 'status', 'valor']]]);
    });

    it('retorna apenas agendamentos futuros confirmados e pendentes', function () {
        makeProxAg($this, Agendamento::STATUS_CONFIRMADO, 30);
        makeProxAg($this, Agendamento::STATUS_PENDENTE, 60);
        makeProxAg($this, Agendamento::STATUS_FINALIZADO, 90);
        makeProxAg($this, Agendamento::STATUS_CANCELADO, 120);

        $data = $this->actingAs($this->user)
            ->getJson(route('agendamentos.proximos'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(2);
        expect(collect($data['items'])->pluck('status')->all())->each->toBeIn([
            Agendamento::STATUS_CONFIRMADO, Agendamento::STATUS_PENDENTE,
        ]);
    });

    it('não retorna agendamentos passados', function () {
        Agendamento::create([
            'company_id' => $this->company->id,
            'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->prof->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->subHour(),
            'duracao' => 30,
            'status' => Agendamento::STATUS_CONFIRMADO,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->user)
            ->getJson(route('agendamentos.proximos'))
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('respeita o parâmetro limite', function () {
        for ($i = 1; $i <= 5; $i++) {
            makeProxAg($this, Agendamento::STATUS_CONFIRMADO, $i * 10);
        }

        $data = $this->actingAs($this->user)
            ->getJson(route('agendamentos.proximos', ['limite' => 3]))
            ->json();

        expect($data['total'])->toBe(3);
        expect($data['items'])->toHaveCount(3);
    });

    it('limita a 50 mesmo com limite maior', function () {
        for ($i = 1; $i <= 10; $i++) {
            makeProxAg($this, Agendamento::STATUS_CONFIRMADO, $i * 10);
        }

        $data = $this->actingAs($this->user)
            ->getJson(route('agendamentos.proximos', ['limite' => 100]))
            ->json();

        expect($data['total'])->toBe(10);
    });

    it('não expõe agendamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-prox', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'Y', 'ativo' => true]);
        $svcOutra = Servico::create([
            'company_id' => $outra->id, 'nome' => 'Svc',
            'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#000', 'ativo' => true,
        ]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'X', 'phone' => '99999999999']);

        Agendamento::create([
            'company_id' => $outra->id, 'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id, 'servico_id' => $svcOutra->id,
            'data_hora' => now()->addHour(), 'duracao' => 30,
            'status' => Agendamento::STATUS_CONFIRMADO,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->user)
            ->getJson(route('agendamentos.proximos'))
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('ordena por data_hora crescente', function () {
        makeProxAg($this, Agendamento::STATUS_CONFIRMADO, 120);
        makeProxAg($this, Agendamento::STATUS_CONFIRMADO, 30);
        makeProxAg($this, Agendamento::STATUS_CONFIRMADO, 60);

        $data = $this->actingAs($this->user)
            ->getJson(route('agendamentos.proximos'))
            ->json();

        $horarios = collect($data['items'])->pluck('data_hora')->all();
        $sorted = $horarios;
        sort($sorted);
        expect($horarios)->toBe($sorted);
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('agendamentos.proximos'))
            ->assertUnauthorized();
    });
});
