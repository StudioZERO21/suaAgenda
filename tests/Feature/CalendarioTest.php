<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\Servico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista',      'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Teste',
        'slug' => 'empresa-teste',
        'plano' => 'trial',
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('calendario', function () {
    it('qualquer role pode acessar o calendário', function () {
        $this->actingAs($this->analista)
            ->get(route('calendario'))
            ->assertOk();
    });

    it('exibe agendamentos da semana atual', function () {
        $profissional = Profissional::create([
            'company_id' => $this->company->id,
            'name' => 'Carlos',
            'ativo' => true,
        ]);
        $servico = Servico::create([
            'company_id' => $this->company->id,
            'nome' => 'Corte',
            'duracao_minutos' => 30,
            'preco' => 40,
            'cor' => '#1a1a1a',
            'ativo' => true,
        ]);

        $cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João']);

        $hoje = now()->startOfWeek(Carbon::MONDAY)->addDays(1)->setTime(10, 0);
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $profissional->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'data_hora' => $hoje,
            'duracao' => 30,
            'valor' => 40,
            'status' => 'confirmado',
        ]);

        $this->actingAs($this->admin)
            ->get(route('calendario'))
            ->assertOk()
            ->assertSee('Corte');
    });

    it('filtra por profissional via query string', function () {
        $prof1 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Ana', 'ativo' => true]);
        $prof2 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Bruno', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->get(route('calendario', ['profissional_id' => $prof1->id]))
            ->assertOk();
    });

    it('navega para semana específica via parâmetro', function () {
        $this->actingAs($this->admin)
            ->get(route('calendario', ['semana' => '2026-06-02']))
            ->assertOk();
    });
});

describe('agendamento move (calendário)', function () {
    it('admin move agendamento para novo horário via drag', function () {
        $profissional = Profissional::create([
            'company_id' => $this->company->id,
            'name' => 'Carlos',
            'ativo' => true,
        ]);
        $servico = Servico::create([
            'company_id' => $this->company->id,
            'nome' => 'Corte',
            'duracao_minutos' => 30,
            'preco' => 40,
            'cor' => '#1a1a1a',
            'ativo' => true,
        ]);
        $cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João']);

        $inicio = now()->addDay()->setTime(10, 0);
        $agendamento = Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $profissional->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'data_hora' => $inicio,
            'duracao' => 30,
            'valor' => 40,
            'status' => 'confirmado',
        ]);

        $novaData = $inicio->copy()->setTime(14, 30);

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.move', $agendamento), [
                'data' => $novaData->format('Y-m-d'),
                'hora' => 14,
                'minuto' => 30,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Movido para 14:30')
            ->assertJsonPath('hora', 14)
            ->assertJsonPath('minuto', 30)
            ->assertJsonPath('hora_label', '14:30');

        expect($agendamento->fresh()->data_hora->format('Y-m-d H:i'))
            ->toBe($novaData->format('Y-m-d H:i'));
    });

    it('bloqueia movimento com conflito de horário', function () {
        $profissional = Profissional::create([
            'company_id' => $this->company->id,
            'name' => 'Ana',
            'ativo' => true,
        ]);
        $cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Maria']);
        $dia = now()->addDays(2)->setTime(9, 0);

        $existente = Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $profissional->id,
            'cliente_id' => $cliente->id,
            'data_hora' => $dia,
            'duracao' => 60,
            'status' => 'confirmado',
        ]);

        $mover = Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $profissional->id,
            'cliente_id' => $cliente->id,
            'data_hora' => $dia->copy()->setTime(11, 0),
            'duracao' => 30,
            'status' => 'pendente',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.move', $mover), [
                'data' => $dia->format('Y-m-d'),
                'hora' => 9,
                'minuto' => 30,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Horário já ocupado para este profissional.');

        expect($existente->fresh()->id)->toBe($existente->id);
    });

    it('analista não pode mover agendamento', function () {
        $profissional = Profissional::create([
            'company_id' => $this->company->id,
            'name' => 'Bruno',
            'ativo' => true,
        ]);
        $cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Pedro']);
        $agendamento = Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $profissional->id,
            'cliente_id' => $cliente->id,
            'data_hora' => now()->addDay()->setTime(15, 0),
            'duracao' => 30,
            'status' => 'confirmado',
        ]);

        $this->actingAs($this->analista)
            ->patchJson(route('agendamentos.move', $agendamento), [
                'data' => now()->addDay()->format('Y-m-d'),
                'hora' => 16,
                'minuto' => 0,
            ])
            ->assertForbidden();
    });
});
