<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Lancamento;
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
        'name' => 'Barbearia FluxoCaixa', 'slug' => 'barbearia-fluxocaixa',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('financeiro_fluxo_caixa', function () {
    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.fluxo-caixa'))
            ->assertOk()
            ->json();

        expect($data)->toBeArray();
        expect($data[0])->toHaveKeys(['data', 'receita', 'despesa', 'saldo']);
    });

    it('retorna uma entrada por dia do período', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.fluxo-caixa'))
            ->json();

        expect(count($data))->toBeGreaterThanOrEqual(28);
        expect(count($data))->toBeLessThanOrEqual(31);
    });

    it('soma receita de agendamentos e lançamentos no mesmo dia', function () {
        $prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'P', 'ativo' => true]);
        $servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'S', 'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true]);
        $cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'C', 'phone' => '11999990001']);

        $hoje = now()->startOfMonth()->addDays(1);

        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $prof->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'data_hora' => $hoje,
            'duracao' => 30,
            'valor' => 100.0,
            'status' => 'finalizado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        Lancamento::create([
            'company_id' => $this->company->id,
            'descricao' => 'Extra',
            'tipo' => 'receita',
            'valor' => 50.0,
            'data' => $hoje->format('Y-m-d'),
            'status' => 'pago',
        ]);

        Lancamento::create([
            'company_id' => $this->company->id,
            'descricao' => 'Aluguel',
            'tipo' => 'despesa',
            'valor' => 30.0,
            'data' => $hoje->format('Y-m-d'),
            'status' => 'pago',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.fluxo-caixa'))
            ->json();

        $dia = collect($data)->firstWhere('data', $hoje->format('Y-m-d'));
        expect((float) $dia['receita'])->toBe(150.0);
        expect((float) $dia['despesa'])->toBe(30.0);
        expect((float) $dia['saldo'])->toBe(120.0);
    });

    it('analista pode ver fluxo de caixa', function () {
        $this->actingAs($this->analista)
            ->getJson(route('financeiro.fluxo-caixa'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('financeiro.fluxo-caixa'))
            ->assertUnauthorized();
    });
});
