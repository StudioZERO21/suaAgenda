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
        'name' => 'Barbearia SemAg', 'slug' => 'barbearia-semag',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
});

describe('clientes_sem_agendamentos', function () {
    it('retorna clientes sem nenhum agendamento', function () {
        Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana', 'lgpd_consent' => true]);
        Cliente::create(['company_id' => $this->company->id, 'name' => 'Bruno', 'lgpd_consent' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.sem-agendamentos'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['total', 'apenas_ativos', 'items']);
        expect($data['total'])->toBe(2);
    });

    it('exclui clientes que têm agendamentos', function () {
        $clienteCom = Cliente::create(['company_id' => $this->company->id, 'name' => 'Com Ag', 'lgpd_consent' => true]);
        $clienteSem = Cliente::create(['company_id' => $this->company->id, 'name' => 'Sem Ag', 'lgpd_consent' => true]);

        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $clienteCom->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now(),
            'duracao' => 30,
            'valor' => 50,
            'status' => Agendamento::STATUS_FINALIZADO,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.sem-agendamentos'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['items'][0]['name'])->toBe('Sem Ag');
    });

    it('apenas_ativos=false inclui clientes inativos', function () {
        Cliente::create(['company_id' => $this->company->id, 'name' => 'Inativo', 'lgpd_consent' => true, 'ativo' => false]);
        Cliente::create(['company_id' => $this->company->id, 'name' => 'Ativo', 'lgpd_consent' => true, 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.sem-agendamentos', ['apenas_ativos' => 'false']))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(2);
        expect($data['apenas_ativos'])->toBeFalse();
    });

    it('ignora clientes de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-semag', 'plano' => 'trial', 'ativo' => true]);
        Cliente::create(['company_id' => $outra->id, 'name' => 'Fora', 'lgpd_consent' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.sem-agendamentos'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('clientes.sem-agendamentos'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('clientes.sem-agendamentos'))
            ->assertUnauthorized();
    });
});
