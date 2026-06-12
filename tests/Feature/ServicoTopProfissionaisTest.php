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
        'name' => 'Barbearia TopProf', 'slug' => 'barbearia-topprof',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof1 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->prof2 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Ana', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'lgpd_consent' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
});

function makeAgTopProf(string $companyId, string $profId, string $clienteId, string $servicoId, string $status, float $valor): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => now()->subDays(rand(1, 30)),
        'duracao' => 30,
        'valor' => $valor,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('servico_top_profissionais', function () {
    it('retorna estrutura correta sem agendamentos finalizados', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.top-profissionais', $this->servico))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['servico_id', 'servico_nome', 'total', 'items']);
        expect($data['total'])->toBe(0);
        expect($data['items'])->toBeEmpty();
    });

    it('ordena profissionais por número de realizações', function () {
        // prof1 faz 3x, prof2 faz 1x
        makeAgTopProf($this->company->id, $this->prof1->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 50);
        makeAgTopProf($this->company->id, $this->prof1->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 50);
        makeAgTopProf($this->company->id, $this->prof1->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 50);
        makeAgTopProf($this->company->id, $this->prof2->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 50);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.top-profissionais', $this->servico))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(2);
        expect($data['items'][0]['profissional_id'])->toBe($this->prof1->id);
        expect($data['items'][0]['total_realizados'])->toBe(3);
        expect($data['items'][1]['total_realizados'])->toBe(1);
    });

    it('ignora agendamentos não finalizados', function () {
        makeAgTopProf($this->company->id, $this->prof1->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_CONFIRMADO, 50);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.top-profissionais', $this->servico))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('servicos.top-profissionais', $this->servico))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('servicos.top-profissionais', $this->servico))
            ->assertUnauthorized();
    });
});
