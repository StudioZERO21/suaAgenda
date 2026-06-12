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
        'name' => 'Barbearia NS', 'slug' => 'barbearia-ns',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof NS', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte NS', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente NS', 'lgpd_consent' => true]);
});

function makeAgNS(string $companyId, string $profId, string $clienteId, string $servicoId, string $status, Carbon $dataHora): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => $dataHora,
        'duracao' => 30,
        'valor' => 50,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('agendamento_potenciais_no_show', function () {
    it('retorna estrutura correta sem no-shows', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.potenciais-no-show'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['horas_tolerancia', 'periodo_dias', 'total', 'items']);
        expect($data['total'])->toBe(0);
        expect($data['items'])->toBeEmpty();
    });

    it('detecta agendamento pendente no passado como no-show', function () {
        makeAgNS($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id,
            Agendamento::STATUS_PENDENTE, now()->subHours(5));

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.potenciais-no-show'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['items'][0])->toHaveKeys(['agendamento_id', 'data_hora', 'status', 'cliente_nome', 'profissional_nome', 'servico_nome', 'valor', 'horas_atraso']);
        expect($data['items'][0]['status'])->toBe(Agendamento::STATUS_PENDENTE);
    });

    it('detecta agendamento confirmado no passado como no-show', function () {
        makeAgNS($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id,
            Agendamento::STATUS_CONFIRMADO, now()->subHours(5));

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.potenciais-no-show'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
    });

    it('não inclui agendamentos finalizados ou cancelados', function () {
        makeAgNS($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id,
            Agendamento::STATUS_FINALIZADO, now()->subHours(5));
        makeAgNS($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id,
            Agendamento::STATUS_CANCELADO, now()->subHours(5));

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.potenciais-no-show'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('não inclui agendamentos futuros', function () {
        makeAgNS($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id,
            Agendamento::STATUS_CONFIRMADO, now()->addHours(2));

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.potenciais-no-show'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('ignora dados de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra NS', 'slug' => 'outra-ns', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'Prof', 'ativo' => true]);
        $srvOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'Srv', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Cli', 'lgpd_consent' => true]);
        makeAgNS($outra->id, $profOutra->id, $cliOutra->id, $srvOutra->id, Agendamento::STATUS_PENDENTE, now()->subHours(5));

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.potenciais-no-show'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('agendamentos.potenciais-no-show'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('agendamentos.potenciais-no-show'))
            ->assertUnauthorized();
    });
});
