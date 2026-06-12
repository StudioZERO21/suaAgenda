<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia PR', 'slug' => 'barbearia-pr',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof PR', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte PR', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente PR', 'lgpd_consent' => true]);
});

function makeAgPR(string $companyId, string $profId, string $clienteId, string $servicoId, string $status, float $valor, Carbon $dataHora): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => $dataHora,
        'duracao' => 30,
        'valor' => $valor,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('financeiro_previsao_receita', function () {
    it('retorna estrutura correta sem agendamentos futuros', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.previsao-receita'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['dias', 'data_inicio', 'data_fim', 'total_agendamentos', 'receita_confirmada', 'receita_pendente', 'receita_total_prevista', 'por_dia']);
        expect($data['total_agendamentos'])->toBe(0);
        expect((float) $data['receita_total_prevista'])->toBe(0.0);
        expect($data['por_dia'])->toHaveCount(14);
    });

    it('cada dia tem campos corretos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.previsao-receita'))
            ->assertOk()
            ->json();

        $dia = $data['por_dia'][0];
        expect($dia)->toHaveKeys(['data', 'dia_semana', 'total_agendamentos', 'confirmados', 'pendentes', 'receita_confirmada', 'receita_pendente', 'receita_total']);
    });

    it('soma confirmados e pendentes separadamente', function () {
        makeAgPR($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_CONFIRMADO, 80.0, now()->addDays(1));
        makeAgPR($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_PENDENTE, 60.0, now()->addDays(2));

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.previsao-receita'))
            ->assertOk()
            ->json();

        expect($data['total_agendamentos'])->toBe(2);
        expect((float) $data['receita_confirmada'])->toBe(80.0);
        expect((float) $data['receita_pendente'])->toBe(60.0);
        expect((float) $data['receita_total_prevista'])->toBe(140.0);
    });

    it('ignora agendamentos finalizados e cancelados', function () {
        makeAgPR($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 100.0, now()->addDays(1));
        makeAgPR($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_CANCELADO, 100.0, now()->addDays(1));

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.previsao-receita'))
            ->assertOk()
            ->json();

        expect($data['total_agendamentos'])->toBe(0);
    });

    it('respeita parâmetro dias', function () {
        makeAgPR($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_CONFIRMADO, 50.0, now()->addDays(20));

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.previsao-receita', ['dias' => 7]))
            ->assertOk()
            ->json();

        expect($data['dias'])->toBe(7);
        expect($data['por_dia'])->toHaveCount(7);
        expect($data['total_agendamentos'])->toBe(0);
    });

    it('ignora agendamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra PR', 'slug' => 'outra-pr', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'Prof', 'ativo' => true]);
        $srvOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'Srv', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Cli', 'lgpd_consent' => true]);
        makeAgPR($outra->id, $profOutra->id, $cliOutra->id, $srvOutra->id, Agendamento::STATUS_CONFIRMADO, 100.0, now()->addDays(1));

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.previsao-receita'))
            ->assertOk()
            ->json();

        expect($data['total_agendamentos'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('financeiro.previsao-receita'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('financeiro.previsao-receita'))
            ->assertUnauthorized();
    });
});
