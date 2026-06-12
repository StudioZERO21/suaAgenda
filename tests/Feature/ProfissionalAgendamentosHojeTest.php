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
        'name' => 'Barbearia AgHoje', 'slug' => 'barbearia-aghoje',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof1 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->prof2 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Bruno', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana', 'lgpd_consent' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
});

function makeAgHoje(string $companyId, string $profId, string $clienteId, string $servicoId, string $status, bool $ontem = false): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => $ontem ? now()->subDay() : now(),
        'duracao' => 30,
        'valor' => 50,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('profissional_agendamentos_hoje', function () {
    it('retorna lista vazia sem agendamentos hoje', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.agendamentos-hoje'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['data', 'total_profissionais', 'items']);
        expect($data['total_profissionais'])->toBe(0);
        expect($data['items'])->toBeEmpty();
    });

    it('inclui profissional com agendamentos hoje', function () {
        makeAgHoje($this->company->id, $this->prof1->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_CONFIRMADO);
        makeAgHoje($this->company->id, $this->prof1->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.agendamentos-hoje'))
            ->assertOk()
            ->json();

        expect($data['total_profissionais'])->toBe(1);
        expect($data['items'][0]['profissional_nome'])->toBe('Carlos');
        expect($data['items'][0]['total_hoje'])->toBe(2);
        expect($data['items'][0]['finalizados'])->toBe(1);
        expect($data['items'][0]['pendentes'])->toBe(1);
    });

    it('não inclui agendamentos cancelados', function () {
        makeAgHoje($this->company->id, $this->prof1->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_CANCELADO);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.agendamentos-hoje'))
            ->assertOk()
            ->json();

        expect($data['total_profissionais'])->toBe(0);
    });

    it('não inclui agendamentos de ontem', function () {
        makeAgHoje($this->company->id, $this->prof1->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_CONFIRMADO, true);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.agendamentos-hoje'))
            ->assertOk()
            ->json();

        expect($data['total_profissionais'])->toBe(0);
    });

    it('incluir_sem_agendamentos retorna todos os profissionais ativos', function () {
        makeAgHoje($this->company->id, $this->prof1->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_CONFIRMADO);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.agendamentos-hoje', ['incluir_sem_agendamentos' => 'true']))
            ->assertOk()
            ->json();

        expect($data['total_profissionais'])->toBe(2);
        $nomes = collect($data['items'])->pluck('profissional_nome')->sort()->values()->all();
        expect($nomes)->toBe(['Bruno', 'Carlos']);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('profissionais.agendamentos-hoje'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('profissionais.agendamentos-hoje'))
            ->assertUnauthorized();
    });
});
