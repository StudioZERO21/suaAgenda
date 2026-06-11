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
        'name' => 'Barbearia Proximos', 'slug' => 'barbearia-proximos',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id, 'name' => 'Maria', 'ativo' => true,
    ]);

    $this->prof = Profissional::create([
        'company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte', 'preco' => 50.0,
        'duracao_minutos' => 30, 'ativo' => true,
    ]);
});

function makeProximoAg(string $companyId, string $clienteId, string $profId, string $servicoId, string $dataHora, string $status): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId, 'cliente_id' => $clienteId,
        'profissional_id' => $profId, 'servico_id' => $servicoId,
        'data_hora' => $dataHora, 'duracao' => 30, 'valor' => 50.0, 'status' => $status,
    ]);
}

describe('cliente_proximos', function () {
    it('retorna lista vazia quando sem agendamentos futuros', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.proximos', $this->cliente))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
        expect($data['items'])->toBeEmpty();
    });

    it('retorna agendamentos futuros pendentes e confirmados', function () {
        makeProximoAg($this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id, now()->addDays(1)->toDateTimeString(), 'pendente');
        makeProximoAg($this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id, now()->addDays(2)->toDateTimeString(), 'confirmado');

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.proximos', $this->cliente))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(2);
        expect($data['items'][0])->toHaveKeys(['id', 'data_hora', 'servico_nome', 'status', 'valor', 'duracao']);
    });

    it('ignora agendamentos passados', function () {
        makeProximoAg($this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id, now()->subDays(1)->toDateTimeString(), 'confirmado');

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.proximos', $this->cliente))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('ignora agendamentos finalizados e cancelados', function () {
        makeProximoAg($this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id, now()->addDays(1)->toDateTimeString(), 'finalizado');
        makeProximoAg($this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id, now()->addDays(2)->toDateTimeString(), 'cancelado');

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.proximos', $this->cliente))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('respeita o limite de resultados', function () {
        for ($i = 1; $i <= 5; $i++) {
            makeProximoAg($this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id, now()->addDays($i)->toDateTimeString(), 'confirmado');
        }

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.proximos', $this->cliente).'?limite=3')
            ->assertOk()
            ->json();

        expect($data['items'])->toHaveCount(3);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('clientes.proximos', $this->cliente))
            ->assertOk();
    });

    it('não pode acessar cliente de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-prox', 'plano' => 'trial', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->getJson(route('clientes.proximos', $cliOutra))
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('clientes.proximos', $this->cliente))
            ->assertUnauthorized();
    });
});
