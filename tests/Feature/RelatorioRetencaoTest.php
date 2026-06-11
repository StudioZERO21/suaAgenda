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
        'name' => 'Barbearia Ret', 'slug' => 'barbearia-ret',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'P', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'S', 'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true]);
});

describe('relatorio_retencao', function () {
    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.retencao'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo', 'total_clientes_periodo', 'clientes_recorrentes', 'clientes_novos', 'taxa_retencao_pct']);
    });

    it('classifica cliente novo vs recorrente', function () {
        $novo = Cliente::create(['company_id' => $this->company->id, 'name' => 'Novo', 'phone' => '11000000001']);
        $recorrente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Recorrente', 'phone' => '11000000002']);

        // recorrente had a past agendamento
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $recorrente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->subDays(40),
            'duracao' => 30,
            'status' => 'finalizado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        // both have agendamento in period
        foreach ([$novo->id, $recorrente->id] as $cid) {
            Agendamento::create([
                'company_id' => $this->company->id,
                'profissional_id' => $this->prof->id,
                'cliente_id' => $cid,
                'servico_id' => $this->servico->id,
                'data_hora' => now()->subDays(5),
                'duracao' => 30,
                'status' => 'finalizado',
                'cancel_token' => Agendamento::generateCancelToken(),
            ]);
        }

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.retencao'))
            ->json();

        expect($data['total_clientes_periodo'])->toBe(2);
        expect($data['clientes_recorrentes'])->toBe(1);
        expect($data['clientes_novos'])->toBe(1);
        expect((float) $data['taxa_retencao_pct'])->toBe(50.0);
    });

    it('retorna zeros quando sem agendamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.retencao'))
            ->json();

        expect($data['total_clientes_periodo'])->toBe(0);
        expect((float) $data['taxa_retencao_pct'])->toBe(0.0);
    });

    it('analista pode ver retenção', function () {
        $this->actingAs($this->analista)
            ->getJson(route('relatorios.retencao'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('relatorios.retencao'))
            ->assertUnauthorized();
    });
});
