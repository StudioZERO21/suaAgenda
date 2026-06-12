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
        'name' => 'Barbearia ServEst', 'slug' => 'barbearia-servest',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'P', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'C', 'phone' => '11999990001']);
});

describe('servico_estatisticas', function () {
    it('retorna estrutura correta', function () {
        Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.estatisticas'))
            ->assertOk()
            ->json();

        expect($data[0])->toHaveKeys(['id', 'nome', 'cor', 'preco', 'duracao_minutos', 'total_agendamentos', 'finalizados', 'receita_total', 'ticket_medio']);
    });

    it('conta agendamentos e calcula receita', function () {
        $servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Barba', 'duracao_minutos' => 20, 'preco' => 30.0, 'cor' => '#222', 'ativo' => true]);

        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $servico->id,
            'data_hora' => now()->subDay(),
            'duracao' => 20,
            'valor' => 30.0,
            'status' => 'finalizado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.estatisticas'))
            ->json();

        $row = collect($data)->firstWhere('id', $servico->id);
        expect($row['finalizados'])->toBe(1);
        expect((float) $row['receita_total'])->toBe(30.0);
        expect((float) $row['ticket_medio'])->toBe(30.0);
    });

    it('retorna zeros quando sem agendamentos', function () {
        $servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Manicure', 'duracao_minutos' => 60, 'preco' => 40.0, 'cor' => '#333', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.estatisticas'))
            ->json();

        $row = collect($data)->firstWhere('id', $servico->id);
        expect($row['total_agendamentos'])->toBe(0);
        expect((float) $row['receita_total'])->toBe(0.0);
    });

    it('não inclui serviços de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-servest', 'plano' => 'trial', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'X', 'duracao_minutos' => 30, 'preco' => 10.0, 'cor' => '#000', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.estatisticas'))
            ->json();

        $ids = collect($data)->pluck('id')->all();
        expect($ids)->not->toContain($servOutra->id);
    });

    it('analista pode ver estatísticas', function () {
        $this->actingAs($this->analista)
            ->getJson(route('servicos.estatisticas'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('servicos.estatisticas'))
            ->assertUnauthorized();
    });
});
