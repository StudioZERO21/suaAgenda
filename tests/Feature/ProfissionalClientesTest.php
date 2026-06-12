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
        'name' => 'Barbearia ProfClients', 'slug' => 'barbearia-profclients',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->clienteA = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana', 'lgpd_consent' => true]);
    $this->clienteB = Cliente::create(['company_id' => $this->company->id, 'name' => 'Bruno', 'lgpd_consent' => true]);
});

function makeAgProfClientes(string $companyId, string $profId, string $clienteId, string $servicoId, string $status): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => now()->subDays(5),
        'duracao' => 30,
        'valor' => 50,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('profissional_clientes', function () {
    it('retorna estrutura correta sem agendamentos finalizados', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.clientes', $this->prof))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['profissional_id', 'profissional_nome', 'total', 'items']);
        expect($data['total'])->toBe(0);
        expect($data['items'])->toBeEmpty();
    });

    it('retorna clientes únicos que o profissional atendeu', function () {
        // clienteA atendido 2x — deve aparecer 1 vez
        makeAgProfClientes($this->company->id, $this->prof->id, $this->clienteA->id, $this->servico->id, Agendamento::STATUS_FINALIZADO);
        makeAgProfClientes($this->company->id, $this->prof->id, $this->clienteA->id, $this->servico->id, Agendamento::STATUS_FINALIZADO);
        makeAgProfClientes($this->company->id, $this->prof->id, $this->clienteB->id, $this->servico->id, Agendamento::STATUS_FINALIZADO);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.clientes', $this->prof))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(2);
        $ids = collect($data['items'])->pluck('id')->all();
        expect($ids)->toContain($this->clienteA->id);
        expect($ids)->toContain($this->clienteB->id);
    });

    it('ignora agendamentos não finalizados', function () {
        makeAgProfClientes($this->company->id, $this->prof->id, $this->clienteA->id, $this->servico->id, Agendamento::STATUS_CONFIRMADO);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.clientes', $this->prof))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('profissionais.clientes', $this->prof))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('profissionais.clientes', $this->prof))
            ->assertUnauthorized();
    });
});
