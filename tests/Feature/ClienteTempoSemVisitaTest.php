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
        'name' => 'Barbearia SemVisita', 'slug' => 'barbearia-semvisita',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana', 'lgpd_consent' => true]);
});

function makeAgSemVisita(string $companyId, string $profId, string $clienteId, string $servicoId, string $status, int $diasAtras): Agendamento
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

describe('cliente_tempo_sem_visita', function () {
    it('retorna sem_historico quando não há agendamentos finalizados', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.tempo-sem-visita', $this->cliente))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['cliente_id', 'cliente_nome', 'dias_sem_visita', 'ultima_visita', 'risco_churn']);
        expect($data['risco_churn'])->toBe('sem_historico');
        expect($data['dias_sem_visita'])->toBeNull();
        expect($data['ultima_visita'])->toBeNull();
    });

    it('classifica risco baixo para visitas recentes', function () {
        makeAgSemVisita($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 10);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.tempo-sem-visita', $this->cliente))
            ->assertOk()
            ->json();

        expect($data['risco_churn'])->toBe('baixo');
        expect($data['dias_sem_visita'])->toBeLessThanOrEqual(30);
    });

    it('classifica risco critico para longa ausência', function () {
        makeAgSemVisita($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 120);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.tempo-sem-visita', $this->cliente))
            ->assertOk()
            ->json();

        expect($data['risco_churn'])->toBe('critico');
        expect($data['dias_sem_visita'])->toBeGreaterThan(90);
    });

    it('usa a data do agendamento mais recente finalizado', function () {
        makeAgSemVisita($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 100);
        makeAgSemVisita($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 5);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.tempo-sem-visita', $this->cliente))
            ->assertOk()
            ->json();

        expect($data['dias_sem_visita'])->toBeLessThanOrEqual(7);
        expect($data['risco_churn'])->toBe('baixo');
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('clientes.tempo-sem-visita', $this->cliente))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('clientes.tempo-sem-visita', $this->cliente))
            ->assertUnauthorized();
    });
});
