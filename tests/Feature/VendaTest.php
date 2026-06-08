<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Lancamento;
use App\Models\Produto;
use App\Models\User;
use App\Models\Venda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor',        'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista',      'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Teste',
        'slug' => 'empresa-teste',
        'plano' => 'trial',
        'whatsapp' => '(11) 99999-0000',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->produto = Produto::create([
        'company_id' => $this->company->id,
        'nome' => 'Pomada Teste',
        'preco' => 45.90,
        'custo' => 22.00,
        'estoque' => 10,
        'unidade' => 'un.',
        'ativo' => true,
    ]);
});

describe('pdv venda', function () {
    it('admin pode registrar uma venda', function () {
        $payload = [
            'cliente_id' => null,
            'items' => [
                ['id' => $this->produto->id, 'type' => 'product', 'name' => 'Pomada Teste', 'price' => 45.90, 'qty' => 2],
            ],
            'subtotal' => 91.80,
            'desconto' => 0,
            'total' => 91.80,
            'metodo_pagamento' => 'pix',
        ];

        $this->actingAs($this->admin)
            ->postJson(route('pdv.store'), $payload)
            ->assertStatus(201)
            ->assertJson(['ok' => true]);

        expect(Venda::where('company_id', $this->company->id)->count())->toBe(1);
        expect((float) Venda::first()->total)->toBe(91.80);
    });

    it('cria lancamento automaticamente ao vender', function () {
        $payload = [
            'items' => [
                ['id' => $this->produto->id, 'type' => 'product', 'name' => 'Pomada Teste', 'price' => 45.90, 'qty' => 1],
            ],
            'subtotal' => 45.90,
            'desconto' => 0,
            'total' => 45.90,
            'metodo_pagamento' => 'dinheiro',
        ];

        $this->actingAs($this->admin)
            ->postJson(route('pdv.store'), $payload)
            ->assertStatus(201);

        expect(Lancamento::where('company_id', $this->company->id)->where('tipo', 'receita')->count())->toBe(1);
        expect((float) Lancamento::first()->valor)->toBe(45.90);
    });

    it('decrementa estoque ao vender produto', function () {
        $payload = [
            'items' => [
                ['id' => $this->produto->id, 'type' => 'product', 'name' => 'Pomada Teste', 'price' => 45.90, 'qty' => 3],
            ],
            'subtotal' => 137.70,
            'desconto' => 0,
            'total' => 137.70,
            'metodo_pagamento' => 'pix',
        ];

        $this->actingAs($this->admin)
            ->postJson(route('pdv.store'), $payload)
            ->assertStatus(201);

        expect($this->produto->fresh()->estoque)->toBe(7);
    });

    it('carrinho vazio é rejeitado', function () {
        $this->actingAs($this->admin)
            ->postJson(route('pdv.store'), [
                'items' => [],
                'subtotal' => 0,
                'desconto' => 0,
                'total' => 0,
                'metodo_pagamento' => 'pix',
            ])
            ->assertUnprocessable();
    });
});
