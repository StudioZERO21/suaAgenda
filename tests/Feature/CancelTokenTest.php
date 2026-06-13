<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\HorarioTrabalho;
use App\Models\Profissional;
use App\Models\Servico;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'name' => 'Barbearia Token', 'slug' => 'barbearia-token',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Carlos',
        'ativo' => true,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id,
        'nome' => 'Corte',
        'duracao_minutos' => 30,
        'preco' => 45.00,
        'cor' => '#1a1a1a',
        'ativo' => true,
    ]);

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id,
        'name' => 'João',
        'phone' => '11999990001',
    ]);
});

function makeAgendamento(array $attrs = []): Agendamento
{
    return Agendamento::create(array_merge([
        'company_id' => test()->company->id,
        'profissional_id' => test()->profissional->id,
        'servico_id' => test()->servico->id,
        'cliente_id' => test()->cliente->id,
        'data_hora' => now()->addDay()->setTime(10, 0),
        'duracao' => 30,
        'valor' => 45.00,
        'status' => Agendamento::STATUS_PENDENTE,
        'cancel_token' => Agendamento::generateCancelToken(),
    ], $attrs));
}

describe('cancel_token', function () {
    it('gera cancel_token ao criar agendamento via fluxo público', function () {
        $this->profissional->servicos()->attach($this->servico->id);
        HorarioTrabalho::create([
            'empresa_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'dia_semana' => (int) now()->addDay()->format('w'),
            'hora_inicio' => '08:00', 'hora_fim' => '18:00', 'ativo' => true,
        ]);

        $response = $this->post(route('agendar.store', $this->company->slug), [
            'servico_id' => $this->servico->id,
            'profissional_id' => $this->profissional->id,
            'data_hora' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'cliente_nome' => 'Maria',
            'cliente_phone' => '11999990002',
            'consent' => 1,
        ]);

        $response->assertRedirect();
        $ag = Agendamento::where('company_id', $this->company->id)->first();
        expect($ag->cancel_token)->not->toBeNull();
        expect(strlen($ag->cancel_token))->toBe(64);
    });

    it('exibe página de status via token', function () {
        $ag = makeAgendamento();
        $this->get(route('agendamento.meu', $ag->cancel_token))
            ->assertOk()
            ->assertViewIs('public.meu-agendamento')
            ->assertSee($this->servico->nome);
    });

    it('retorna 404 para token inválido', function () {
        $this->get(route('agendamento.meu', 'token-invalido-nao-existe'))->assertNotFound();
    });

    it('cancela agendamento futuro pendente', function () {
        $ag = makeAgendamento();
        $this->post(route('agendamento.cancelar', $ag->cancel_token))->assertRedirect();
        expect($ag->fresh()->status)->toBe(Agendamento::STATUS_CANCELADO);
    });

    it('não cancela agendamento já cancelado', function () {
        $ag = makeAgendamento(['status' => Agendamento::STATUS_CANCELADO]);
        $this->post(route('agendamento.cancelar', $ag->cancel_token))
            ->assertRedirect(route('agendamento.meu', $ag->cancel_token));
        expect(session('erro'))->not->toBeNull();
    });

    it('não cancela agendamento no passado', function () {
        $ag = makeAgendamento(['data_hora' => now()->subDay()->setTime(10, 0)]);
        $this->post(route('agendamento.cancelar', $ag->cancel_token))
            ->assertRedirect(route('agendamento.meu', $ag->cancel_token));
        $ag->refresh();
        expect($ag->status)->not->toBe(Agendamento::STATUS_CANCELADO);
    });

    it('não cancela agendamento finalizado', function () {
        $ag = makeAgendamento(['status' => Agendamento::STATUS_FINALIZADO]);
        $this->post(route('agendamento.cancelar', $ag->cancel_token))
            ->assertRedirect(route('agendamento.meu', $ag->cancel_token));
        expect($ag->fresh()->status)->toBe(Agendamento::STATUS_FINALIZADO);
    });
});
