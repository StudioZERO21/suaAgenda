<?php

declare(strict_types=1);

use App\Mail\AgendamentoLembrete;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Lembretes Barbearia',
        'slug' => 'lembretes-barbearia',
        'plano' => 'starter',
        'ativo' => true,
    ]);

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id,
        'name' => 'Ana Lembrete',
        'email' => 'ana@lembrete.test',
        'ativo' => true,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id,
        'nome' => 'Corte',
        'duracao_minutos' => 30,
        'preco' => 50,
        'ativo' => true,
    ]);

    $user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->profissional = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Bob',
        'ativo' => true,
        'user_id' => $user->id,
    ]);
});

function makeLembreteAgendamento(Company $company, Cliente $cliente, Profissional $profissional, Servico $servico, string $dataHora, string $status = 'confirmado'): Agendamento
{
    return Agendamento::create([
        'company_id' => $company->id,
        'cliente_id' => $cliente->id,
        'profissional_id' => $profissional->id,
        'servico_id' => $servico->id,
        'data_hora' => $dataHora,
        'status' => $status,
        'valor' => 50,
        'duracao' => 30,
    ]);
}

describe('lembretes_command', function () {
    it('envia lembrete 24h para agendamento de amanhã (padrão)', function () {
        makeLembreteAgendamento(
            $this->company, $this->cliente, $this->profissional, $this->servico,
            now()->addDay()->setTime(10, 0)->toDateTimeString(),
        );

        $this->artisan('agendamentos:lembretes')->assertSuccessful();

        Mail::assertQueued(AgendamentoLembrete::class, 1);
    });

    it('não envia quando auto_reminder = false', function () {
        $settings = $this->company->settings ?? [];
        $settings['advanced']['auto_reminder'] = false;
        $this->company->update(['settings' => $settings]);

        makeLembreteAgendamento(
            $this->company, $this->cliente, $this->profissional, $this->servico,
            now()->addDay()->setTime(10, 0)->toDateTimeString(),
        );

        $this->artisan('agendamentos:lembretes')->assertSuccessful();

        Mail::assertNothingQueued();
    });

    it('respeita reminder_hours = 48 (dois dias)', function () {
        $settings = $this->company->settings ?? [];
        $settings['advanced']['reminder_hours'] = 48;
        $this->company->update(['settings' => $settings]);

        makeLembreteAgendamento(
            $this->company, $this->cliente, $this->profissional, $this->servico,
            now()->addDays(2)->setTime(10, 0)->toDateTimeString(),
        );

        $this->artisan('agendamentos:lembretes')->assertSuccessful();

        Mail::assertQueued(AgendamentoLembrete::class, 1);
    });

    it('não envia para agendamento cancelado', function () {
        makeLembreteAgendamento(
            $this->company, $this->cliente, $this->profissional, $this->servico,
            now()->addDay()->setTime(10, 0)->toDateTimeString(),
            'cancelado',
        );

        $this->artisan('agendamentos:lembretes')->assertSuccessful();

        Mail::assertNothingQueued();
    });

    it('não envia quando cliente não tem e-mail', function () {
        $semEmail = Cliente::create(['company_id' => $this->company->id, 'name' => 'Sem Email', 'ativo' => true]);

        makeLembreteAgendamento(
            $this->company, $semEmail, $this->profissional, $this->servico,
            now()->addDay()->setTime(10, 0)->toDateTimeString(),
        );

        $this->artisan('agendamentos:lembretes')->assertSuccessful();

        Mail::assertNothingQueued();
    });

    it('exibe contagem correta no output', function () {
        makeLembreteAgendamento(
            $this->company, $this->cliente, $this->profissional, $this->servico,
            now()->addDay()->setTime(10, 0)->toDateTimeString(),
        );

        $this->artisan('agendamentos:lembretes')
            ->expectsOutput('Lembretes enfileirados: 1')
            ->assertSuccessful();
    });
});
