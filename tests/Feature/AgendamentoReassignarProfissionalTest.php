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
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia RAP', 'slug' => 'barbearia-rap',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof1 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->prof2 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Marcos', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'ativo' => true]);

    $this->agendamento = Agendamento::create([
        'company_id' => $this->company->id,
        'cliente_id' => $this->cliente->id,
        'profissional_id' => $this->prof1->id,
        'servico_id' => $this->servico->id,
        'data_hora' => now()->addDay()->setHour(10)->setMinute(0)->setSecond(0)->toDateTimeString(),
        'duracao' => 30,
        'valor' => 50.0,
        'status' => 'pendente',
    ]);
});

describe('agendamento_reassignar_profissional', function () {
    it('admin pode reatribuir profissional', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.profissional', $this->agendamento), [
                'profissional_id' => $this->prof2->id,
            ])
            ->assertOk()
            ->assertJsonStructure(['profissional_id', 'profissional_nome', 'updated_at'])
            ->json();

        expect($data['profissional_id'])->toBe($this->prof2->id);
        expect($data['profissional_nome'])->toBe('Marcos');
        expect($this->agendamento->fresh()->profissional_id)->toBe($this->prof2->id);
    });

    it('gestor pode reatribuir profissional', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('agendamentos.profissional', $this->agendamento), [
                'profissional_id' => $this->prof2->id,
            ])
            ->assertOk();
    });

    it('analista não pode reatribuir profissional', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('agendamentos.profissional', $this->agendamento), [
                'profissional_id' => $this->prof2->id,
            ])
            ->assertForbidden();
    });

    it('rejeita profissional de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-rap', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.profissional', $this->agendamento), [
                'profissional_id' => $profOutra->id,
            ])
            ->assertStatus(404);
    });

    it('rejeita profissional inativo', function () {
        $inativo = Profissional::create(['company_id' => $this->company->id, 'name' => 'Inativo', 'ativo' => false]);

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.profissional', $this->agendamento), [
                'profissional_id' => $inativo->id,
            ])
            ->assertStatus(404);
    });

    it('rejeita id inválido', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.profissional', $this->agendamento), [
                'profissional_id' => 'nao-existe',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['profissional_id']);
    });

    it('não pode reatribuir agendamento de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra2', 'slug' => 'outra2-rap', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'Y', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'X', 'preco' => 30.0, 'duracao_minutos' => 30, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Z', 'ativo' => true]);
        $agOutra = Agendamento::create([
            'company_id' => $outra->id,
            'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id,
            'servico_id' => $servOutra->id,
            'data_hora' => now()->addDay()->setHour(11)->setMinute(0)->setSecond(0)->toDateTimeString(),
            'duracao' => 30, 'valor' => 30.0, 'status' => 'pendente',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.profissional', $agOutra), [
                'profissional_id' => $this->prof2->id,
            ])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('agendamentos.profissional', $this->agendamento), [
            'profissional_id' => $this->prof2->id,
        ])
            ->assertUnauthorized();
    });
});
