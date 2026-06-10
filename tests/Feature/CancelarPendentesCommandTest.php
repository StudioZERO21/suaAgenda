<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Notificacao;
use App\Models\Profissional;
use App\Models\Servico;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'name' => 'Barbearia Cancel', 'slug' => 'barbearia-cancel',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'phone' => '11999990001']);
});

function makePendenteAg($self, string $status = 'pendente', int $horasAtras = 5): Agendamento
{
    return Agendamento::create([
        'company_id' => $self->company->id,
        'cliente_id' => $self->cliente->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $self->servico->id,
        'data_hora' => now()->subHours($horasAtras),
        'duracao' => 30,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('cancelar_pendentes_command', function () {
    it('cancela agendamento pendente vencido (padrão 2h)', function () {
        $ag = makePendenteAg($this, 'pendente', 5);

        $this->artisan('agendamentos:cancelar-pendentes')->assertSuccessful();

        expect($ag->fresh()->status)->toBe('cancelado');
    });

    it('não cancela agendamento futuro pendente', function () {
        $ag = Agendamento::create([
            'company_id' => $this->company->id,
            'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->prof->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->addHours(3),
            'duracao' => 30,
            'status' => 'pendente',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $this->artisan('agendamentos:cancelar-pendentes')->assertSuccessful();

        expect($ag->fresh()->status)->toBe('pendente');
    });

    it('não cancela agendamento confirmado vencido', function () {
        $ag = makePendenteAg($this, 'confirmado', 5);

        $this->artisan('agendamentos:cancelar-pendentes')->assertSuccessful();

        expect($ag->fresh()->status)->toBe('confirmado');
    });

    it('cria notificação por empresa ao cancelar', function () {
        makePendenteAg($this, 'pendente', 5);
        makePendenteAg($this, 'pendente', 5);

        $this->artisan('agendamentos:cancelar-pendentes')->assertSuccessful();

        $notif = Notificacao::where('company_id', $this->company->id)
            ->where('tipo', 'cancelamento_automatico')
            ->first();

        expect($notif)->not->toBeNull()
            ->and($notif->mensagem)->toContain('2');
    });

    it('respeita --grace personalizado', function () {
        $ag1 = makePendenteAg($this, 'pendente', 6); // 6h atrás, além de 5h de grace
        $ag2 = makePendenteAg($this, 'pendente', 3); // 3h atrás, dentro de 5h de grace

        $this->artisan('agendamentos:cancelar-pendentes', ['--grace' => 5])->assertSuccessful();

        expect($ag1->fresh()->status)->toBe('cancelado');
        expect($ag2->fresh()->status)->toBe('pendente');
    });

    it('isolamento: cancela apenas agendamentos da empresa correta', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-cancel', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'S', 'duracao_minutos' => 30, 'preco' => 10, 'cor' => '#000', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'phone' => '11111111111']);

        $agOutra = Agendamento::create([
            'company_id' => $outra->id, 'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id, 'servico_id' => $servOutra->id,
            'data_hora' => now()->subHours(5), 'duracao' => 30,
            'status' => 'pendente', 'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $agMinha = makePendenteAg($this, 'pendente', 5);

        $this->artisan('agendamentos:cancelar-pendentes')->assertSuccessful();

        expect($agMinha->fresh()->status)->toBe('cancelado');
        expect($agOutra->fresh()->status)->toBe('cancelado');
        expect(Notificacao::where('tipo', 'cancelamento_automatico')->count())->toBe(2);
    });
});
