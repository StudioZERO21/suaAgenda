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
        'name' => 'Barbearia Churn', 'slug' => 'barbearia-churn',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);
});

function makeChurnAg(string $companyId, string $clienteId, string $profId, string $servId, string $dataHora): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'cliente_id' => $clienteId,
        'profissional_id' => $profId,
        'servico_id' => $servId,
        'data_hora' => $dataHora,
        'duracao' => 30,
        'valor' => 50.0,
        'status' => 'finalizado',
    ]);
}

describe('relatorio_churn', function () {
    it('retorna estrutura correta quando sem clientes', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.churn'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['inativo_dias', 'total_clientes', 'churnados', 'taxa_churn_pct', 'clientes']);
        expect($data['churnados'])->toBe(0);
    });

    it('identifica cliente churnado corretamente', function () {
        $cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João Antigo', 'ativo' => true]);
        makeChurnAg(
            $this->company->id, $cliente->id, $this->prof->id, $this->servico->id,
            now()->subDays(90)->toDateTimeString()
        );

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.churn', ['inativo_dias' => 60]))
            ->assertOk()
            ->json();

        expect($data['churnados'])->toBe(1);
        expect($data['clientes'][0]['nome'])->toBe('João Antigo');
        expect($data['clientes'][0]['dias_sem_visita'])->toBeGreaterThanOrEqual(90);
    });

    it('não considera cliente ativo como churnado', function () {
        $cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ativo', 'ativo' => true]);
        makeChurnAg(
            $this->company->id, $cliente->id, $this->prof->id, $this->servico->id,
            now()->subDays(10)->toDateTimeString()
        );

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.churn', ['inativo_dias' => 60]))
            ->assertOk()
            ->json();

        expect($data['churnados'])->toBe(0);
    });

    it('não inclui clientes de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-churn', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'X', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Z', 'ativo' => true]);
        makeChurnAg($outra->id, $cliOutra->id, $profOutra->id, $servOutra->id, now()->subDays(100)->toDateTimeString());

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.churn'))
            ->assertOk()
            ->json();

        expect($data['churnados'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('relatorios.churn'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('relatorios.churn'))
            ->assertUnauthorized();
    });
});
