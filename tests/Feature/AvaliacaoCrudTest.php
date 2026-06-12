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
        'name' => 'Barbearia ACrud', 'slug' => 'barbearia-acrud',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

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

    $this->avaliacao = Avaliacao::create([
        'company_id' => $this->company->id,
        'agendamento_id' => $this->agendamento->id,
        'nota' => 4,
        'comentario' => 'Bom serviço',
    ]);
});

describe('avaliacao_crud', function () {
    it('admin atualiza avaliação', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('avaliacoes.update', $this->avaliacao), ['nota' => 5, 'comentario' => 'Excelente!'])
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['id', 'nota', 'comentario', 'estrelas', 'updated_at']);
        expect($data['nota'])->toBe(5);
        expect($data['comentario'])->toBe('Excelente!');
        expect($data['estrelas'])->toBe('★★★★★');
        expect($this->avaliacao->fresh()->nota)->toBe(5);
    });

    it('gestor atualiza avaliação', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('avaliacoes.update', $this->avaliacao), ['nota' => 3])
            ->assertOk();
    });

    it('analista não pode atualizar avaliação', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('avaliacoes.update', $this->avaliacao), ['nota' => 1])
            ->assertForbidden();
    });

    it('nota fora do range retorna 422', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('avaliacoes.update', $this->avaliacao), ['nota' => 6])
            ->assertUnprocessable();
    });

    it('admin exclui avaliação', function () {
        $this->actingAs($this->admin)
            ->deleteJson(route('avaliacoes.destroy', $this->avaliacao))
            ->assertNoContent();

        expect(Avaliacao::find($this->avaliacao->id))->toBeNull();
    });

    it('gestor exclui avaliação', function () {
        $this->actingAs($this->gestor)
            ->deleteJson(route('avaliacoes.destroy', $this->avaliacao))
            ->assertNoContent();
    });

    it('analista não pode excluir avaliação', function () {
        $this->actingAs($this->analista)
            ->deleteJson(route('avaliacoes.destroy', $this->avaliacao))
            ->assertForbidden();
    });

    it('não pode manipular avaliação de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-acrud', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'S', 'preco' => 10.0, 'duracao_minutos' => 30, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'ativo' => true]);
        $agOutra = Agendamento::create([
            'company_id' => $outra->id, 'cliente_id' => $cliOutra->id, 'profissional_id' => $profOutra->id,
            'servico_id' => $servOutra->id, 'data_hora' => now()->subDay()->toDateTimeString(),
            'duracao' => 30, 'valor' => 10.0, 'status' => Agendamento::STATUS_FINALIZADO,
        ]);
        $avalOutra = Avaliacao::create(['company_id' => $outra->id, 'agendamento_id' => $agOutra->id, 'nota' => 5]);

        $this->actingAs($this->admin)
            ->deleteJson(route('avaliacoes.destroy', $avalOutra))
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->deleteJson(route('avaliacoes.destroy', $this->avaliacao))
            ->assertUnauthorized();
    });
});
