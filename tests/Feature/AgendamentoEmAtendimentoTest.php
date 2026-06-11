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
        'name' => 'Barbearia EmAt', 'slug' => 'barbearia-emat',
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
        'company_id' => $this->company->id, 'name' => 'Marcos', 'phone' => '11999990033',
    ]);
});

function makeEmAtAg($self, string $status): Agendamento
{
    return Agendamento::create([
        'company_id' => $self->company->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $self->servico->id,
        'cliente_id' => $self->cliente->id,
        'data_hora' => now()->subMinutes(15),
        'duracao' => 30,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('agendamento_em_atendimento', function () {
    it('retorna apenas agendamentos em atendimento', function () {
        makeEmAtAg($this, Agendamento::STATUS_EM_ATENDIMENTO);
        makeEmAtAg($this, Agendamento::STATUS_CONFIRMADO);
        makeEmAtAg($this, Agendamento::STATUS_PENDENTE);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.em-atendimento'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
    });

    it('retorna estrutura correta', function () {
        makeEmAtAg($this, Agendamento::STATUS_EM_ATENDIMENTO);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.em-atendimento'))
            ->json();

        expect($data['items'][0])->toHaveKeys([
            'id', 'data_hora', 'cliente_nome', 'cliente_phone',
            'servico_nome', 'profissional_nome', 'profissional_id', 'duracao',
        ]);
    });

    it('retorna lista vazia quando ninguém em atendimento', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.em-atendimento'))
            ->json();

        expect($data['total'])->toBe(0);
        expect($data['items'])->toBeEmpty();
    });

    it('não retorna atendimentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-emat', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $svcOutra = Servico::create([
            'company_id' => $outra->id, 'nome' => 'S', 'duracao_minutos' => 30, 'preco' => 10.0, 'cor' => '#000', 'ativo' => true,
        ]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'phone' => '99999999999']);
        Agendamento::create([
            'company_id' => $outra->id, 'profissional_id' => $profOutra->id,
            'servico_id' => $svcOutra->id, 'cliente_id' => $cliOutra->id,
            'data_hora' => now()->subMinutes(5), 'duracao' => 30,
            'status' => Agendamento::STATUS_EM_ATENDIMENTO,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.em-atendimento'))
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode ver em atendimento', function () {
        $this->actingAs($this->analista)
            ->getJson(route('agendamentos.em-atendimento'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('agendamentos.em-atendimento'))
            ->assertUnauthorized();
    });
});
