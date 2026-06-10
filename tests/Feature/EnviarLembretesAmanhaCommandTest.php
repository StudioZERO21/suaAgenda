<?php

declare(strict_types=1);

use App\Mail\LembreteAgendamento;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'name' => 'Barbearia Lem', 'slug' => 'barbearia-lem',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);
});

function makeLembreteAg($self, Cliente $cliente, string $status, int $diasAFrente = 1): Agendamento
{
    return Agendamento::create([
        'company_id' => $self->company->id,
        'cliente_id' => $cliente->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $self->servico->id,
        'data_hora' => now()->addDays($diasAFrente)->setTime(10, 0),
        'duracao' => 30,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('enviar_lembretes_amanha', function () {
    it('envia email para cliente confirmado com email amanhã', function () {
        Mail::fake();

        $cliente = Cliente::create([
            'company_id' => $this->company->id,
            'name' => 'João', 'phone' => '11999990001', 'email' => 'joao@test.com',
        ]);
        makeLembreteAg($this, $cliente, Agendamento::STATUS_CONFIRMADO);

        $this->artisan('agendamentos:lembretes-amanha')->assertSuccessful();

        Mail::assertQueued(LembreteAgendamento::class, 1);
        Mail::assertQueued(LembreteAgendamento::class, fn ($mail) => $mail->hasTo('joao@test.com'));
    });

    it('envia email para cliente pendente com email amanhã', function () {
        Mail::fake();

        $cliente = Cliente::create([
            'company_id' => $this->company->id,
            'name' => 'Maria', 'phone' => '11999990002', 'email' => 'maria@test.com',
        ]);
        makeLembreteAg($this, $cliente, Agendamento::STATUS_PENDENTE);

        $this->artisan('agendamentos:lembretes-amanha')->assertSuccessful();

        Mail::assertQueued(LembreteAgendamento::class, 1);
    });

    it('não envia para agendamento finalizado', function () {
        Mail::fake();

        $cliente = Cliente::create([
            'company_id' => $this->company->id,
            'name' => 'Carlos', 'phone' => '11999990003', 'email' => 'carlos@test.com',
        ]);
        makeLembreteAg($this, $cliente, Agendamento::STATUS_FINALIZADO);

        $this->artisan('agendamentos:lembretes-amanha')->assertSuccessful();

        Mail::assertNothingQueued();
    });

    it('não envia para agendamento cancelado', function () {
        Mail::fake();

        $cliente = Cliente::create([
            'company_id' => $this->company->id,
            'name' => 'Pedro', 'phone' => '11999990004', 'email' => 'pedro@test.com',
        ]);
        makeLembreteAg($this, $cliente, Agendamento::STATUS_CANCELADO);

        $this->artisan('agendamentos:lembretes-amanha')->assertSuccessful();

        Mail::assertNothingQueued();
    });

    it('não envia quando cliente não tem email', function () {
        Mail::fake();

        $cliente = Cliente::create([
            'company_id' => $this->company->id,
            'name' => 'Sem Email', 'phone' => '11999990005',
        ]);
        makeLembreteAg($this, $cliente, Agendamento::STATUS_CONFIRMADO);

        $this->artisan('agendamentos:lembretes-amanha')->assertSuccessful();

        Mail::assertNothingQueued();
    });

    it('não envia para agendamento hoje (não é amanhã)', function () {
        Mail::fake();

        $cliente = Cliente::create([
            'company_id' => $this->company->id,
            'name' => 'Hoje', 'phone' => '11999990006', 'email' => 'hoje@test.com',
        ]);

        Agendamento::create([
            'company_id' => $this->company->id,
            'cliente_id' => $cliente->id,
            'profissional_id' => $this->prof->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->setTime(14, 0),
            'duracao' => 30,
            'status' => Agendamento::STATUS_CONFIRMADO,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $this->artisan('agendamentos:lembretes-amanha')->assertSuccessful();

        Mail::assertNothingQueued();
    });

    it('envia para múltiplos clientes', function () {
        Mail::fake();

        $c1 = Cliente::create(['company_id' => $this->company->id, 'name' => 'A', 'phone' => '11000000001', 'email' => 'a@test.com']);
        $c2 = Cliente::create(['company_id' => $this->company->id, 'name' => 'B', 'phone' => '11000000002', 'email' => 'b@test.com']);
        $c3 = Cliente::create(['company_id' => $this->company->id, 'name' => 'C', 'phone' => '11000000003']);

        makeLembreteAg($this, $c1, Agendamento::STATUS_CONFIRMADO);
        makeLembreteAg($this, $c2, Agendamento::STATUS_PENDENTE);
        makeLembreteAg($this, $c3, Agendamento::STATUS_CONFIRMADO);

        $this->artisan('agendamentos:lembretes-amanha')->assertSuccessful();

        Mail::assertQueued(LembreteAgendamento::class, 2);
    });
});
