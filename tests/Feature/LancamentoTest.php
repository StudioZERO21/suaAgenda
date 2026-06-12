<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Lancamento;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Lanc',
        'slug' => 'empresa-lanc',
        'plano' => 'trial',
        'whatsapp' => '(11) 99999-0001',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->company2 = Company::create([
        'name' => 'Outra Empresa',
        'slug' => 'outra-empresa',
        'plano' => 'trial',
        'whatsapp' => '(11) 99999-0002',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->admin2 = User::factory()->create(['empresa_id' => $this->company2->id]);
    $this->admin2->assignRole('admin_empresa');
});

describe('lancamentos json api', function () {
    it('admin pode criar lançamento e recebe json 201', function () {
        $payload = [
            'tipo' => 'receita',
            'descricao' => 'Venda de produto',
            'categoria' => 'Produto',
            'valor' => 150.00,
            'data' => now()->format('Y-m-d'),
            'status' => 'pago',
            'metodo_pagamento' => 'Pix',
        ];

        $this->actingAs($this->admin)
            ->postJson(route('financeiro.lancamentos.store'), $payload)
            ->assertStatus(201)
            ->assertJsonStructure(['id', 'tipo', 'valor', 'status_key', 'source'])
            ->assertJson(['tipo' => 'receita', 'source' => 'lancamento', 'status_key' => 'paid']);

        expect(Lancamento::where('company_id', $this->company->id)->count())->toBe(1);
    });

    it('gestor pode criar lançamento', function () {
        $this->actingAs($this->gestor)
            ->postJson(route('financeiro.lancamentos.store'), [
                'tipo' => 'despesa',
                'descricao' => 'Aluguel',
                'valor' => 800.00,
                'data' => now()->format('Y-m-d'),
                'status' => 'pendente',
            ])
            ->assertStatus(201)
            ->assertJson(['tipo' => 'despesa', 'status_key' => 'pending']);
    });

    it('analista não pode criar lançamento', function () {
        $this->actingAs($this->analista)
            ->postJson(route('financeiro.lancamentos.store'), [
                'tipo' => 'receita',
                'descricao' => 'Teste',
                'valor' => 50.00,
                'data' => now()->format('Y-m-d'),
                'status' => 'pago',
            ])
            ->assertForbidden();
    });

    it('validação rejeita tipo inválido', function () {
        $this->actingAs($this->admin)
            ->postJson(route('financeiro.lancamentos.store'), [
                'tipo' => 'invalido',
                'descricao' => 'Teste',
                'valor' => 50.00,
                'data' => now()->format('Y-m-d'),
                'status' => 'pago',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tipo']);
    });

    it('admin pode atualizar lançamento e recebe json 200', function () {
        $lancamento = Lancamento::create([
            'company_id' => $this->company->id,
            'tipo' => 'receita',
            'descricao' => 'Original',
            'valor' => 100.00,
            'data' => now()->format('Y-m-d'),
            'status' => 'pendente',
        ]);

        $this->actingAs($this->admin)
            ->putJson(route('financeiro.lancamentos.update', $lancamento), [
                'tipo' => 'receita',
                'descricao' => 'Atualizado',
                'valor' => 200.00,
                'data' => now()->format('Y-m-d'),
                'status' => 'pago',
            ])
            ->assertOk()
            ->assertJson(['source' => 'lancamento', 'status_key' => 'paid']);

        expect($lancamento->fresh()->descricao)->toBe('Atualizado');
        expect((float) $lancamento->fresh()->valor)->toBe(200.0);
    });

    it('não pode atualizar lançamento de outra empresa', function () {
        $lancamentoAlheio = Lancamento::create([
            'company_id' => $this->company2->id,
            'tipo' => 'receita',
            'descricao' => 'Alheio',
            'valor' => 50.00,
            'data' => now()->format('Y-m-d'),
            'status' => 'pago',
        ]);

        $this->actingAs($this->admin)
            ->putJson(route('financeiro.lancamentos.update', $lancamentoAlheio), [
                'tipo' => 'receita',
                'descricao' => 'Hackeado',
                'valor' => 1.00,
                'data' => now()->format('Y-m-d'),
                'status' => 'pago',
            ])
            ->assertNotFound();
    });

    it('admin pode excluir lançamento e recebe 204', function () {
        $lancamento = Lancamento::create([
            'company_id' => $this->company->id,
            'tipo' => 'despesa',
            'descricao' => 'Para excluir',
            'valor' => 30.00,
            'data' => now()->format('Y-m-d'),
            'status' => 'pago',
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('financeiro.lancamentos.destroy', $lancamento))
            ->assertNoContent();

        expect(Lancamento::find($lancamento->id))->toBeNull();
        expect(Lancamento::withTrashed()->find($lancamento->id))->not->toBeNull();
    });

    it('não pode excluir lançamento de outra empresa', function () {
        $lancamentoAlheio = Lancamento::create([
            'company_id' => $this->company2->id,
            'tipo' => 'receita',
            'descricao' => 'Alheio',
            'valor' => 50.00,
            'data' => now()->format('Y-m-d'),
            'status' => 'pago',
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('financeiro.lancamentos.destroy', $lancamentoAlheio))
            ->assertNotFound();
    });
});
