<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use App\Models\Venda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia CSC', 'slug' => 'barbearia-csc',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof CSC', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte CSC', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->c1 = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente Sem Compra', 'lgpd_consent' => true]);
    $this->c2 = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente Com Compra', 'lgpd_consent' => true]);
});

function makeAgCSC(string $companyId, string $profId, string $clienteId, string $servicoId): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => now()->subDays(5),
        'duracao' => 30,
        'valor' => 50,
        'status' => Agendamento::STATUS_FINALIZADO,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

function makeVendaCSC(string $companyId, string $clienteId): Venda
{
    return Venda::create([
        'company_id' => $companyId,
        'cliente_id' => $clienteId,
        'total' => 30,
        'subtotal' => 30,
        'desconto' => 0,
        'metodo_pagamento' => 'dinheiro',
    ]);
}

describe('pdv_clientes_sem_compra', function () {
    it('retorna estrutura correta sem dados', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.clientes.sem-compra'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo_dias', 'com_agendamento', 'sem_compra', 'items']);
        expect($data['sem_compra'])->toBe(0);
        expect($data['items'])->toBeEmpty();
    });

    it('inclui clientes com agendamento mas sem compra', function () {
        makeAgCSC($this->company->id, $this->prof->id, $this->c1->id, $this->servico->id);
        makeAgCSC($this->company->id, $this->prof->id, $this->c2->id, $this->servico->id);
        makeVendaCSC($this->company->id, $this->c2->id);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.clientes.sem-compra'))
            ->assertOk()
            ->json();

        expect($data['com_agendamento'])->toBe(2);
        expect($data['sem_compra'])->toBe(1);
        expect($data['items'][0]['nome'])->toBe('Cliente Sem Compra');
    });

    it('items têm campos corretos', function () {
        makeAgCSC($this->company->id, $this->prof->id, $this->c1->id, $this->servico->id);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.clientes.sem-compra'))
            ->assertOk()
            ->json();

        expect($data['items'][0])->toHaveKeys(['cliente_id', 'nome', 'phone', 'visitas', 'receita_servicos', 'ultima_visita']);
    });

    it('não inclui clientes sem agendamento', function () {
        makeVendaCSC($this->company->id, $this->c1->id);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.clientes.sem-compra'))
            ->assertOk()
            ->json();

        expect($data['sem_compra'])->toBe(0);
    });

    it('ignora dados de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra CSC', 'slug' => 'outra-csc', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'Prof', 'ativo' => true]);
        $srvOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'Srv', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Cli', 'lgpd_consent' => true]);
        makeAgCSC($outra->id, $profOutra->id, $cliOutra->id, $srvOutra->id);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.clientes.sem-compra'))
            ->assertOk()
            ->json();

        expect($data['sem_compra'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('pdv.clientes.sem-compra'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('pdv.clientes.sem-compra'))
            ->assertUnauthorized();
    });
});
