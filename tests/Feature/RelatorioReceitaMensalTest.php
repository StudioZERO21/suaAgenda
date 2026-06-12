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
        'name' => 'Barbearia RM', 'slug' => 'barbearia-rm',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'ativo' => true]);
});

function makeRmAg(string $companyId, string $clienteId, string $profId, string $servId, string $dataHora, float $valor): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'cliente_id' => $clienteId,
        'profissional_id' => $profId,
        'servico_id' => $servId,
        'data_hora' => $dataHora,
        'duracao' => 30,
        'valor' => $valor,
        'status' => 'finalizado',
    ]);
}

describe('relatorio_receita_mensal', function () {
    it('retorna 12 meses por padrão com estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.receita-mensal'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['meses', 'receita_total', 'melhor_mes', 'dados']);
        expect($data['meses'])->toBe(12);
        expect($data['dados'])->toHaveCount(12);
        expect($data['dados'][0])->toHaveKeys(['mes', 'label', 'receita', 'total']);
    });

    it('soma receita do mês corrente corretamente', function () {
        makeRmAg(
            $this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id,
            now()->startOfMonth()->addDays(5)->toDateTimeString(), 80.0
        );
        makeRmAg(
            $this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id,
            now()->startOfMonth()->addDays(10)->toDateTimeString(), 60.0
        );

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.receita-mensal'))
            ->assertOk()
            ->json();

        $mesAtual = now()->format('Y-m');
        $dadoAtual = collect($data['dados'])->firstWhere('mes', $mesAtual);
        expect((float) $dadoAtual['receita'])->toBe(140.0);
        expect($dadoAtual['total'])->toBe(2);
    });

    it('parâmetro meses funciona', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.receita-mensal', ['meses' => 6]))
            ->assertOk()
            ->json();

        expect($data['meses'])->toBe(6);
        expect($data['dados'])->toHaveCount(6);
    });

    it('não inclui dados de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-rm', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'X', 'preco' => 100.0, 'duracao_minutos' => 30, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Z', 'ativo' => true]);
        makeRmAg($outra->id, $cliOutra->id, $profOutra->id, $servOutra->id, now()->toDateTimeString(), 999.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.receita-mensal'))
            ->assertOk()
            ->json();

        expect((float) $data['receita_total'])->toBe(0.0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('relatorios.receita-mensal'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('relatorios.receita-mensal'))
            ->assertUnauthorized();
    });
});
