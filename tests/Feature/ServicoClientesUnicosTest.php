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
        'name' => 'Barbearia CliUniq', 'slug' => 'barbearia-cliuniq',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->c1 = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana', 'lgpd_consent' => true]);
    $this->c2 = Cliente::create(['company_id' => $this->company->id, 'name' => 'Bruno', 'lgpd_consent' => true]);
});

function makeAgCliUniq(string $companyId, string $profId, string $clienteId, string $servicoId, string $status, float $valor = 50.0): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => now()->subDays(5),
        'duracao' => 30,
        'valor' => $valor,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('servico_clientes_unicos', function () {
    it('retorna lista vazia sem agendamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.clientes-unicos', $this->servico))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['servico_id', 'servico_nome', 'total', 'items']);
        expect($data['total'])->toBe(0);
        expect($data['items'])->toBeEmpty();
    });

    it('lista clientes únicos com totais', function () {
        makeAgCliUniq($this->company->id, $this->prof->id, $this->c1->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 80.0);
        makeAgCliUniq($this->company->id, $this->prof->id, $this->c1->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 80.0);
        makeAgCliUniq($this->company->id, $this->prof->id, $this->c2->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 60.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.clientes-unicos', $this->servico))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(2);

        $ana = collect($data['items'])->firstWhere('cliente_nome', 'Ana');
        expect($ana['total_agendamentos'])->toBe(2);
        expect((float) $ana['receita_total'])->toBe(160.0);
    });

    it('exclui agendamentos não finalizados', function () {
        makeAgCliUniq($this->company->id, $this->prof->id, $this->c1->id, $this->servico->id, Agendamento::STATUS_CANCELADO);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.clientes-unicos', $this->servico))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('servicos.clientes-unicos', $this->servico))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('servicos.clientes-unicos', $this->servico))
            ->assertUnauthorized();
    });
});
