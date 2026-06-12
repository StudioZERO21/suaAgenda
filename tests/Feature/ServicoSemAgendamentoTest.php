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
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia SA', 'slug' => 'barbearia-sa',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof SA', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente SA', 'lgpd_consent' => true]);
});

function makeServicoSA(string $companyId, string $nome, bool $ativo = true): Servico
{
    return Servico::create([
        'company_id' => $companyId,
        'nome' => $nome,
        'duracao_minutos' => 30,
        'preco' => 50,
        'ativo' => $ativo,
    ]);
}

function makeAgSA(string $companyId, string $profId, string $clienteId, string $servicoId, string $status = 'finalizado'): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => now()->subDays(5),
        'duracao' => 30,
        'valor' => 50,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('servico_sem_agendamento', function () {
    it('retorna estrutura correta sem serviços', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.sem-agendamento'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo_dias', 'total_servicos', 'sem_agendamento', 'items']);
        expect($data['total_servicos'])->toBe(0);
        expect($data['sem_agendamento'])->toBe(0);
    });

    it('lista serviços sem agendamento no período', function () {
        $s1 = makeServicoSA($this->company->id, 'Corte SA');
        $s2 = makeServicoSA($this->company->id, 'Hidratação SA');
        makeAgSA($this->company->id, $this->prof->id, $this->cliente->id, $s1->id);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.sem-agendamento'))
            ->assertOk()
            ->json();

        expect($data['total_servicos'])->toBe(2);
        expect($data['sem_agendamento'])->toBe(1);
        expect($data['items'][0]['nome'])->toBe('Hidratação SA');
    });

    it('items têm campos corretos', function () {
        makeServicoSA($this->company->id, 'Barba SA');

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.sem-agendamento'))
            ->assertOk()
            ->json();

        $item = $data['items'][0];
        expect($item)->toHaveKeys(['id', 'nome', 'cor', 'preco', 'duracao_minutos', 'ativo', 'criado_em']);
        expect($item['ativo'])->toBeTrue();
    });

    it('agendamentos cancelados não contam como agendado', function () {
        $s1 = makeServicoSA($this->company->id, 'Escova SA');
        makeAgSA($this->company->id, $this->prof->id, $this->cliente->id, $s1->id, Agendamento::STATUS_CANCELADO);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.sem-agendamento'))
            ->assertOk()
            ->json();

        expect($data['sem_agendamento'])->toBe(1);
    });

    it('ignora serviços de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra SA', 'slug' => 'outra-sa', 'plano' => 'trial', 'ativo' => true]);
        makeServicoSA($outra->id, 'Serv Outra');

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.sem-agendamento'))
            ->assertOk()
            ->json();

        expect($data['total_servicos'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('servicos.sem-agendamento'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('servicos.sem-agendamento'))
            ->assertUnauthorized();
    });
});
