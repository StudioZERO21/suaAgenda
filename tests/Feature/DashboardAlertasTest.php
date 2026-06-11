<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Produto;
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
        'name' => 'Barbearia Alertas', 'slug' => 'barbearia-alertas',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'P', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true,
    ]);
    $this->cliente = Cliente::create([
        'company_id' => $this->company->id, 'name' => 'João', 'phone' => '11999990044',
    ]);
});

describe('dashboard_alertas', function () {
    it('retorna estrutura correta', function () {
        $this->actingAs($this->admin)
            ->getJson(route('dashboard.alertas'))
            ->assertOk()
            ->assertJsonStructure([
                'pendentes', 'estoque_baixo', 'aniversariantes_hoje',
                'em_atendimento', 'total_alertas',
            ]);
    });

    it('conta pendentes futuros corretamente', function () {
        Agendamento::create([
            'company_id' => $this->company->id, 'profissional_id' => $this->prof->id,
            'servico_id' => $this->servico->id, 'cliente_id' => $this->cliente->id,
            'data_hora' => now()->addDay(), 'duracao' => 30,
            'status' => Agendamento::STATUS_PENDENTE, 'cancel_token' => Agendamento::generateCancelToken(),
        ]);
        Agendamento::create([
            'company_id' => $this->company->id, 'profissional_id' => $this->prof->id,
            'servico_id' => $this->servico->id, 'cliente_id' => $this->cliente->id,
            'data_hora' => now()->addDay(), 'duracao' => 30,
            'status' => Agendamento::STATUS_CONFIRMADO, 'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('dashboard.alertas'))
            ->json();

        expect($data['pendentes'])->toBe(1);
    });

    it('conta produtos com estoque baixo', function () {
        Produto::create([
            'company_id' => $this->company->id, 'nome' => 'P1', 'preco' => 10.0,
            'estoque' => 2, 'estoque_min' => 5, 'ativo' => true,
        ]);
        Produto::create([
            'company_id' => $this->company->id, 'nome' => 'P2', 'preco' => 10.0,
            'estoque' => 10, 'estoque_min' => 5, 'ativo' => true,
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('dashboard.alertas'))
            ->json();

        expect($data['estoque_baixo'])->toBe(1);
    });

    it('conta aniversariantes de hoje', function () {
        Cliente::create([
            'company_id' => $this->company->id, 'name' => 'Aniversariante',
            'phone' => '11999990055', 'ativo' => true,
            'data_nasc' => now()->format('Y-m-d'),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('dashboard.alertas'))
            ->json();

        expect($data['aniversariantes_hoje'])->toBe(1);
    });

    it('total_alertas é soma de pendentes + estoque_baixo + aniversariantes', function () {
        Agendamento::create([
            'company_id' => $this->company->id, 'profissional_id' => $this->prof->id,
            'servico_id' => $this->servico->id, 'cliente_id' => $this->cliente->id,
            'data_hora' => now()->addDay(), 'duracao' => 30,
            'status' => Agendamento::STATUS_PENDENTE, 'cancel_token' => Agendamento::generateCancelToken(),
        ]);
        Produto::create([
            'company_id' => $this->company->id, 'nome' => 'PBaixo', 'preco' => 5.0,
            'estoque' => 0, 'estoque_min' => 3, 'ativo' => true,
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('dashboard.alertas'))
            ->json();

        expect($data['total_alertas'])->toBe($data['pendentes'] + $data['estoque_baixo'] + $data['aniversariantes_hoje']);
    });

    it('não expõe dados de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-alertas', 'plano' => 'trial', 'ativo' => true]);
        Produto::create([
            'company_id' => $outra->id, 'nome' => 'POutra', 'preco' => 5.0,
            'estoque' => 0, 'estoque_min' => 3, 'ativo' => true,
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('dashboard.alertas'))
            ->json();

        expect($data['estoque_baixo'])->toBe(0);
    });

    it('analista pode ver alertas', function () {
        $this->actingAs($this->analista)
            ->getJson(route('dashboard.alertas'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('dashboard.alertas'))
            ->assertUnauthorized();
    });
});
