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
        'name' => 'Barbearia Meu', 'slug' => 'barbearia-meu',
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
        'company_id' => $this->company->id, 'name' => 'João', 'phone' => '11999990055',
    ]);

    $this->agendamento = Agendamento::create([
        'company_id' => $this->company->id,
        'profissional_id' => $this->profissional->id,
        'servico_id' => $this->servico->id,
        'cliente_id' => $this->cliente->id,
        'data_hora' => now()->addDay()->setTime(10, 0),
        'duracao' => 30,
        'status' => Agendamento::STATUS_CONFIRMADO,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
});

describe('meu_agendamento', function () {
    it('exibe página de agendamento com token válido', function () {
        $this->get(route('agendamento.meu', $this->agendamento->cancel_token))
            ->assertOk()
            ->assertViewIs('public.meu-agendamento');
    });

    it('retorna 404 para token inválido', function () {
        $this->get(route('agendamento.meu', 'token-inexistente'))
            ->assertNotFound();
    });

    it('agendamento futuro confirmado é cancelável', function () {
        $response = $this->get(route('agendamento.meu', $this->agendamento->cancel_token));
        $response->assertOk();
        $response->assertViewHas('cancelavel', true);
    });

    it('agendamento passado não é cancelável', function () {
        $this->agendamento->update(['data_hora' => now()->subDay()]);
        $response = $this->get(route('agendamento.meu', $this->agendamento->cancel_token));
        $response->assertOk();
        $response->assertViewHas('cancelavel', false);
    });

    it('agendamento cancelado não é cancelável', function () {
        $this->agendamento->update(['status' => Agendamento::STATUS_CANCELADO]);
        $response = $this->get(route('agendamento.meu', $this->agendamento->cancel_token));
        $response->assertOk();
        $response->assertViewHas('cancelavel', false);
    });
});

describe('cancelar_meu_agendamento', function () {
    it('cancela agendamento futuro confirmado via token', function () {
        $this->post(route('agendamento.cancelar', $this->agendamento->cancel_token))
            ->assertRedirect(route('agendamento.meu', $this->agendamento->cancel_token));

        expect($this->agendamento->fresh()->status)->toBe(Agendamento::STATUS_CANCELADO);
    });

    it('não cancela agendamento já finalizado', function () {
        $this->agendamento->update(['status' => Agendamento::STATUS_FINALIZADO]);

        $this->post(route('agendamento.cancelar', $this->agendamento->cancel_token))
            ->assertRedirect();

        expect($this->agendamento->fresh()->status)->toBe(Agendamento::STATUS_FINALIZADO);
    });

    it('não cancela agendamento no passado', function () {
        $this->agendamento->update(['data_hora' => now()->subHour()]);

        $this->post(route('agendamento.cancelar', $this->agendamento->cancel_token))
            ->assertRedirect();

        expect($this->agendamento->fresh()->status)->toBe(Agendamento::STATUS_CONFIRMADO);
    });

    it('retorna 404 para token inválido ao cancelar', function () {
        $this->post(route('agendamento.cancelar', 'token-falso'))
            ->assertNotFound();
    });
});
