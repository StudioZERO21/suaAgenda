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
        'name' => 'Barbearia Detalhe', 'slug' => 'barbearia-detalhe',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Lucas', 'ativo' => true, 'cor' => '#aabbcc']);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Barba', 'duracao_minutos' => 20, 'preco' => 30.0, 'cor' => '#ff0000', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Roberto', 'phone' => '11999990088', 'email' => 'roberto@test.com']);

    $this->agendamento = Agendamento::create([
        'company_id' => $this->company->id,
        'profissional_id' => $this->prof->id,
        'cliente_id' => $this->cliente->id,
        'servico_id' => $this->servico->id,
        'data_hora' => now()->addDay(),
        'duracao' => 20,
        'valor' => 30.0,
        'status' => 'confirmado',
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
});

describe('agendamento_detalhe', function () {
    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.detalhe', $this->agendamento))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['id', 'data_hora', 'status', 'duracao', 'valor', 'observacao', 'cliente', 'profissional', 'servico', 'avaliacao']);
    });

    it('retorna dados completos do agendamento', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.detalhe', $this->agendamento))
            ->json();

        expect($data['id'])->toBe($this->agendamento->id);
        expect($data['status'])->toBe('confirmado');
        expect((float) $data['valor'])->toBe(30.0);
        expect($data['cliente']['name'])->toBe('Roberto');
        expect($data['profissional']['name'])->toBe('Lucas');
        expect($data['servico']['nome'])->toBe('Barba');
        expect($data['avaliacao'])->toBeNull();
    });

    it('inclui avaliação quando existir', function () {
        Avaliacao::create([
            'company_id' => $this->company->id,
            'agendamento_id' => $this->agendamento->id,
            'nota' => 5,
            'comentario' => 'Excelente!',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.detalhe', $this->agendamento))
            ->json();

        expect($data['avaliacao'])->not->toBeNull();
        expect($data['avaliacao']['nota'])->toBe(5);
        expect($data['avaliacao']['comentario'])->toBe('Excelente!');
    });

    it('não acessa agendamento de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-detalhe', 'plano' => 'trial', 'ativo' => true]);
        $agOutra = Agendamento::create([
            'company_id' => $outra->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->addDay(),
            'duracao' => 20,
            'status' => 'pendente',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('agendamentos.detalhe', $agOutra))
            ->assertForbidden();
    });

    it('analista pode ver detalhe', function () {
        $this->actingAs($this->analista)
            ->getJson(route('agendamentos.detalhe', $this->agendamento))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('agendamentos.detalhe', $this->agendamento))
            ->assertUnauthorized();
    });
});
