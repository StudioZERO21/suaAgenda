<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\Servico;
use App\Models\User;
use App\Models\Venda;
use App\Models\VendaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Timeline', 'slug' => 'barbearia-timeline',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id,
        'name' => 'João Cliente',
        'lgpd_consent' => true,
    ]);

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Carlos Barbeiro',
        'ativo' => true,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id,
        'nome' => 'Corte',
        'duracao' => 30,
        'preco' => 50,
        'ativo' => true,
    ]);
});

describe('cliente_timeline', function () {
    it('retorna timeline vazia para cliente sem eventos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.timeline', $this->cliente))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['total', 'items']);
        expect($data['total'])->toBe(0);
        expect($data['items'])->toBeArray()->toHaveCount(0);
    });

    it('inclui agendamentos na timeline', function () {
        Agendamento::create([
            'company_id' => $this->company->id,
            'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->subDay(),
            'duracao' => 30,
            'valor' => 50,
            'status' => Agendamento::STATUS_FINALIZADO,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.timeline', $this->cliente))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['items'][0]['tipo'])->toBe('agendamento');
        expect($data['items'][0])->toHaveKeys(['tipo', 'id', 'data', 'status', 'servico', 'profissional', 'valor']);
    });

    it('inclui compras na timeline', function () {
        $venda = Venda::create([
            'company_id' => $this->company->id,
            'cliente_id' => $this->cliente->id,
            'total' => 80,
            'metodo_pagamento' => 'dinheiro',
            'status' => 'pago',
        ]);
        VendaItem::create([
            'venda_id' => $venda->id,
            'produto_id' => null,
            'descricao' => 'Pomada',
            'qtd' => 1,
            'preco_unit' => 80,
            'total' => 80,
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.timeline', $this->cliente))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['items'][0]['tipo'])->toBe('compra');
        expect($data['items'][0])->toHaveKeys(['tipo', 'id', 'data', 'total', 'itens_count']);
    });

    it('inclui avaliações na timeline', function () {
        $ag = Agendamento::create([
            'company_id' => $this->company->id,
            'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->subDay(),
            'duracao' => 30,
            'valor' => 50,
            'status' => Agendamento::STATUS_FINALIZADO,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        Avaliacao::create([
            'company_id' => $this->company->id,
            'agendamento_id' => $ag->id,
            'nota' => 5,
            'comentario' => 'Excelente!',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.timeline', $this->cliente))
            ->assertOk()
            ->json();

        $tipos = collect($data['items'])->pluck('tipo')->toArray();
        expect($tipos)->toContain('avaliacao');
        expect($tipos)->toContain('agendamento');
    });

    it('não retorna eventos de outro cliente', function () {
        $outro = Cliente::create([
            'company_id' => $this->company->id, 'name' => 'Outro', 'lgpd_consent' => true,
        ]);
        Agendamento::create([
            'company_id' => $this->company->id,
            'cliente_id' => $outro->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->subDay(),
            'duracao' => 30,
            'valor' => 50,
            'status' => Agendamento::STATUS_FINALIZADO,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.timeline', $this->cliente))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar timeline', function () {
        $this->actingAs($this->analista)
            ->getJson(route('clientes.timeline', $this->cliente))
            ->assertOk();
    });

    it('não pode acessar timeline de cliente de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-timeline', 'plano' => 'trial', 'ativo' => true]);
        $clienteOutra = Cliente::create([
            'company_id' => $outra->id, 'name' => 'X', 'lgpd_consent' => false,
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('clientes.timeline', $clienteOutra))
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('clientes.timeline', $this->cliente))
            ->assertUnauthorized();
    });
});
