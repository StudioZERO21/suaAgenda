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
        'name' => 'Barbearia Pendentes', 'slug' => 'barbearia-pendentes',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true,
    ]);
    $this->cliente = Cliente::create([
        'company_id' => $this->company->id, 'name' => 'João', 'phone' => '11999990011',
    ]);
});

function makeAgPendRoute($self, string $status, bool $futuro = true): Agendamento
{
    return Agendamento::create([
        'company_id' => $self->company->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $self->servico->id,
        'cliente_id' => $self->cliente->id,
        'data_hora' => $futuro ? now()->addDay()->setTime(10, 0) : now()->subHour(),
        'duracao' => 30,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('agendamento_pendentes', function () {
    it('retorna apenas pendentes futuros', function () {
        makeAgPendRoute($this, Agendamento::STATUS_PENDENTE);
        makeAgPendRoute($this, Agendamento::STATUS_CONFIRMADO);
        makeAgPendRoute($this, Agendamento::STATUS_CANCELADO);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.pendentes'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
    });

    it('não retorna pendente no passado', function () {
        makeAgPendRoute($this, Agendamento::STATUS_PENDENTE, false);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.pendentes'))
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('retorna estrutura correta', function () {
        makeAgPendRoute($this, Agendamento::STATUS_PENDENTE);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.pendentes'))
            ->json();

        expect($data['items'][0])->toHaveKeys([
            'id', 'data_hora', 'cliente_nome', 'cliente_phone',
            'servico_nome', 'profissional_nome', 'valor', 'duracao', 'cancel_token',
        ]);
    });

    it('ordena por data_hora crescente', function () {
        makeAgPendRoute($this, Agendamento::STATUS_PENDENTE);
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'servico_id' => $this->servico->id,
            'cliente_id' => $this->cliente->id,
            'data_hora' => now()->addDays(3)->setTime(10, 0),
            'duracao' => 30,
            'status' => Agendamento::STATUS_PENDENTE,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.pendentes'))
            ->json();

        $datas = collect($data['items'])->pluck('data_hora')->all();
        expect($datas[0] < $datas[1])->toBeTrue();
    });

    it('não retorna agendamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-pend', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $svcOutra = Servico::create([
            'company_id' => $outra->id, 'nome' => 'S', 'duracao_minutos' => 30, 'preco' => 10.0, 'cor' => '#000', 'ativo' => true,
        ]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'phone' => '99999999999']);
        Agendamento::create([
            'company_id' => $outra->id, 'profissional_id' => $profOutra->id,
            'servico_id' => $svcOutra->id, 'cliente_id' => $cliOutra->id,
            'data_hora' => now()->addDay(), 'duracao' => 30,
            'status' => Agendamento::STATUS_PENDENTE, 'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.pendentes'))
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode ver pendentes', function () {
        $this->actingAs($this->analista)
            ->getJson(route('agendamentos.pendentes'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('agendamentos.pendentes'))
            ->assertUnauthorized();
    });
});
