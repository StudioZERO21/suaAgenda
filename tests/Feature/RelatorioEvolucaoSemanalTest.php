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
        'name' => 'Barbearia Evolucao Sem', 'slug' => 'barbearia-es',
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

describe('relatorio_evolucao_semanal', function () {
    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.evolucao-semanal'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKey('semanas');
        expect($data['semanas'])->toBeArray();
        expect(count($data['semanas']))->toBe(8);
        expect($data['semanas'][0])->toHaveKeys(['semana', 'label', 'total', 'finalizados', 'receita']);
    });

    it('respeita o parâmetro semanas', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.evolucao-semanal', ['semanas' => 4]))
            ->assertOk()
            ->json();

        expect(count($data['semanas']))->toBe(4);
    });

    it('conta agendamentos da semana atual', function () {
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
            'data_hora' => now()->startOfWeek()->addDay()->toDateTimeString(),
            'duracao' => 30, 'valor' => 50.0, 'status' => 'finalizado',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.evolucao-semanal', ['semanas' => 1]))
            ->assertOk()
            ->json();

        $semanaAtual = $data['semanas'][0];
        expect($semanaAtual['total'])->toBe(1);
        expect($semanaAtual['finalizados'])->toBe(1);
        expect((float) $semanaAtual['receita'])->toBe(50.0);
    });

    it('ignora agendamentos cancelados', function () {
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
            'data_hora' => now()->startOfWeek()->addDay()->toDateTimeString(),
            'duracao' => 30, 'valor' => 50.0, 'status' => 'cancelado',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.evolucao-semanal', ['semanas' => 1]))
            ->assertOk()
            ->json();

        expect($data['semanas'][0]['total'])->toBe(0);
    });

    it('não inclui dados de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-es', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'X', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'ativo' => true]);
        Agendamento::create([
            'company_id' => $outra->id, 'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id, 'servico_id' => $servOutra->id,
            'data_hora' => now()->startOfWeek()->addDay()->toDateTimeString(),
            'duracao' => 30, 'valor' => 50.0, 'status' => 'finalizado',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.evolucao-semanal', ['semanas' => 1]))
            ->assertOk()
            ->json();

        expect($data['semanas'][0]['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('relatorios.evolucao-semanal'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('relatorios.evolucao-semanal'))
            ->assertUnauthorized();
    });
});
