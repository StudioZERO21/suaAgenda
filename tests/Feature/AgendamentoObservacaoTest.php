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
        'name' => 'Barbearia Obs', 'slug' => 'barbearia-obs',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

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

    $this->agendamento = Agendamento::create([
        'company_id' => $this->company->id,
        'cliente_id' => $this->cliente->id,
        'profissional_id' => $this->prof->id,
        'servico_id' => $this->servico->id,
        'data_hora' => now()->addDay(),
        'duracao' => 30, 'valor' => 50.0, 'status' => 'pendente',
    ]);
});

describe('agendamento_observacao', function () {
    it('admin pode salvar observação', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.observacao', $this->agendamento), [
                'observacao' => 'Cliente prefere corte baixo.',
            ])
            ->assertOk()
            ->assertJsonStructure(['observacao', 'updated_at'])
            ->json();

        expect($data['observacao'])->toBe('Cliente prefere corte baixo.');
        expect($this->agendamento->fresh()->observacao)->toBe('Cliente prefere corte baixo.');
    });

    it('gestor pode salvar observação', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('agendamentos.observacao', $this->agendamento), [
                'observacao' => 'Nota do gestor.',
            ])
            ->assertOk();
    });

    it('analista não pode salvar observação', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('agendamentos.observacao', $this->agendamento), [
                'observacao' => 'Nota do analista.',
            ])
            ->assertForbidden();
    });

    it('aceita observação nula para limpar campo', function () {
        $this->agendamento->update(['observacao' => 'texto antigo']);

        $data = $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.observacao', $this->agendamento), [
                'observacao' => null,
            ])
            ->assertOk()
            ->json();

        expect($data['observacao'])->toBe('');
    });

    it('rejeita observação maior que 1000 caracteres', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.observacao', $this->agendamento), [
                'observacao' => str_repeat('x', 1001),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['observacao']);
    });

    it('não pode editar agendamento de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-obs', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'X', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'ativo' => true]);
        $agOutra = Agendamento::create([
            'company_id' => $outra->id, 'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id, 'servico_id' => $servOutra->id,
            'data_hora' => now()->addDay(), 'duracao' => 30, 'valor' => 50.0, 'status' => 'pendente',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.observacao', $agOutra), ['observacao' => 'hack'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('agendamentos.observacao', $this->agendamento), ['observacao' => 'x'])
            ->assertUnauthorized();
    });
});
