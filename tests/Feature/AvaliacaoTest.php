<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'name' => 'Barbearia Aval', 'slug' => 'barbearia-aval',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana', 'phone' => '11999990001']);

    $this->token = Agendamento::generateCancelToken();
    $this->ag = Agendamento::create([
        'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
        'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
        'data_hora' => now()->subDay()->setTime(10, 0),
        'duracao' => 30, 'status' => 'finalizado', 'valor' => 50.00,
        'cancel_token' => $this->token,
    ]);
});

describe('avaliacoes', function () {
    it('exibe formulário de avaliação para agendamento finalizado', function () {
        $this->get(route('avaliacao.show', $this->token))
            ->assertOk()
            ->assertSee('Como foi seu atendimento?');
    });

    it('retorna 404 para agendamento não finalizado', function () {
        $token2 = Agendamento::generateCancelToken();
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
            'data_hora' => now()->addDay()->setTime(10, 0),
            'duracao' => 30, 'status' => 'pendente', 'cancel_token' => $token2,
        ]);

        $this->get(route('avaliacao.show', $token2))->assertNotFound();
    });

    it('retorna 410 quando já foi avaliado', function () {
        Avaliacao::create([
            'company_id' => $this->company->id,
            'agendamento_id' => $this->ag->id,
            'nota' => 5,
        ]);

        $this->get(route('avaliacao.show', $this->token))->assertStatus(410);
    });

    it('salva avaliação com nota válida', function () {
        $this->post(route('avaliacao.store', $this->token), ['nota' => 5, 'comentario' => 'Ótimo!'])
            ->assertRedirect(route('agendamento.meu', $this->token));

        $this->assertDatabaseHas('avaliacoes', [
            'agendamento_id' => $this->ag->id,
            'nota' => 5,
            'comentario' => 'Ótimo!',
        ]);
    });

    it('rejeita nota inválida', function () {
        $this->post(route('avaliacao.store', $this->token), ['nota' => 6])
            ->assertSessionHasErrors('nota');
    });

    it('não permite segunda avaliação para o mesmo agendamento', function () {
        Avaliacao::create([
            'company_id' => $this->company->id,
            'agendamento_id' => $this->ag->id,
            'nota' => 4,
        ]);

        $this->post(route('avaliacao.store', $this->token), ['nota' => 5])->assertStatus(422);
    });

    it('meu-agendamento exibe avaliação existente', function () {
        Avaliacao::create([
            'company_id' => $this->company->id,
            'agendamento_id' => $this->ag->id,
            'nota' => 4,
        ]);

        $this->get(route('agendamento.meu', $this->token))
            ->assertOk()
            ->assertSee('Sua avaliação: 4/5');
    });

    it('meu-agendamento exibe link avaliar quando finalizado e não avaliado', function () {
        $this->get(route('agendamento.meu', $this->token))
            ->assertOk()
            ->assertSee('Avaliar atendimento');
    });
});
