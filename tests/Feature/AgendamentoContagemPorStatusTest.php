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
        'name' => 'Barbearia ContagPS', 'slug' => 'barbearia-contagps',
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

function makeAgContagem(string $companyId, string $profId, string $clienteId, string $servicoId, string $status, int $diasAtras = 5): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => now()->subDays($diasAtras),
        'duracao' => 30,
        'valor' => 50,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('agendamentos_contagem_por_status', function () {
    it('retorna zeros sem agendamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.contagem-por-status'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo_dias', 'total', 'pendente', 'confirmado', 'em_atendimento', 'finalizado', 'cancelado']);
        expect($data['total'])->toBe(0);
        expect($data['finalizado'])->toBe(0);
    });

    it('conta por status corretamente', function () {
        makeAgContagem($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO);
        makeAgContagem($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO);
        makeAgContagem($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_CANCELADO);
        makeAgContagem($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_PENDENTE);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.contagem-por-status'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(4);
        expect($data['finalizado'])->toBe(2);
        expect($data['cancelado'])->toBe(1);
        expect($data['pendente'])->toBe(1);
    });

    it('respeita o parâmetro dias', function () {
        makeAgContagem($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 5);
        makeAgContagem($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 60);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.contagem-por-status', ['dias' => 30]))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['periodo_dias'])->toBe(30);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('agendamentos.contagem-por-status'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('agendamentos.contagem-por-status'))
            ->assertUnauthorized();
    });
});
