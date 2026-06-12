<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia DiaSem', 'slug' => 'barbearia-ds',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create([
        'company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte', 'preco' => 50.0,
        'duracao_minutos' => 30, 'ativo' => true,
    ]);

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id, 'name' => 'João', 'ativo' => true,
    ]);
});

describe('relatorio_por_dia_semana', function () {
    it('retorna 7 dias com estrutura correta quando sem dados', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.por-dia-semana'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo', 'dias']);
        expect($data['dias'])->toHaveCount(7);
        expect($data['dias'][0])->toHaveKeys(['dia', 'nome', 'total', 'receita']);
    });

    it('conta agendamentos por dia da semana corretamente', function () {
        $segunda = now()->startOfWeek()->addDay(0); // Monday = 1 in Carbon
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
            'data_hora' => $segunda->setHour(10)->setMinute(0)->setSecond(0)->toDateTimeString(),
            'duracao' => 30, 'valor' => 50.0, 'status' => 'finalizado',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.por-dia-semana'))
            ->assertOk()
            ->json();

        $totalGeral = collect($data['dias'])->sum('total');
        expect($totalGeral)->toBeGreaterThanOrEqual(1);
    });

    it('ignora agendamentos cancelados', function () {
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
            'data_hora' => now()->subDays(1)->setHour(10)->setMinute(0)->setSecond(0)->toDateTimeString(),
            'duracao' => 30, 'valor' => 50.0, 'status' => 'cancelado',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.por-dia-semana'))
            ->assertOk()
            ->json();

        $totalGeral = collect($data['dias'])->sum('total');
        expect($totalGeral)->toBe(0);
    });

    it('não inclui dados de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-ds', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'X', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'ativo' => true]);

        Agendamento::create([
            'company_id' => $outra->id, 'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id, 'servico_id' => $servOutra->id,
            'data_hora' => now()->subDays(1)->toDateTimeString(),
            'duracao' => 30, 'valor' => 500.0, 'status' => 'finalizado',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.por-dia-semana'))
            ->assertOk()
            ->json();

        $totalGeral = collect($data['dias'])->sum('total');
        expect($totalGeral)->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('relatorios.por-dia-semana'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('relatorios.por-dia-semana'))
            ->assertUnauthorized();
    });
});
