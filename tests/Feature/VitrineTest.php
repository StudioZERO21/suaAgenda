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
        'name' => 'Vitrine Barbearia', 'slug' => 'vitrine-barbearia',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true,
    ]);

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true,
    ]);
});

describe('vitrine_landing', function () {
    it('exibe a vitrine pública pelo slug', function () {
        $this->get(route('vitrine.show', $this->company->slug))
            ->assertOk()
            ->assertViewIs('public.vitrine')
            ->assertSee($this->company->name);
    });

    it('retorna 404 para slug inexistente', function () {
        $this->get(route('vitrine.show', 'slug-nao-existe'))
            ->assertNotFound();
    });

    it('retorna 404 para empresa inativa', function () {
        $this->company->update(['ativo' => false]);
        $this->get(route('vitrine.show', $this->company->slug))
            ->assertNotFound();
    });

    it('exibe lista de serviços ativos na vitrine', function () {
        $this->get(route('vitrine.show', $this->company->slug))
            ->assertOk()
            ->assertViewHas('servicos');
    });

    it('não exibe serviço inativo na vitrine', function () {
        $inativo = Servico::create([
            'company_id' => $this->company->id, 'nome' => 'ServiçoInativo',
            'duracao_minutos' => 30, 'preco' => 20.0, 'cor' => '#000', 'ativo' => false,
        ]);

        $response = $this->get(route('vitrine.show', $this->company->slug));
        $response->assertOk();
        $servicos = $response->viewData('servicos');
        expect($servicos->pluck('id')->contains($inativo->id))->toBeFalse();
    });
});

describe('minhas_reservas', function () {
    it('exibe página sem reservas quando sem telefone', function () {
        $this->get(route('vitrine.minhas-reservas', $this->company->slug))
            ->assertOk()
            ->assertViewIs('public.minhas-reservas');
    });

    it('exibe reservas do cliente quando telefone é informado', function () {
        $cliente = Cliente::create([
            'company_id' => $this->company->id, 'name' => 'Maria', 'phone' => '11999990088',
        ]);

        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'cliente_id' => $cliente->id,
            'data_hora' => now()->addDays(2),
            'duracao' => 30,
            'status' => Agendamento::STATUS_CONFIRMADO,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $response = $this->get(route('vitrine.minhas-reservas', $this->company->slug).'?phone=11999990088');
        $response->assertOk();
        $agendamentos = $response->viewData('agendamentos');
        expect($agendamentos->count())->toBe(1);
    });

    it('retorna lista vazia para telefone sem cadastro', function () {
        $response = $this->get(route('vitrine.minhas-reservas', $this->company->slug).'?phone=11000000000');
        $response->assertOk();
        $agendamentos = $response->viewData('agendamentos');
        expect($agendamentos)->toBeEmpty();
    });

    it('retorna 404 para slug inexistente', function () {
        $this->get(route('vitrine.minhas-reservas', 'slug-falso'))
            ->assertNotFound();
    });
});
