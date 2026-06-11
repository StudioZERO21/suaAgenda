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
        'name' => 'Barbearia Confirmado', 'slug' => 'barbearia-confirmado',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true,
    ]);

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true,
    ]);

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id, 'name' => 'Pedro', 'phone' => '11999990066',
    ]);

    $this->agendamento = Agendamento::create([
        'company_id' => $this->company->id,
        'profissional_id' => $this->profissional->id,
        'servico_id' => $this->servico->id,
        'cliente_id' => $this->cliente->id,
        'data_hora' => now()->addDay()->setTime(14, 0),
        'duracao' => 30,
        'status' => Agendamento::STATUS_CONFIRMADO,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
});

describe('agendar_confirmado', function () {
    it('exibe a página de confirmação para agendamento válido', function () {
        $this->get(route('agendar.confirmado', [
            'slug' => $this->company->slug,
            'agendamento' => $this->agendamento->id,
        ]))
            ->assertOk()
            ->assertViewIs('public.agendado');
    });

    it('passa os dados do agendamento para a view', function () {
        $response = $this->get(route('agendar.confirmado', [
            'slug' => $this->company->slug,
            'agendamento' => $this->agendamento->id,
        ]));

        $response->assertViewHas('ag');
        $response->assertViewHas('company');
        $ag = $response->viewData('ag');
        expect($ag->id)->toBe($this->agendamento->id);
    });

    it('retorna 404 para slug inexistente', function () {
        $this->get(route('agendar.confirmado', [
            'slug' => 'slug-nao-existe',
            'agendamento' => $this->agendamento->id,
        ]))->assertNotFound();
    });

    it('retorna 404 para agendamento de outra empresa', function () {
        $outra = Company::create([
            'name' => 'Outra', 'slug' => 'outra-confirmado', 'plano' => 'trial', 'ativo' => true,
        ]);

        $this->get(route('agendar.confirmado', [
            'slug' => $outra->slug,
            'agendamento' => $this->agendamento->id,
        ]))->assertNotFound();
    });

    it('retorna 404 para agendamento inexistente', function () {
        $this->get(route('agendar.confirmado', [
            'slug' => $this->company->slug,
            'agendamento' => 'uuid-nao-existe',
        ]))->assertNotFound();
    });

    it('carrega relacionamentos de servico e profissional', function () {
        $response = $this->get(route('agendar.confirmado', [
            'slug' => $this->company->slug,
            'agendamento' => $this->agendamento->id,
        ]));

        $ag = $response->viewData('ag');
        expect($ag->servico)->not->toBeNull();
        expect($ag->profissional)->not->toBeNull();
    });
});
