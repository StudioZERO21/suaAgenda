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
        'name' => 'Barbearia Pico', 'slug' => 'barbearia-pico',
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

function makeHorarioPicoAg(string $companyId, string $clienteId, string $profId, string $servicoId, string $dataHora): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId, 'cliente_id' => $clienteId,
        'profissional_id' => $profId, 'servico_id' => $servicoId,
        'data_hora' => $dataHora, 'duracao' => 30, 'valor' => 50.0, 'status' => 'finalizado',
    ]);
}

describe('relatorio_horarios_pico', function () {
    it('retorna estrutura correta com lista vazia', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.horarios-pico'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo', 'total_agendamentos', 'horarios']);
        expect($data['total_agendamentos'])->toBe(0);
        expect($data['horarios'])->toBeEmpty();
    });

    it('agrupa agendamentos por hora corretamente', function () {
        $base = now()->subDays(1)->toDateString();
        makeHorarioPicoAg($this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id, $base.' 10:00:00');
        makeHorarioPicoAg($this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id, $base.' 10:30:00');
        makeHorarioPicoAg($this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id, $base.' 14:00:00');

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.horarios-pico'))
            ->assertOk()
            ->json();

        expect($data['total_agendamentos'])->toBe(3);
        $hora10 = collect($data['horarios'])->firstWhere('hora', 10);
        expect($hora10)->not->toBeNull();
        expect($hora10['total'])->toBe(2);
        expect($hora10['pct'])->toBe(100);
    });

    it('ignora agendamentos cancelados', function () {
        $base = now()->subDays(1)->toDateString();
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
            'data_hora' => $base.' 09:00:00', 'duracao' => 30, 'valor' => 50.0, 'status' => 'cancelado',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.horarios-pico'))
            ->assertOk()
            ->json();

        expect($data['total_agendamentos'])->toBe(0);
    });

    it('não inclui dados de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-pico', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'X', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'ativo' => true]);

        makeHorarioPicoAg($outra->id, $cliOutra->id, $profOutra->id, $servOutra->id, now()->subDays(1)->toDateString().' 11:00:00');

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.horarios-pico'))
            ->assertOk()
            ->json();

        expect($data['total_agendamentos'])->toBe(0);
    });

    it('horários estão ordenados', function () {
        $base = now()->subDays(1)->toDateString();
        makeHorarioPicoAg($this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id, $base.' 14:00:00');
        makeHorarioPicoAg($this->company->id, $this->cliente->id, $this->prof->id, $this->servico->id, $base.' 09:00:00');

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.horarios-pico'))
            ->assertOk()
            ->json();

        $horas = array_column($data['horarios'], 'hora');
        expect($horas)->toBe(array_values($horas));
        expect($horas[0])->toBeLessThan($horas[1]);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('relatorios.horarios-pico'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('relatorios.horarios-pico'))
            ->assertUnauthorized();
    });
});
