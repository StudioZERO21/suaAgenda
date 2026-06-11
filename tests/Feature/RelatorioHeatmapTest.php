<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Heat', 'slug' => 'barbearia-heat',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'P', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'S', 'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'C', 'phone' => '11999990001']);
});

describe('relatorio_heatmap', function () {
    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.heatmap'))
            ->assertOk()
            ->json();

        expect($data)->toBeArray();
        expect($data[0])->toHaveKeys(['dia_semana', 'dia_nome', 'hora', 'total']);
    });

    it('retorna 7x15 slots (dias x horas 7-21)', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.heatmap'))
            ->json();

        expect(count($data))->toBe(7 * 15);
    });

    it('incrementa total para agendamento no horário correto', function () {
        $segunda = now()->subDays(7)->startOfWeek(Carbon::MONDAY)->setTime(10, 0);

        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => $segunda,
            'duracao' => 30,
            'status' => 'finalizado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.heatmap', ['preset' => '30d']))
            ->json();

        $slot = collect($data)->first(fn ($s) => $s['dia_semana'] === 1 && $s['hora'] === 10);
        expect($slot['total'])->toBeGreaterThanOrEqual(1);
    });

    it('não conta agendamentos cancelados', function () {
        $segunda = now()->subDays(7)->startOfWeek(Carbon::MONDAY)->setTime(10, 0);

        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => $segunda,
            'duracao' => 30,
            'status' => 'cancelado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.heatmap', ['preset' => '30d']))
            ->json();

        $slot = collect($data)->first(fn ($s) => $s['dia_semana'] === 1 && $s['hora'] === 10);
        expect($slot['total'])->toBe(0);
    });

    it('analista pode ver heatmap', function () {
        $this->actingAs($this->analista)
            ->getJson(route('relatorios.heatmap'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('relatorios.heatmap'))
            ->assertUnauthorized();
    });
});
