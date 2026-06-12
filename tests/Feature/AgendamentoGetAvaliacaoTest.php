<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Avaliacao;
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
        'name' => 'Barbearia GAvl', 'slug' => 'barbearia-gavl',
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

describe('agendamento_get_avaliacao', function () {
    it('retorna avaliado=false quando sem avaliação', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.avaliacao.get', $this->agendamento))
            ->assertOk()
            ->json();

        expect($data['avaliado'])->toBeFalse();
        expect($data['avaliacao'])->toBeNull();
    });

    it('retorna avaliação quando existe', function () {
        Avaliacao::create([
            'company_id' => $this->company->id,
            'agendamento_id' => $this->agendamento->id,
            'nota' => 4,
            'comentario' => 'Muito bom',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.avaliacao.get', $this->agendamento))
            ->assertOk()
            ->json();

        expect($data['avaliado'])->toBeTrue();
        expect($data['avaliacao'])->toHaveKeys(['id', 'nota', 'comentario', 'estrelas', 'created_at']);
        expect($data['avaliacao']['nota'])->toBe(4);
        expect($data['avaliacao']['comentario'])->toBe('Muito bom');
        expect($data['avaliacao']['estrelas'])->toBe('★★★★☆');
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('agendamentos.avaliacao.get', $this->agendamento))
            ->assertOk();
    });

    it('não pode acessar avaliação de agendamento de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-gavl', 'plano' => 'trial', 'ativo' => true]);
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
            ->getJson(route('agendamentos.avaliacao.get', $agOutra))
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('agendamentos.avaliacao.get', $this->agendamento))
            ->assertUnauthorized();
    });
});
