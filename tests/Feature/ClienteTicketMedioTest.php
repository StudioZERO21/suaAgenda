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
        'name' => 'Barbearia Ticket', 'slug' => 'barbearia-ticket',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana', 'lgpd_consent' => true]);
});

function makeAgTicket(string $companyId, string $profId, string $clienteId, string $servicoId, float $valor, string $status, Carbon\Carbon $dataHora): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => $dataHora,
        'duracao' => 30,
        'valor' => $valor,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('cliente_ticket_medio', function () {
    it('retorna estrutura correta sem agendamentos finalizados', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.ticket-medio', $this->cliente))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['cliente_id', 'cliente_nome', 'ultimos_7_dias', 'ultimos_30_dias', 'ultimos_90_dias', 'geral']);
        expect($data['geral']['total'])->toBe(0);
        expect($data['geral']['ticket_medio'])->toBeNull();
        expect((float) $data['geral']['receita'])->toBe(0.0);
    });

    it('calcula ticket médio geral a partir de finalizados', function () {
        makeAgTicket($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 60, Agendamento::STATUS_FINALIZADO, now()->subDays(5));
        makeAgTicket($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 40, Agendamento::STATUS_FINALIZADO, now()->subDays(60));

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.ticket-medio', $this->cliente))
            ->assertOk()
            ->json();

        expect($data['geral']['total'])->toBe(2);
        expect((float) $data['geral']['ticket_medio'])->toBe(50.0);
    });

    it('filtra corretamente por janelas de tempo', function () {
        // Dentro de 7 dias
        makeAgTicket($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 80, Agendamento::STATUS_FINALIZADO, now()->subDays(3));
        // Dentro de 30 dias mas fora de 7
        makeAgTicket($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 60, Agendamento::STATUS_FINALIZADO, now()->subDays(20));
        // Fora de 30 dias, dentro de 90
        makeAgTicket($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 40, Agendamento::STATUS_FINALIZADO, now()->subDays(60));

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.ticket-medio', $this->cliente))
            ->assertOk()
            ->json();

        expect($data['ultimos_7_dias']['total'])->toBe(1);
        expect($data['ultimos_30_dias']['total'])->toBe(2);
        expect($data['ultimos_90_dias']['total'])->toBe(3);
        expect($data['geral']['total'])->toBe(3);
    });

    it('ignora agendamentos não finalizados', function () {
        makeAgTicket($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 50, Agendamento::STATUS_CONFIRMADO, now()->subDays(1));

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.ticket-medio', $this->cliente))
            ->assertOk()
            ->json();

        expect($data['geral']['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('clientes.ticket-medio', $this->cliente))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('clientes.ticket-medio', $this->cliente))
            ->assertUnauthorized();
    });
});
