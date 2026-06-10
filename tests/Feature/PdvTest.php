<?php

declare(strict_types=1);

use App\Models\Cliente;
use App\Models\Company;
use App\Models\Lancamento;
use App\Models\Produto;
use App\Models\Servico;
use App\Models\User;
use App\Models\Venda;
use App\Models\VendaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'PDV Barbearia',
        'slug' => 'pdv-barbearia',
        'plano' => 'starter',
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->produto = Produto::create([
        'company_id' => $this->company->id,
        'nome' => 'Shampoo',
        'preco' => 25.00,
        'custo' => 10.00,
        'estoque' => 10,
        'ativo' => true,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id,
        'nome' => 'Corte',
        'duracao_minutos' => 30,
        'preco' => 40.00,
        'ativo' => true,
    ]);
});

$salePayload = fn (array $merge = []) => array_merge([
    'items' => [
        ['id' => '', 'type' => 'product', 'name' => 'Shampoo', 'price' => 25.00, 'qty' => 1],
    ],
    'subtotal' => 25.00,
    'desconto' => 0,
    'total' => 25.00,
    'metodo_pagamento' => 'dinheiro',
], $merge);

describe('pdv_export_csv', function () {
    it('admin pode exportar vendas em CSV', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('pdv.exportar'));

        $response->assertOk();
        expect($response->headers->get('content-type'))->toContain('text/csv');
    });

    it('CSV de vendas contém cabeçalhos corretos', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('pdv.exportar'));

        $content = $response->streamedContent();
        expect($content)->toContain('Data')
            ->and($content)->toContain('Cliente')
            ->and($content)->toContain('Total');
    });

    it('unauthenticated é redirecionado', function () {
        $this->get(route('pdv.exportar'))->assertRedirect();
    });
});

describe('pdv_index', function () {
    it('admin acessa o PDV', function () {
        $this->actingAs($this->admin)
            ->get(route('pdv'))
            ->assertOk()
            ->assertViewIs('pdv.index');
    });

    it('gestor acessa o PDV', function () {
        $this->actingAs($this->gestor)
            ->get(route('pdv'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->get(route('pdv'))->assertRedirect();
    });
});

describe('pdv_store', function () use (&$salePayload) {
    it('admin pode registrar venda de produto', function () use (&$salePayload) {
        $payload = $salePayload(['items' => [
            ['id' => $this->produto->id, 'type' => 'product', 'name' => 'Shampoo', 'price' => 25.00, 'qty' => 2],
        ], 'subtotal' => 50.00, 'total' => 50.00]);

        $this->actingAs($this->admin)
            ->postJson(route('pdv.store'), $payload)
            ->assertCreated()
            ->assertJson(['ok' => true]);

        expect(Venda::where('company_id', $this->company->id)->count())->toBe(1);
    });

    it('gestor pode registrar venda', function () use (&$salePayload) {
        $payload = $salePayload(['items' => [
            ['id' => $this->produto->id, 'type' => 'product', 'name' => 'Shampoo', 'price' => 25.00, 'qty' => 1],
        ]]);

        $this->actingAs($this->gestor)
            ->postJson(route('pdv.store'), $payload)
            ->assertCreated();
    });

    it('venda de produto decrementa estoque', function () use (&$salePayload) {
        $payload = $salePayload(['items' => [
            ['id' => $this->produto->id, 'type' => 'product', 'name' => 'Shampoo', 'price' => 25.00, 'qty' => 3],
        ], 'subtotal' => 75.00, 'total' => 75.00]);

        $this->actingAs($this->admin)
            ->postJson(route('pdv.store'), $payload)
            ->assertCreated();

        expect($this->produto->fresh()->estoque)->toBe(7);
    });

    it('venda cria VendaItens corretos', function () use (&$salePayload) {
        $payload = $salePayload(['items' => [
            ['id' => $this->produto->id, 'type' => 'product', 'name' => 'Shampoo', 'price' => 25.00, 'qty' => 1],
            ['id' => $this->servico->id, 'type' => 'service', 'name' => 'Corte', 'price' => 40.00, 'qty' => 1],
        ], 'subtotal' => 65.00, 'total' => 65.00]);

        $this->actingAs($this->admin)
            ->postJson(route('pdv.store'), $payload);

        $venda = Venda::where('company_id', $this->company->id)->first();
        expect(VendaItem::where('venda_id', $venda->id)->count())->toBe(2);
    });

    it('venda cria lancamento financeiro automaticamente', function () use (&$salePayload) {
        $payload = $salePayload(['items' => [
            ['id' => $this->produto->id, 'type' => 'product', 'name' => 'Shampoo', 'price' => 25.00, 'qty' => 1],
        ]]);

        $this->actingAs($this->admin)
            ->postJson(route('pdv.store'), $payload);

        expect(Lancamento::where('company_id', $this->company->id)->where('categoria', 'venda')->count())->toBe(1);
    });

    it('venda com desconto registra total correto', function () use (&$salePayload) {
        $payload = $salePayload(['items' => [
            ['id' => $this->produto->id, 'type' => 'product', 'name' => 'Shampoo', 'price' => 25.00, 'qty' => 1],
        ], 'desconto' => 5.00, 'total' => 20.00]);

        $this->actingAs($this->admin)
            ->postJson(route('pdv.store'), $payload);

        $venda = Venda::where('company_id', $this->company->id)->first();
        expect((float) $venda->total)->toBe(20.0)
            ->and((float) $venda->desconto)->toBe(5.0);
    });

    it('validação falha sem items', function () {
        $this->actingAs($this->admin)
            ->postJson(route('pdv.store'), ['items' => [], 'subtotal' => 0, 'desconto' => 0, 'total' => 0, 'metodo_pagamento' => 'dinheiro'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    });

    it('venda pode ter cliente associado', function () use (&$salePayload) {
        $cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana', 'ativo' => true]);

        $payload = $salePayload([
            'items' => [['id' => $this->produto->id, 'type' => 'product', 'name' => 'Shampoo', 'price' => 25.00, 'qty' => 1]],
            'cliente_id' => $cliente->id,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('pdv.store'), $payload)
            ->assertCreated();

        $venda = Venda::where('company_id', $this->company->id)->first();
        expect($venda->cliente_id)->toBe($cliente->id);
    });
});
