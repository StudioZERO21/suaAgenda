<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\Servico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia ResSem', 'slug' => 'barbearia-ressem',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana', 'lgpd_consent' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
});

function makeAgSemana(string $companyId, string $profId, string $clienteId, string $servicoId, string $status, Carbon $dataHora): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => $dataHora,
        'duracao' => 30,
        'valor' => 100,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('agendamento_resumo_semana', function () {
    it('retorna estrutura com 7 dias', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.resumo-semana'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['semana_inicio', 'semana_fim', 'total_semana', 'receita_semana', 'dias']);
        expect($data['dias'])->toHaveCount(7);
        expect($data['dias'][0])->toHaveKeys(['data', 'dia_semana', 'total', 'finalizados', 'cancelados', 'pendentes', 'receita']);
    });

    it('conta agendamentos por dia corretamente', function () {
        $segunda = now()->startOfWeek(Carbon::MONDAY);
        makeAgSemana($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, $segunda->copy()->setTime(10, 0));
        makeAgSemana($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, $segunda->copy()->setTime(11, 0));
        makeAgSemana($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_CANCELADO, $segunda->copy()->addDay()->setTime(10, 0));

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.resumo-semana'))
            ->assertOk()
            ->json();

        expect($data['total_semana'])->toBe(3);
        expect($data['dias'][0]['total'])->toBe(2);
        expect($data['dias'][0]['finalizados'])->toBe(2);
        expect((float) $data['dias'][0]['receita'])->toBe(200.0);
        expect($data['dias'][1]['cancelados'])->toBe(1);
    });

    it('aceita parâmetro inicio personalizado', function () {
        $semanaPassada = now()->startOfWeek(Carbon::MONDAY)->subWeek();
        makeAgSemana($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, $semanaPassada->copy()->setTime(10, 0));

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.resumo-semana', ['inicio' => $semanaPassada->format('Y-m-d')]))
            ->assertOk()
            ->json();

        expect($data['semana_inicio'])->toBe($semanaPassada->format('Y-m-d'));
        expect($data['total_semana'])->toBe(1);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('agendamentos.resumo-semana'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('agendamentos.resumo-semana'))
            ->assertUnauthorized();
    });
});
