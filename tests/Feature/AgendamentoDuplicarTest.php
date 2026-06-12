<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Dup', 'slug' => 'barbearia-dup',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'phone' => '11999990001']);

    $this->original = Agendamento::create([
        'company_id' => $this->company->id,
        'cliente_id' => $this->cliente->id,
        'profissional_id' => $this->prof->id,
        'servico_id' => $this->servico->id,
        'data_hora' => now()->subDays(7)->setHour(10)->setMinute(0)->setSecond(0),
        'duracao' => 45,
        'valor' => 60.00,
        'status' => 'finalizado',
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
});

describe('agendamento_duplicar', function () {
    it('cria novo agendamento com mesmos dados na nova data', function () {
        $novaData = now()->addDays(7)->setHour(10)->setMinute(0)->setSecond(0)->format('Y-m-d H:i');

        $this->actingAs($this->user)
            ->post(route('agendamentos.duplicar', $this->original), ['data_hora' => $novaData])
            ->assertRedirect();

        $novo = Agendamento::where('company_id', $this->company->id)
            ->where('status', 'pendente')
            ->latest()
            ->first();

        expect($novo)->not->toBeNull()
            ->and($novo->cliente_id)->toBe($this->original->cliente_id)
            ->and($novo->profissional_id)->toBe($this->original->profissional_id)
            ->and($novo->servico_id)->toBe($this->original->servico_id)
            ->and($novo->duracao)->toBe($this->original->duracao);
    });

    it('agendamento duplicado começa como pendente', function () {
        $novaData = now()->addDays(7)->setHour(11)->setMinute(0)->setSecond(0)->format('Y-m-d H:i');

        $this->actingAs($this->user)
            ->post(route('agendamentos.duplicar', $this->original), ['data_hora' => $novaData]);

        $novo = Agendamento::where('company_id', $this->company->id)
            ->where('status', 'pendente')
            ->latest()
            ->first();

        expect($novo->status)->toBe('pendente');
    });

    it('data_hora no passado é rejeitada', function () {
        $this->actingAs($this->user)
            ->post(route('agendamentos.duplicar', $this->original), [
                'data_hora' => now()->subHour()->format('Y-m-d H:i'),
            ])
            ->assertSessionHasErrors('data_hora');
    });

    it('detecta conflito de horário ao duplicar', function () {
        $novaData = now()->addDays(3)->setHour(14)->setMinute(0)->setSecond(0);

        // Ocupa o slot
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
            'data_hora' => $novaData, 'duracao' => 30,
            'status' => 'confirmado', 'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $this->actingAs($this->user)
            ->post(route('agendamentos.duplicar', $this->original), [
                'data_hora' => $novaData->format('Y-m-d H:i'),
            ])
            ->assertSessionHasErrors('data_hora');
    });

    it('não pode duplicar agendamento de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-dup', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'S', 'duracao_minutos' => 30, 'preco' => 10, 'cor' => '#000', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'phone' => '11111111111']);

        $agOutra = Agendamento::create([
            'company_id' => $outra->id, 'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id, 'servico_id' => $servOutra->id,
            'data_hora' => now()->subDays(3), 'duracao' => 30,
            'status' => 'finalizado', 'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $this->actingAs($this->user)
            ->post(route('agendamentos.duplicar', $agOutra), [
                'data_hora' => now()->addDays(7)->format('Y-m-d H:i'),
            ])
            ->assertForbidden();
    });

    it('unauthenticated é redirecionado', function () {
        $this->post(route('agendamentos.duplicar', $this->original), [
            'data_hora' => now()->addDays(7)->format('Y-m-d H:i'),
        ])->assertRedirect(route('login'));
    });
});
