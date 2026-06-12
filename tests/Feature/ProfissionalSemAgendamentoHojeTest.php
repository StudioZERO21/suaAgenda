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
        'name' => 'Barbearia SAH', 'slug' => 'barbearia-sah',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte SAH', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente SAH', 'lgpd_consent' => true]);
});

describe('profissional_sem_agendamento_hoje', function () {
    it('retorna estrutura correta sem profissionais', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.sem-agendamento-hoje'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['data', 'total_ativos', 'com_agendamento', 'sem_agendamento', 'items']);
        expect($data['total_ativos'])->toBe(0);
        expect($data['sem_agendamento'])->toBe(0);
    });

    it('lista profissionais sem agendamento hoje', function () {
        $p1 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof Livre SAH', 'ativo' => true]);
        $p2 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof Ocupado SAH', 'ativo' => true]);

        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $p2->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => today()->setTime(10, 0),
            'duracao' => 30,
            'valor' => 50,
            'status' => Agendamento::STATUS_CONFIRMADO,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.sem-agendamento-hoje'))
            ->assertOk()
            ->json();

        expect($data['total_ativos'])->toBe(2);
        expect($data['com_agendamento'])->toBe(1);
        expect($data['sem_agendamento'])->toBe(1);
        expect($data['items'][0]['profissional_nome'])->toBe('Prof Livre SAH');
    });

    it('agendamento cancelado não conta como agendado', function () {
        $prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof Canc SAH', 'ativo' => true]);

        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => today()->setTime(11, 0),
            'duracao' => 30,
            'valor' => 50,
            'status' => Agendamento::STATUS_CANCELADO,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.sem-agendamento-hoje'))
            ->assertOk()
            ->json();

        expect($data['sem_agendamento'])->toBe(1);
    });

    it('exclui profissionais inativos', function () {
        Profissional::create(['company_id' => $this->company->id, 'name' => 'Inativo SAH', 'ativo' => false]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.sem-agendamento-hoje'))
            ->assertOk()
            ->json();

        expect($data['total_ativos'])->toBe(0);
    });

    it('ignora profissionais de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra SAH', 'slug' => 'outra-sah', 'plano' => 'trial', 'ativo' => true]);
        Profissional::create(['company_id' => $outra->id, 'name' => 'Outro SAH', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.sem-agendamento-hoje'))
            ->assertOk()
            ->json();

        expect($data['total_ativos'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('profissionais.sem-agendamento-hoje'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('profissionais.sem-agendamento-hoje'))
            ->assertUnauthorized();
    });
});
