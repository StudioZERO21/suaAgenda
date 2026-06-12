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
        'name' => 'Barbearia PorHora', 'slug' => 'barbearia-porhora',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof PH', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte PH', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente PH', 'lgpd_consent' => true]);
});

function makeAgPorHora(string $companyId, string $profId, string $clienteId, string $servicoId, Carbon $dataHora, string $status = Agendamento::STATUS_FINALIZADO): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => $dataHora,
        'duracao' => 30,
        'valor' => 50,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('agendamento_por_hora', function () {
    it('retorna 24 horas com zeros sem agendamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.por-hora'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo_dias', 'total', 'horas', 'hora_pico']);
        expect($data['horas'])->toHaveCount(24);
        expect($data['total'])->toBe(0);
        expect($data['hora_pico'])->toBeNull();
        expect($data['horas'][0])->toHaveKeys(['hora', 'hora_fmt', 'total', 'finalizados', 'cancelados']);
    });

    it('agrupa corretamente por hora do dia', function () {
        $base = now()->subDays(2);
        makeAgPorHora($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, $base->copy()->setHour(10)->setMinute(0));
        makeAgPorHora($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, $base->copy()->setHour(10)->setMinute(30));
        makeAgPorHora($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, $base->copy()->setHour(14)->setMinute(0));

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.por-hora'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(3);

        $hora10 = collect($data['horas'])->firstWhere('hora', 10);
        expect($hora10['total'])->toBe(2);
        expect($hora10['finalizados'])->toBe(2);

        $hora14 = collect($data['horas'])->firstWhere('hora', 14);
        expect($hora14['total'])->toBe(1);
    });

    it('identifica hora pico corretamente', function () {
        $base = now()->subDays(1);
        makeAgPorHora($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, $base->copy()->setHour(9));
        makeAgPorHora($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, $base->copy()->setHour(11));
        makeAgPorHora($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, $base->copy()->setHour(11));
        makeAgPorHora($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, $base->copy()->setHour(11));

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.por-hora'))
            ->assertOk()
            ->json();

        expect($data['hora_pico']['hora'])->toBe(11);
        expect($data['hora_pico']['hora_fmt'])->toBe('11:00');
        expect($data['hora_pico']['total'])->toBe(3);
    });

    it('respeita parâmetro dias', function () {
        $recente = now()->subDays(5)->setHour(10);
        $antigo = now()->subDays(60)->setHour(10);

        makeAgPorHora($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, $recente);
        makeAgPorHora($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, $antigo);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.por-hora', ['dias' => 30]))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['periodo_dias'])->toBe(30);
    });

    it('ignora agendamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra PH', 'slug' => 'outra-ph', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'Prof Outra', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'Srv Outra', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Cli Outra', 'lgpd_consent' => true]);

        makeAgPorHora($outra->id, $profOutra->id, $cliOutra->id, $servOutra->id, now()->subDay()->setHour(10));

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.por-hora'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('agendamentos.por-hora'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('agendamentos.por-hora'))
            ->assertUnauthorized();
    });
});
