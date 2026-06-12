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
        'name' => 'Barbearia PorProf', 'slug' => 'barbearia-porprof',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof1 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true, 'cor' => '#111']);
    $this->prof2 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Ana', 'ativo' => true, 'cor' => '#222']);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'lgpd_consent' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao' => 30, 'preco' => 50, 'ativo' => true]);
});

function makeAgPorProf(string $companyId, string $profId, string $clienteId, string $servicoId, string $status, float $valor, ?string $data = null): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => ($data ?? today()->toDateString()).' 10:00:00',
        'duracao' => 30,
        'valor' => $valor,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('agendamentos_por_profissional', function () {
    it('retorna estrutura correta sem agendamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.por-profissional'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['data', 'total_agendamentos', 'profissionais']);
        expect($data['total_agendamentos'])->toBe(0);
        expect($data['profissionais'])->toHaveCount(0);
    });

    it('agrupa agendamentos por profissional', function () {
        makeAgPorProf($this->company->id, $this->prof1->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_CONFIRMADO, 50);
        makeAgPorProf($this->company->id, $this->prof1->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 50);
        makeAgPorProf($this->company->id, $this->prof2->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_PENDENTE, 50);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.por-profissional'))
            ->assertOk()
            ->json();

        expect($data['total_agendamentos'])->toBe(3);
        expect($data['profissionais'])->toHaveCount(2);

        $prof1data = collect($data['profissionais'])->firstWhere('profissional_id', $this->prof1->id);
        expect($prof1data['total'])->toBe(2);
        expect($prof1data['finalizados'])->toBe(1);
        expect((float) $prof1data['receita'])->toBe(50.0);
    });

    it('aceita parâmetro data', function () {
        $outraData = now()->addDays(2)->toDateString();
        makeAgPorProf($this->company->id, $this->prof1->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_CONFIRMADO, 50, $outraData);

        $dataHoje = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.por-profissional'))
            ->assertOk()
            ->json();
        expect($dataHoje['total_agendamentos'])->toBe(0);

        $dataFutura = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.por-profissional', ['data' => $outraData]))
            ->assertOk()
            ->json();
        expect($dataFutura['total_agendamentos'])->toBe(1);
    });

    it('não retorna agendamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-porprof', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $clienteOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'lgpd_consent' => false]);
        $servicoOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'Z', 'duracao' => 30, 'preco' => 10, 'ativo' => true]);
        makeAgPorProf($outra->id, $profOutra->id, $clienteOutra->id, $servicoOutra->id, Agendamento::STATUS_CONFIRMADO, 50);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.por-profissional'))
            ->assertOk()
            ->json();

        expect($data['total_agendamentos'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('agendamentos.por-profissional'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('agendamentos.por-profissional'))
            ->assertUnauthorized();
    });
});
