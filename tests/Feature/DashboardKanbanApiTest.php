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
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Kanban', 'slug' => 'barbearia-kanban',
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

function makeKanbanAg(string $companyId, string $clienteId, string $profId, string $servicoId, string $status): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId, 'cliente_id' => $clienteId,
        'profissional_id' => $profId, 'servico_id' => $servicoId,
        'data_hora' => now()->toDateString().' 10:00:00',
        'duracao' => 30, 'valor' => 50.0, 'status' => $status,
    ]);
}

describe('dashboard_kanban_api', function () {
    it('retorna estrutura correta com colunas de status', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('dashboard.kanban'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['data', 'total', 'colunas']);
        expect($data['colunas'])->toHaveKeys(['pendente', 'confirmado', 'em_atendimento', 'finalizado']);
        expect($data['total'])->toBe(0);
    });

    it('agendamento aparece na coluna correta', function () {
        makeKanbanAg($this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id, 'confirmado');

        $data = $this->actingAs($this->admin)
            ->getJson(route('dashboard.kanban'))
            ->assertOk()
            ->json();

        expect($data['colunas']['confirmado'])->toHaveCount(1);
        expect($data['colunas']['pendente'])->toBeEmpty();
        expect($data['total'])->toBe(1);
    });

    it('agendamento de ontem não aparece', function () {
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
            'data_hora' => now()->subDay()->toDateString().' 10:00:00',
            'duracao' => 30, 'valor' => 50.0, 'status' => 'confirmado',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('dashboard.kanban'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('card contém campos esperados', function () {
        makeKanbanAg($this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id, 'pendente');

        $data = $this->actingAs($this->admin)
            ->getJson(route('dashboard.kanban'))
            ->assertOk()
            ->json();

        $card = $data['colunas']['pendente'][0];
        expect($card)->toHaveKeys(['id', 'status', 'hora', 'cliente_nome', 'servico_nome', 'profissional_nome', 'valor', 'duracao']);
    });

    it('não inclui agendamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-kb', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'X', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'ativo' => true]);

        makeKanbanAg($outra->id, $cliOutra->id, $profOutra->id, $servOutra->id, 'pendente');

        $data = $this->actingAs($this->admin)
            ->getJson(route('dashboard.kanban'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar kanban', function () {
        $this->actingAs($this->analista)
            ->getJson(route('dashboard.kanban'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('dashboard.kanban'))
            ->assertUnauthorized();
    });
});
