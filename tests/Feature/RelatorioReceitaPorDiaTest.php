<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
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
        'name' => 'Barbearia Rec Dia', 'slug' => 'barbearia-rd',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create([
        'company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte', 'preco' => 50.0,
        'duracao_minutos' => 30, 'ativo' => true,
    ]);

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id, 'name' => 'João', 'ativo' => true,
    ]);
});

describe('relatorio_receita_por_dia', function () {
    it('retorna estrutura correta sem dados', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.receita-por-dia'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo', 'receita_total', 'dias']);
        expect((float) $data['receita_total'])->toBe(0.0);
        expect($data['dias'])->toBeEmpty();
    });

    it('soma receita por dia corretamente', function () {
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
            'data_hora' => now()->subDays(1)->setTime(10, 0)->toDateTimeString(),
            'duracao' => 30, 'valor' => 50.0, 'status' => 'finalizado',
        ]);
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
            'data_hora' => now()->subDays(1)->setTime(14, 0)->toDateTimeString(),
            'duracao' => 30, 'valor' => 30.0, 'status' => 'finalizado',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.receita-por-dia'))
            ->assertOk()
            ->json();

        expect((float) $data['receita_total'])->toBe(80.0);
        expect($data['dias'])->toHaveCount(1);
        expect((float) $data['dias'][0]['receita'])->toBe(80.0);
        expect($data['dias'][0]['total'])->toBe(2);
    });

    it('ignora agendamentos não finalizados', function () {
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
            'data_hora' => now()->subDays(1)->toDateTimeString(),
            'duracao' => 30, 'valor' => 50.0, 'status' => 'pendente',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.receita-por-dia'))
            ->assertOk()
            ->json();

        expect((float) $data['receita_total'])->toBe(0.0);
        expect($data['dias'])->toBeEmpty();
    });

    it('não inclui dados de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-rd', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'X', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'ativo' => true]);
        Agendamento::create([
            'company_id' => $outra->id, 'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id, 'servico_id' => $servOutra->id,
            'data_hora' => now()->subDays(1)->toDateTimeString(),
            'duracao' => 30, 'valor' => 200.0, 'status' => 'finalizado',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.receita-por-dia'))
            ->assertOk()
            ->json();

        expect((float) $data['receita_total'])->toBe(0.0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('relatorios.receita-por-dia'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('relatorios.receita-por-dia'))
            ->assertUnauthorized();
    });
});
