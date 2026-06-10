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

    $this->company = Company::create([
        'name' => 'Barbearia Resumo', 'slug' => 'barbearia-resumo',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'phone' => '11999990001']);
});

function makeResumoAg($self, string $status, bool $hoje = true): Agendamento
{
    return Agendamento::create([
        'company_id' => $self->company->id,
        'cliente_id' => $self->cliente->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $self->servico->id,
        'data_hora' => $hoje ? today()->setTime(10, 0) : now()->addDays(2)->setTime(10, 0),
        'duracao' => 30,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('dashboard_resumo', function () {
    it('retorna estrutura correta', function () {
        $this->actingAs($this->user)
            ->getJson(route('dashboard.resumo'))
            ->assertOk()
            ->assertJsonStructure([
                'hoje_total', 'hoje_finalizados', 'hoje_confirmados',
                'hoje_receita', 'proximos_3_dias', 'clientes_ativos', 'profissionais_ativos',
            ]);
    });

    it('conta agendamentos de hoje corretamente', function () {
        makeResumoAg($this, Agendamento::STATUS_CONFIRMADO);
        makeResumoAg($this, Agendamento::STATUS_FINALIZADO);
        makeResumoAg($this, Agendamento::STATUS_CANCELADO);

        $data = $this->actingAs($this->user)
            ->getJson(route('dashboard.resumo'))
            ->json();

        expect($data['hoje_total'])->toBe(3);
        expect($data['hoje_finalizados'])->toBe(1);
        expect($data['hoje_confirmados'])->toBe(1);
    });

    it('conta clientes e profissionais ativos', function () {
        Profissional::create(['company_id' => $this->company->id, 'name' => 'Inativo', 'ativo' => false]);
        Cliente::create(['company_id' => $this->company->id, 'name' => 'Inativo', 'phone' => '11999990002', 'ativo' => false]);

        $data = $this->actingAs($this->user)
            ->getJson(route('dashboard.resumo'))
            ->json();

        expect($data['clientes_ativos'])->toBe(1);
        expect($data['profissionais_ativos'])->toBe(1);
    });

    it('conta proximos_3_dias corretamente', function () {
        makeResumoAg($this, Agendamento::STATUS_CONFIRMADO, hoje: false);
        makeResumoAg($this, Agendamento::STATUS_PENDENTE, hoje: false);
        makeResumoAg($this, Agendamento::STATUS_CANCELADO, hoje: false);

        $data = $this->actingAs($this->user)
            ->getJson(route('dashboard.resumo'))
            ->json();

        expect($data['proximos_3_dias'])->toBe(2);
    });

    it('não expõe dados de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-resumo', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $svcOutra = Servico::create([
            'company_id' => $outra->id, 'nome' => 'Svc',
            'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#000', 'ativo' => true,
        ]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'phone' => '99999999999']);

        Agendamento::create([
            'company_id' => $outra->id, 'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id, 'servico_id' => $svcOutra->id,
            'data_hora' => today()->setTime(11, 0), 'duracao' => 30,
            'status' => Agendamento::STATUS_CONFIRMADO,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->user)
            ->getJson(route('dashboard.resumo'))
            ->json();

        expect($data['hoje_total'])->toBe(0);
        expect($data['clientes_ativos'])->toBe(1);
        expect($data['profissionais_ativos'])->toBe(1);
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('dashboard.resumo'))
            ->assertUnauthorized();
    });
});
