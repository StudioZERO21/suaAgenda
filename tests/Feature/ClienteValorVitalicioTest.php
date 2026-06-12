<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\Servico;
use App\Models\User;
use App\Models\Venda;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia LTV', 'slug' => 'barbearia-ltv',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana', 'lgpd_consent' => true]);
});

describe('cliente_valor_vitalicio', function () {
    it('retorna zeros para cliente sem histórico', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.valor-vitalicio', $this->cliente))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['cliente_id', 'cliente_nome', 'ltv_total', 'receita_agendamentos', 'total_agendamentos', 'receita_vendas', 'total_vendas', 'primeira_interacao']);
        expect((float) $data['ltv_total'])->toBe(0.0);
        expect($data['total_agendamentos'])->toBe(0);
        expect($data['total_vendas'])->toBe(0);
    });

    it('soma agendamentos finalizados no LTV', function () {
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->subDays(10),
            'duracao' => 30,
            'valor' => 150.0,
            'status' => Agendamento::STATUS_FINALIZADO,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.valor-vitalicio', $this->cliente))
            ->assertOk()
            ->json();

        expect((float) $data['receita_agendamentos'])->toBe(150.0);
        expect($data['total_agendamentos'])->toBe(1);
        expect((float) $data['ltv_total'])->toBe(150.0);
    });

    it('soma vendas PDV no LTV', function () {
        Venda::create([
            'company_id' => $this->company->id,
            'cliente_id' => $this->cliente->id,
            'subtotal' => 80.0, 'desconto' => 0, 'total' => 80.0,
            'metodo_pagamento' => 'pix',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.valor-vitalicio', $this->cliente))
            ->assertOk()
            ->json();

        expect((float) $data['receita_vendas'])->toBe(80.0);
        expect($data['total_vendas'])->toBe(1);
        expect((float) $data['ltv_total'])->toBe(80.0);
    });

    it('combina agendamentos e vendas no LTV', function () {
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->subDays(5),
            'duracao' => 30,
            'valor' => 200.0,
            'status' => Agendamento::STATUS_FINALIZADO,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);
        Venda::create([
            'company_id' => $this->company->id,
            'cliente_id' => $this->cliente->id,
            'subtotal' => 50.0, 'desconto' => 0, 'total' => 50.0,
            'metodo_pagamento' => 'dinheiro',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.valor-vitalicio', $this->cliente))
            ->assertOk()
            ->json();

        expect((float) $data['ltv_total'])->toBe(250.0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('clientes.valor-vitalicio', $this->cliente))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('clientes.valor-vitalicio', $this->cliente))
            ->assertUnauthorized();
    });
});
