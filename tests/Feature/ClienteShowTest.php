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
    $this->company = Company::create([
        'name' => 'Barbearia Show', 'slug' => 'barbearia-show',
        'plano' => 'trial', 'ativo' => true,
    ]);

    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Beatriz Alves', 'phone' => '11999990001']);

    $this->ag = Agendamento::create([
        'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
        'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
        'data_hora' => now()->subDay()->setTime(10, 0), 'duracao' => 30,
        'valor' => 50.00, 'status' => 'finalizado',
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
});

describe('cliente_show', function () {
    it('exibe perfil do cliente com stats', function () {
        $this->actingAs($this->user)
            ->get(route('clientes.show', $this->cliente))
            ->assertOk()
            ->assertSee('Beatriz Alves')
            ->assertSee('Agendamentos')
            ->assertSee('Receita Total');
    });

    it('exibe total de agendamentos corretamente', function () {
        $data = $this->actingAs($this->user)
            ->get(route('clientes.show', $this->cliente))
            ->getOriginalContent()
            ->getData();

        expect($data['totalAgendamentos'])->toBe(1);
    });

    it('exibe receita total corretamente', function () {
        $data = $this->actingAs($this->user)
            ->get(route('clientes.show', $this->cliente))
            ->getOriginalContent()
            ->getData();

        expect($data['receitaTotal'])->toBe(50.0);
    });

    it('exibe nota média quando há avaliação', function () {
        Avaliacao::create([
            'company_id' => $this->company->id,
            'agendamento_id' => $this->ag->id,
            'nota' => 4,
        ]);

        $this->actingAs($this->user)
            ->get(route('clientes.show', $this->cliente))
            ->assertOk()
            ->assertSee('4/5');
    });

    it('nota média é null quando sem avaliações', function () {
        $data = $this->actingAs($this->user)
            ->get(route('clientes.show', $this->cliente))
            ->getOriginalContent()
            ->getData();

        expect($data['notaMedia'])->toBeNull();
    });

    it('impede acesso a cliente de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-show', 'plano' => 'trial', 'ativo' => true]);
        $clienteOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Estranho', 'phone' => '11888880001']);

        $this->actingAs($this->user)
            ->get(route('clientes.show', $clienteOutra))
            ->assertForbidden();
    });
});
