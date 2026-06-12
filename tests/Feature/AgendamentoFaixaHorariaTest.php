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
        'name' => 'Barbearia FH', 'slug' => 'barbearia-fh',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof FH', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte FH', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente FH', 'lgpd_consent' => true]);
});

function makeAgFH(string $companyId, string $profId, string $clienteId, string $servicoId, int $hora): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => now()->subDays(1)->setTime($hora, 0),
        'duracao' => 30,
        'valor' => 50,
        'status' => Agendamento::STATUS_FINALIZADO,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('agendamento_faixa_horaria', function () {
    it('retorna estrutura correta sem agendamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.faixa-horaria'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo_dias', 'total', 'melhor_faixa', 'faixas']);
        expect($data['total'])->toBe(0);
        expect($data['melhor_faixa'])->toBeNull();
        expect($data['faixas'])->toHaveCount(4);
    });

    it('classifica agendamento da manhã corretamente', function () {
        makeAgFH($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 9);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.faixa-horaria'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['melhor_faixa'])->toBe('manha');

        $por_faixa = collect($data['faixas'])->keyBy('faixa');
        expect($por_faixa['manha']['total_agendamentos'])->toBe(1);
        expect((float) $por_faixa['manha']['percentual'])->toBe(100.0);
        expect($por_faixa['tarde']['total_agendamentos'])->toBe(0);
    });

    it('classifica todas as faixas corretamente', function () {
        makeAgFH($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 2);  // madrugada
        makeAgFH($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 8);  // manha
        makeAgFH($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 15); // tarde
        makeAgFH($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 20); // noite

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.faixa-horaria'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(4);

        $por_faixa = collect($data['faixas'])->keyBy('faixa');
        expect($por_faixa['madrugada']['total_agendamentos'])->toBe(1);
        expect($por_faixa['manha']['total_agendamentos'])->toBe(1);
        expect($por_faixa['tarde']['total_agendamentos'])->toBe(1);
        expect($por_faixa['noite']['total_agendamentos'])->toBe(1);
        expect((float) $por_faixa['manha']['percentual'])->toBe(25.0);
    });

    it('ignora agendamentos não finalizados', function () {
        $ag = makeAgFH($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 10);
        $ag->status = Agendamento::STATUS_CANCELADO;
        $ag->save();

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.faixa-horaria'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('ignora agendamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra FH', 'slug' => 'outra-fh', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'Prof', 'ativo' => true]);
        $srvOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'Srv', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Cli', 'lgpd_consent' => true]);
        makeAgFH($outra->id, $profOutra->id, $cliOutra->id, $srvOutra->id, 10);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.faixa-horaria'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('agendamentos.faixa-horaria'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('agendamentos.faixa-horaria'))
            ->assertUnauthorized();
    });
});
