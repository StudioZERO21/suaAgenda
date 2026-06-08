<?php

declare(strict_types=1);

use App\Mail\AgendamentoConfirmado;
use App\Mail\AgendamentoLembrete;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Teste',
        'slug' => 'empresa-teste',
        'plano' => 'trial',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Profissional Teste',
        'ativo' => true,
    ]);

    $this->clienteComEmail = Cliente::create([
        'company_id' => $this->company->id,
        'name' => 'Cliente Com Email',
        'email' => 'cliente@teste.com',
        'lgpd_consent' => true,
    ]);

    $this->clienteSemEmail = Cliente::create([
        'company_id' => $this->company->id,
        'name' => 'Cliente Sem Email',
        'lgpd_consent' => true,
    ]);
});

describe('mail AgendamentoConfirmado', function () {
    it('envia e-mail de confirmação ao criar agendamento', function () {
        Mail::fake();

        $this->actingAs($this->user)
            ->post(route('agendamentos.store'), [
                'profissional_id' => $this->profissional->id,
                'cliente_id' => $this->clienteComEmail->id,
                'data_hora' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'duracao' => 60,
            ])
            ->assertRedirect();

        Mail::assertQueued(AgendamentoConfirmado::class, function ($mail) {
            return $mail->hasTo('cliente@teste.com');
        });
    });

    it('não envia e-mail quando cliente não tem e-mail', function () {
        Mail::fake();

        $this->actingAs($this->user)
            ->post(route('agendamentos.store'), [
                'profissional_id' => $this->profissional->id,
                'cliente_id' => $this->clienteSemEmail->id,
                'data_hora' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'duracao' => 60,
            ])
            ->assertRedirect();

        Mail::assertNotQueued(AgendamentoConfirmado::class);
    });

    it('mailable tem assunto e conteúdo corretos', function () {
        $agendamento = Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'cliente_id' => $this->clienteComEmail->id,
            'data_hora' => now()->addDays(2),
            'duracao' => 60,
            'status' => Agendamento::STATUS_PENDENTE,
        ]);

        $mailable = new AgendamentoConfirmado($agendamento->load(['cliente', 'profissional', 'company']));

        expect($mailable->envelope()->subject)->toStartWith('Agendamento confirmado');
        $mailable->assertSeeInHtml($this->clienteComEmail->name);
    });
});

describe('comando agendamentos:lembretes', function () {
    it('enfileira lembretes para agendamentos de amanhã', function () {
        Mail::fake();

        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'cliente_id' => $this->clienteComEmail->id,
            'data_hora' => now()->addDay()->setTime(10, 0),
            'duracao' => 60,
            'status' => Agendamento::STATUS_CONFIRMADO,
        ]);

        $this->artisan('agendamentos:lembretes')
            ->assertExitCode(0);

        Mail::assertQueued(AgendamentoLembrete::class, 1);
    });

    it('ignora agendamentos de amanhã com status cancelado', function () {
        Mail::fake();

        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'cliente_id' => $this->clienteComEmail->id,
            'data_hora' => now()->addDay()->setTime(10, 0),
            'duracao' => 60,
            'status' => Agendamento::STATUS_CANCELADO,
        ]);

        $this->artisan('agendamentos:lembretes')
            ->assertExitCode(0);

        Mail::assertNotQueued(AgendamentoLembrete::class);
    });

    it('ignora agendamentos de amanhã sem e-mail de cliente', function () {
        Mail::fake();

        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'cliente_id' => $this->clienteSemEmail->id,
            'data_hora' => now()->addDay()->setTime(10, 0),
            'duracao' => 60,
            'status' => Agendamento::STATUS_PENDENTE,
        ]);

        $this->artisan('agendamentos:lembretes')
            ->assertExitCode(0);

        Mail::assertNotQueued(AgendamentoLembrete::class);
    });

    it('ignora agendamentos de outros dias', function () {
        Mail::fake();

        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'cliente_id' => $this->clienteComEmail->id,
            'data_hora' => now()->addDays(3)->setTime(10, 0),
            'duracao' => 60,
            'status' => Agendamento::STATUS_CONFIRMADO,
        ]);

        $this->artisan('agendamentos:lembretes')
            ->assertExitCode(0);

        Mail::assertNotQueued(AgendamentoLembrete::class);
    });
});
