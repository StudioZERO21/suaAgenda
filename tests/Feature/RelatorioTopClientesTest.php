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
        'name' => 'Barbearia Top CLI', 'slug' => 'barbearia-tc',
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
});

function makeTopCliAg(string $companyId, string $clienteId, string $profId, string $servicoId, float $valor = 50.0, string $status = 'finalizado'): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId, 'cliente_id' => $clienteId,
        'profissional_id' => $profId, 'servico_id' => $servicoId,
        'data_hora' => now()->subDays(rand(1, 20))->toDateTimeString(),
        'duracao' => 30, 'valor' => $valor, 'status' => $status,
    ]);
}

describe('relatorio_top_clientes', function () {
    it('retorna lista vazia quando sem agendamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.top-clientes'))
            ->assertOk()
            ->json();

        expect($data)->toBeEmpty();
    });

    it('retorna clientes ordenados por visitas', function () {
        $c1 = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'ativo' => true]);
        $c2 = Cliente::create(['company_id' => $this->company->id, 'name' => 'Maria', 'ativo' => true]);

        makeTopCliAg($this->company->id, $c1->id, $this->prof->id, $this->servico->id);
        makeTopCliAg($this->company->id, $c1->id, $this->prof->id, $this->servico->id);
        makeTopCliAg($this->company->id, $c2->id, $this->prof->id, $this->servico->id);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.top-clientes'))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(2);
        expect($data[0]['name'])->toBe('João');
        expect($data[0]['total_visitas'])->toBe(2);
    });

    it('item contém campos esperados', function () {
        $c = Cliente::create(['company_id' => $this->company->id, 'name' => 'Teste', 'ativo' => true]);
        makeTopCliAg($this->company->id, $c->id, $this->prof->id, $this->servico->id);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.top-clientes'))
            ->assertOk()
            ->json();

        expect($data[0])->toHaveKeys(['cliente_id', 'name', 'phone', 'email', 'total_visitas', 'total_gasto']);
    });

    it('ignora agendamentos cancelados', function () {
        $c = Cliente::create(['company_id' => $this->company->id, 'name' => 'Teste', 'ativo' => true]);
        makeTopCliAg($this->company->id, $c->id, $this->prof->id, $this->servico->id, 50.0, 'cancelado');

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.top-clientes'))
            ->assertOk()
            ->json();

        expect($data)->toBeEmpty();
    });

    it('não inclui clientes de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-tc', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'X', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'ativo' => true]);
        makeTopCliAg($outra->id, $cliOutra->id, $profOutra->id, $servOutra->id);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.top-clientes'))
            ->assertOk()
            ->json();

        expect($data)->toBeEmpty();
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('relatorios.top-clientes'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('relatorios.top-clientes'))
            ->assertUnauthorized();
    });
});
