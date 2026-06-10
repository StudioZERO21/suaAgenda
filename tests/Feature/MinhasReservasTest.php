<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'name' => 'Barbearia Reservas', 'slug' => 'barbearia-reservas',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Carlos', 'ativo' => true,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id,
        'nome' => 'Corte', 'duracao_minutos' => 30,
        'preco' => 45.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id,
        'name' => 'João Silva',
        'phone' => '11999990001',
    ]);
});

describe('minhas_reservas', function () {
    it('exibe formulário sem phone', function () {
        $this->get(route('vitrine.minhas-reservas', $this->company->slug))
            ->assertOk()
            ->assertViewIs('public.minhas-reservas')
            ->assertSee('Minhas Reservas');
    });

    it('retorna not found para empresa inexistente', function () {
        $this->get(route('vitrine.minhas-reservas', 'slug-invalido'))->assertNotFound();
    });

    it('retorna mensagem de não encontrado para telefone desconhecido', function () {
        $this->get(route('vitrine.minhas-reservas', $this->company->slug).'?phone=99900000000')
            ->assertOk()
            ->assertSee('Nenhum cadastro encontrado');
    });

    it('retorna agendamentos do cliente pelo telefone exato', function () {
        Agendamento::create([
            'company_id' => $this->company->id,
            'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->addDay()->setTime(10, 0),
            'duracao' => 30,
            'status' => 'pendente',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $this->get(route('vitrine.minhas-reservas', $this->company->slug).'?phone=11999990001')
            ->assertOk()
            ->assertSee('João Silva')
            ->assertSee('Corte');
    });

    it('encontra cliente por telefone com formatação diferente', function () {
        Agendamento::create([
            'company_id' => $this->company->id,
            'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->addDay()->setTime(10, 0),
            'duracao' => 30,
            'status' => 'pendente',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $this->get(route('vitrine.minhas-reservas', $this->company->slug).'?phone=(11)+99999-0001')
            ->assertOk()
            ->assertSee('João Silva');
    });

    it('não mistura clientes de empresas diferentes', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra', 'plano' => 'trial', 'ativo' => true]);
        // Pedro existe apenas em 'outra', não em 'barbearia-reservas'
        $clienteOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Pedro', 'phone' => '11888880002']);

        Agendamento::create([
            'company_id' => $outra->id,
            'cliente_id' => $clienteOutra->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->addDay()->setTime(10, 0),
            'duracao' => 30,
            'status' => 'pendente',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        // Buscar o telefone de Pedro em barbearia-reservas não deve achar nenhum cliente
        $this->get(route('vitrine.minhas-reservas', $this->company->slug).'?phone=11888880002')
            ->assertOk()
            ->assertSee('Nenhum cadastro encontrado');
    });
});
