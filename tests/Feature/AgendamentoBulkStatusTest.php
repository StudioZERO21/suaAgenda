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
        'name' => 'Barbearia Bulk', 'slug' => 'barbearia-bulk',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'phone' => '11999990001']);
});

function makeBulkAg($self, string $status = 'confirmado'): Agendamento
{
    return Agendamento::create([
        'company_id' => $self->company->id,
        'cliente_id' => $self->cliente->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $self->servico->id,
        'data_hora' => now()->addHours(2),
        'duracao' => 30,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('agendamento_bulk_status', function () {
    it('admin pode atualizar status em bulk', function () {
        $ag1 = makeBulkAg($this, 'confirmado');
        $ag2 = makeBulkAg($this, 'confirmado');

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.bulk-status'), [
                'ids' => [$ag1->id, $ag2->id],
                'status' => 'finalizado',
            ])
            ->assertOk()
            ->assertJson(['updated' => 2]);

        expect($ag1->fresh()->status)->toBe('finalizado');
        expect($ag2->fresh()->status)->toBe('finalizado');
    });

    it('gestor pode atualizar status em bulk', function () {
        $ag = makeBulkAg($this, 'confirmado');

        $this->actingAs($this->gestor)
            ->patchJson(route('agendamentos.bulk-status'), [
                'ids' => [$ag->id],
                'status' => 'finalizado',
            ])
            ->assertOk()
            ->assertJson(['updated' => 1]);
    });

    it('analista não pode atualizar em bulk', function () {
        $ag = makeBulkAg($this);

        $this->actingAs($this->analista)
            ->patchJson(route('agendamentos.bulk-status'), [
                'ids' => [$ag->id],
                'status' => 'finalizado',
            ])
            ->assertForbidden();
    });

    it('status inválido retorna erro 422', function () {
        $ag = makeBulkAg($this);

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.bulk-status'), [
                'ids' => [$ag->id],
                'status' => 'invalido',
            ])
            ->assertUnprocessable();
    });

    it('isolamento: não atualiza agendamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-bulk', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'S', 'duracao_minutos' => 30, 'preco' => 10, 'cor' => '#000', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'phone' => '11111111111']);

        $agOutra = Agendamento::create([
            'company_id' => $outra->id, 'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id, 'servico_id' => $servOutra->id,
            'data_hora' => now()->addHour(), 'duracao' => 30,
            'status' => 'confirmado', 'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.bulk-status'), [
                'ids' => [$agOutra->id],
                'status' => 'finalizado',
            ])
            ->assertOk()
            ->assertJson(['updated' => 0]);

        expect($agOutra->fresh()->status)->toBe('confirmado');
    });

    it('unauthenticated é redirecionado', function () {
        $ag = makeBulkAg($this);

        $this->patchJson(route('agendamentos.bulk-status'), [
            'ids' => [$ag->id],
            'status' => 'finalizado',
        ])->assertUnauthorized();
    });
});
