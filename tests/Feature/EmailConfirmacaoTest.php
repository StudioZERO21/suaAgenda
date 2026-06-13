<?php

declare(strict_types=1);

use App\Mail\AgendamentoConfirmado;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\HorarioTrabalho;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'name' => 'Barbearia Email', 'slug' => 'barbearia-email',
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

    $this->prof->servicos()->attach($this->servico->id);
    HorarioTrabalho::create([
        'empresa_id' => $this->company->id,
        'profissional_id' => $this->prof->id,
        'dia_semana' => (int) now()->addDay()->format('w'),
        'hora_inicio' => '08:00', 'hora_fim' => '20:00', 'ativo' => true,
    ]);
});

describe('email_confirmacao', function () {
    it('envia email ao criar agendamento interno com cliente com email', function () {
        Mail::fake();

        $cliente = Cliente::create([
            'company_id' => $this->company->id,
            'name' => 'Ana Costa',
            'phone' => '11999990001',
            'email' => 'ana@example.com',
        ]);

        $this->actingAs($this->user)->post(route('agendamentos.store'), [
            'cliente_id' => $cliente->id,
            'profissional_id' => $this->prof->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i'),
            'duracao' => 30,
        ])->assertRedirect();

        Mail::assertQueued(AgendamentoConfirmado::class, fn ($mail) => $mail->hasTo('ana@example.com'));
    });

    it('não envia email quando cliente não tem email', function () {
        Mail::fake();

        $cliente = Cliente::create([
            'company_id' => $this->company->id,
            'name' => 'Bruno Sem Email',
            'phone' => '11999990002',
        ]);

        $this->actingAs($this->user)->post(route('agendamentos.store'), [
            'cliente_id' => $cliente->id,
            'profissional_id' => $this->prof->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->addDay()->setTime(11, 0)->format('Y-m-d H:i'),
            'duracao' => 30,
        ])->assertRedirect();

        Mail::assertNotQueued(AgendamentoConfirmado::class);
    });

    it('envia email ao criar agendamento público com email', function () {
        Mail::fake();

        $this->post(route('agendar.store', $this->company->slug), [
            'cliente_nome' => 'Carla Publica',
            'cliente_phone' => '11999990003',
            'cliente_email' => 'carla@example.com',
            'profissional_id' => $this->prof->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->addDay()->setTime(14, 0)->format('Y-m-d H:i'),
            'consent' => 1,
        ])->assertRedirect();

        Mail::assertQueued(AgendamentoConfirmado::class, fn ($mail) => $mail->hasTo('carla@example.com'));
    });

    it('envia email ao confirmar agendamento pendente via updateStatus', function () {
        Mail::fake();

        $cliente = Cliente::create([
            'company_id' => $this->company->id,
            'name' => 'Pedro Pendente',
            'phone' => '11977770001',
            'email' => 'pedro@pending.com',
        ]);

        $ag = Agendamento::create([
            'company_id' => $this->company->id,
            'cliente_id' => $cliente->id,
            'profissional_id' => $this->prof->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->addDay()->setTime(14, 0),
            'duracao' => 30,
            'status' => 'pendente',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $this->actingAs($this->user)
            ->patchJson(route('agendamentos.updateStatus', $ag), ['status' => 'confirmado'])
            ->assertOk();

        Mail::assertQueued(AgendamentoConfirmado::class, fn ($m) => $m->hasTo('pedro@pending.com'));
    });

    it('não envia email ao confirmar agendamento já confirmado', function () {
        Mail::fake();

        $cliente = Cliente::create([
            'company_id' => $this->company->id,
            'name' => 'Já Confirmado',
            'phone' => '11977770002',
            'email' => 'ja@confirmado.com',
        ]);

        $ag = Agendamento::create([
            'company_id' => $this->company->id,
            'cliente_id' => $cliente->id,
            'profissional_id' => $this->prof->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->addDay()->setTime(15, 0),
            'duracao' => 30,
            'status' => 'confirmado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $this->actingAs($this->user)
            ->patchJson(route('agendamentos.updateStatus', $ag), ['status' => 'confirmado'])
            ->assertOk();

        Mail::assertNothingQueued();
    });

    it('email contém link de cancel_token', function () {
        $ag = Agendamento::create([
            'company_id' => $this->company->id,
            'cliente_id' => Cliente::create(['company_id' => $this->company->id, 'name' => 'X', 'phone' => '11000000001'])->id,
            'profissional_id' => $this->prof->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->addDay()->setTime(10, 0),
            'duracao' => 30, 'status' => 'pendente',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $ag->load(['cliente', 'profissional', 'servico', 'company']);

        $mailable = new AgendamentoConfirmado($ag);
        $rendered = $mailable->render();

        expect($rendered)->toContain($ag->cancel_token);
    });
});
