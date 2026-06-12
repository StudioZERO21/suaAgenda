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
        'name' => 'Barbearia ResumoH', 'slug' => 'barbearia-resumoh',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente X', 'lgpd_consent' => true]);
    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof X', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao' => 30, 'preco' => 50, 'ativo' => true]);
});

function makeAgResumoHoje(array $attrs): Agendamento
{
    return Agendamento::create(array_merge([
        'company_id' => Company::first()->id,
        'cancel_token' => Agendamento::generateCancelToken(),
        'data_hora' => today()->setHour(10),
        'duracao' => 30,
        'valor' => 50,
        'status' => Agendamento::STATUS_PENDENTE,
    ], $attrs));
}

describe('agendamento_resumo_hoje', function () {
    it('retorna estrutura correta com zeros quando sem agendamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.resumo-hoje'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys([
            'total', 'pendentes', 'confirmados', 'em_atendimento',
            'finalizados', 'cancelados', 'receita_dia', 'proximo_horario',
        ]);
        expect($data['total'])->toBe(0);
        expect((float) $data['receita_dia'])->toBe(0.0);
        expect($data['proximo_horario'])->toBeNull();
    });

    it('conta agendamentos por status', function () {
        makeAgResumoHoje(['company_id' => $this->company->id, 'cliente_id' => $this->cliente->id, 'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id, 'status' => Agendamento::STATUS_PENDENTE]);
        makeAgResumoHoje(['company_id' => $this->company->id, 'cliente_id' => $this->cliente->id, 'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id, 'status' => Agendamento::STATUS_CONFIRMADO]);
        makeAgResumoHoje(['company_id' => $this->company->id, 'cliente_id' => $this->cliente->id, 'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id, 'status' => Agendamento::STATUS_FINALIZADO, 'valor' => 80]);
        makeAgResumoHoje(['company_id' => $this->company->id, 'cliente_id' => $this->cliente->id, 'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id, 'status' => Agendamento::STATUS_CANCELADO]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.resumo-hoje'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(4);
        expect($data['pendentes'])->toBe(1);
        expect($data['confirmados'])->toBe(1);
        expect($data['finalizados'])->toBe(1);
        expect($data['cancelados'])->toBe(1);
        expect((float) $data['receita_dia'])->toBe(80.0);
    });

    it('não retorna dados de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-resumoh', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $clienteOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'lgpd_consent' => false]);
        $servicoOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'Z', 'duracao' => 30, 'preco' => 50, 'ativo' => true]);

        makeAgResumoHoje(['company_id' => $outra->id, 'cliente_id' => $clienteOutra->id, 'profissional_id' => $profOutra->id, 'servico_id' => $servicoOutra->id]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.resumo-hoje'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar resumo', function () {
        $this->actingAs($this->analista)
            ->getJson(route('agendamentos.resumo-hoje'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('agendamentos.resumo-hoje'))
            ->assertUnauthorized();
    });
});
