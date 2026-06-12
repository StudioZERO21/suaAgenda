<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Avaliacao;
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
        'name' => 'Barbearia SemAval', 'slug' => 'barbearia-semaval',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof SA', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte SA', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente SA', 'lgpd_consent' => true]);
});

function makeAgSemAval(string $companyId, string $profId, string $clienteId, string $servicoId, string $status = Agendamento::STATUS_FINALIZADO, int $diasAtras = 5): Agendamento
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

describe('agendamento_sem_avaliacao', function () {
    it('retorna estrutura correta sem agendamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.sem-avaliacao'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo_dias', 'total', 'items']);
        expect($data['total'])->toBe(0);
        expect($data['periodo_dias'])->toBe(30);
    });

    it('retorna agendamentos finalizados sem avaliação', function () {
        $ag1 = makeAgSemAval($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id);
        $ag2 = makeAgSemAval($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 3);
        Avaliacao::create(['company_id' => $this->company->id, 'agendamento_id' => $ag2->id, 'nota' => 5]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.sem-avaliacao'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['items'][0]['agendamento_id'])->toBe($ag1->id);
    });

    it('exclui agendamentos não finalizados', function () {
        makeAgSemAval($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_PENDENTE);
        makeAgSemAval($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_CANCELADO);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.sem-avaliacao'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('respeita parâmetro dias', function () {
        makeAgSemAval($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 5);
        makeAgSemAval($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 45);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.sem-avaliacao', ['dias' => 30]))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['periodo_dias'])->toBe(30);
    });

    it('items têm campos corretos incluindo dias_desde', function () {
        makeAgSemAval($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 3);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.sem-avaliacao'))
            ->assertOk()
            ->json();

        expect($data['items'][0])->toHaveKeys(['agendamento_id', 'data_hora', 'cliente_nome', 'profissional_nome', 'servico_nome', 'valor', 'dias_desde']);
        expect($data['items'][0]['dias_desde'])->toBe(3);
    });

    it('ignora agendamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra SA', 'slug' => 'outra-sa', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'Prof Outra', 'ativo' => true]);
        $srvOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'Srv', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Cli', 'lgpd_consent' => true]);
        makeAgSemAval($outra->id, $profOutra->id, $cliOutra->id, $srvOutra->id);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.sem-avaliacao'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('agendamentos.sem-avaliacao'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('agendamentos.sem-avaliacao'))
            ->assertUnauthorized();
    });
});
