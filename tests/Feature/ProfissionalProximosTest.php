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
        'name' => 'Barbearia Proximos Prof', 'slug' => 'barbearia-pp',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create([
        'company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte', 'preco' => 50.0,
        'duracao_minutos' => 30, 'ativo' => true,
    ]);

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id, 'name' => 'João', 'ativo' => true,
    ]);
});

function makeProfProximoAg(string $companyId, string $clienteId, string $profId, string $servicoId, string $dataHora, string $status = 'confirmado'): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId, 'cliente_id' => $clienteId,
        'profissional_id' => $profId, 'servico_id' => $servicoId,
        'data_hora' => $dataHora, 'duracao' => 30, 'valor' => 50.0, 'status' => $status,
    ]);
}

describe('profissional_proximos', function () {
    it('retorna estrutura correta com lista vazia', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.proximos', $this->prof))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['total', 'items']);
        expect($data['total'])->toBe(0);
        expect($data['items'])->toBeEmpty();
    });

    it('retorna agendamentos futuros confirmados e pendentes', function () {
        makeProfProximoAg($this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id, now()->addHour()->toDateTimeString(), 'confirmado');
        makeProfProximoAg($this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id, now()->addHours(2)->toDateTimeString(), 'pendente');

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.proximos', $this->prof))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(2);
        expect($data['items'][0])->toHaveKeys(['id', 'data_hora', 'status', 'valor', 'cliente', 'servico']);
    });

    it('ignora agendamentos passados', function () {
        makeProfProximoAg($this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id, now()->subHour()->toDateTimeString(), 'confirmado');

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.proximos', $this->prof))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('ignora agendamentos finalizados e cancelados', function () {
        makeProfProximoAg($this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id, now()->addHour()->toDateTimeString(), 'finalizado');
        makeProfProximoAg($this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id, now()->addHours(2)->toDateTimeString(), 'cancelado');

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.proximos', $this->prof))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('profissionais.proximos', $this->prof))
            ->assertOk();
    });

    it('não pode acessar profissional de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-pp', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->getJson(route('profissionais.proximos', $profOutra))
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('profissionais.proximos', $this->prof))
            ->assertUnauthorized();
    });
});
