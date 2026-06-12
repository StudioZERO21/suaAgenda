<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Avaliacao;
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
        'name' => 'Barbearia AAv', 'slug' => 'barbearia-aav',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'ativo' => true]);

    $this->agendamento = Agendamento::create([
        'company_id' => $this->company->id,
        'cliente_id' => $this->cliente->id,
        'profissional_id' => $this->prof->id,
        'servico_id' => $this->servico->id,
        'data_hora' => now()->subDay()->toDateTimeString(),
        'duracao' => 30,
        'valor' => 50.0,
        'status' => Agendamento::STATUS_FINALIZADO,
    ]);
});

describe('agendamento_avaliacao', function () {
    it('admin cria avaliação para agendamento finalizado', function () {
        $data = $this->actingAs($this->admin)
            ->postJson(route('agendamentos.avaliacao', $this->agendamento), [
                'nota' => 5,
                'comentario' => 'Excelente serviço!',
            ])
            ->assertCreated()
            ->json();

        expect($data)->toHaveKeys(['id', 'nota', 'comentario', 'estrelas', 'created_at']);
        expect($data['nota'])->toBe(5);
        expect($data['comentario'])->toBe('Excelente serviço!');
        expect($data['estrelas'])->toBe('★★★★★');
        expect(Avaliacao::where('agendamento_id', $this->agendamento->id)->exists())->toBeTrue();
    });

    it('comentário é opcional', function () {
        $data = $this->actingAs($this->admin)
            ->postJson(route('agendamentos.avaliacao', $this->agendamento), ['nota' => 3])
            ->assertCreated()
            ->json();

        expect($data['nota'])->toBe(3);
        expect($data['comentario'])->toBe('');
    });

    it('não pode avaliar agendamento não finalizado', function () {
        $this->agendamento->update(['status' => Agendamento::STATUS_CONFIRMADO]);

        $this->actingAs($this->admin)
            ->postJson(route('agendamentos.avaliacao', $this->agendamento), ['nota' => 5])
            ->assertUnprocessable();
    });

    it('não pode avaliar o mesmo agendamento duas vezes', function () {
        Avaliacao::create([
            'company_id' => $this->company->id,
            'agendamento_id' => $this->agendamento->id,
            'nota' => 5,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('agendamentos.avaliacao', $this->agendamento), ['nota' => 4])
            ->assertStatus(409);
    });

    it('nota fora do range 1-5 retorna 422', function () {
        $this->actingAs($this->admin)
            ->postJson(route('agendamentos.avaliacao', $this->agendamento), ['nota' => 6])
            ->assertUnprocessable();

        $this->actingAs($this->admin)
            ->postJson(route('agendamentos.avaliacao', $this->agendamento), ['nota' => 0])
            ->assertUnprocessable();
    });

    it('analista pode criar avaliação', function () {
        $this->actingAs($this->analista)
            ->postJson(route('agendamentos.avaliacao', $this->agendamento), ['nota' => 4])
            ->assertCreated();
    });

    it('não pode avaliar agendamento de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-aav', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'S', 'preco' => 10.0, 'duracao_minutos' => 30, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'ativo' => true]);
        $agOutra = Agendamento::create([
            'company_id' => $outra->id,
            'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id,
            'servico_id' => $servOutra->id,
            'data_hora' => now()->subDay()->toDateTimeString(),
            'duracao' => 30, 'valor' => 10.0, 'status' => Agendamento::STATUS_FINALIZADO,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('agendamentos.avaliacao', $agOutra), ['nota' => 5])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->postJson(route('agendamentos.avaliacao', $this->agendamento), ['nota' => 5])
            ->assertUnauthorized();
    });
});
