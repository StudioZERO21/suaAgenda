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
        'name' => 'Barbearia AM', 'slug' => 'barbearia-am',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof AM', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte AM', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente AM', 'lgpd_consent' => true]);
});

function makeAgAM(string $companyId, string $profId, string $clienteId, string $servicoId, int $diasAntecedencia): Agendamento
{
    $ag = Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => now()->addDays($diasAntecedencia),
        'duracao' => 30,
        'valor' => 50,
        'status' => Agendamento::STATUS_CONFIRMADO,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
    $ag->created_at = now();
    $ag->save();

    return $ag;
}

describe('agendamento_antecedencia_media', function () {
    it('retorna estrutura correta sem agendamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.antecedencia-media'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo_dias', 'total', 'antecedencia_media_dias', 'antecedencia_min_dias', 'antecedencia_max_dias', 'mesmo_dia', 'menos_de_1_dia', 'ate_3_dias', 'mais_de_3_dias']);
        expect($data['total'])->toBe(0);
        expect($data['antecedencia_media_dias'])->toBeNull();
    });

    it('calcula antecedência para agendamento no mesmo dia', function () {
        makeAgAM($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.antecedencia-media'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['mesmo_dia'])->toBe(1);
        expect((float) $data['antecedencia_media_dias'])->toBe(0.0);
    });

    it('calcula antecedência para agendamento futuro', function () {
        makeAgAM($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 5);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.antecedencia-media'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['mais_de_3_dias'])->toBe(1);
    });

    it('ignora agendamentos cancelados', function () {
        $ag = makeAgAM($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 5);
        $ag->status = Agendamento::STATUS_CANCELADO;
        $ag->save();

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.antecedencia-media'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('ignora agendamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra AM', 'slug' => 'outra-am', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'Prof', 'ativo' => true]);
        $srvOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'Srv', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Cli', 'lgpd_consent' => true]);
        makeAgAM($outra->id, $profOutra->id, $cliOutra->id, $srvOutra->id, 5);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.antecedencia-media'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('agendamentos.antecedencia-media'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('agendamentos.antecedencia-media'))
            ->assertUnauthorized();
    });
});
