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
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Stats', 'slug' => 'barbearia-stats',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('configuracao_empresa_stats', function () {
    it('retorna estrutura correta com zeros iniciais', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('configuracoes.empresa.stats'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys([
            'clientes_total', 'clientes_ativos',
            'profissionais_total', 'profissionais_ativos',
            'servicos_total', 'servicos_ativos',
            'agendamentos_mes', 'agendamentos_mes_finalizados', 'receita_mes',
        ]);

        expect($data['clientes_total'])->toBe(0);
        expect($data['profissionais_total'])->toBe(0);
        expect($data['servicos_total'])->toBe(0);
        expect((float) $data['receita_mes'])->toBe(0.0);
    });

    it('conta clientes ativos e inativos corretamente', function () {
        Cliente::create(['company_id' => $this->company->id, 'name' => 'A', 'ativo' => true]);
        Cliente::create(['company_id' => $this->company->id, 'name' => 'B', 'ativo' => true]);
        Cliente::create(['company_id' => $this->company->id, 'name' => 'C', 'ativo' => false]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('configuracoes.empresa.stats'))
            ->assertOk()
            ->json();

        expect($data['clientes_total'])->toBe(3);
        expect($data['clientes_ativos'])->toBe(2);
    });

    it('conta profissionais e serviços corretamente', function () {
        Profissional::create(['company_id' => $this->company->id, 'name' => 'P1', 'ativo' => true]);
        Profissional::create(['company_id' => $this->company->id, 'name' => 'P2', 'ativo' => false]);
        Servico::create(['company_id' => $this->company->id, 'nome' => 'S1', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('configuracoes.empresa.stats'))
            ->assertOk()
            ->json();

        expect($data['profissionais_total'])->toBe(2);
        expect($data['profissionais_ativos'])->toBe(1);
        expect($data['servicos_total'])->toBe(1);
        expect($data['servicos_ativos'])->toBe(1);
    });

    it('conta agendamentos do mês e receita', function () {
        $cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'ativo' => true]);
        $prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'P', 'ativo' => true]);
        $serv = Servico::create(['company_id' => $this->company->id, 'nome' => 'S', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);

        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $cliente->id,
            'profissional_id' => $prof->id, 'servico_id' => $serv->id,
            'data_hora' => now()->startOfMonth()->addDays(2)->setHour(10),
            'duracao' => 30, 'valor' => 80.0, 'status' => 'finalizado',
        ]);

        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $cliente->id,
            'profissional_id' => $prof->id, 'servico_id' => $serv->id,
            'data_hora' => now()->startOfMonth()->addDays(3)->setHour(11),
            'duracao' => 30, 'valor' => 60.0, 'status' => 'confirmado',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('configuracoes.empresa.stats'))
            ->assertOk()
            ->json();

        expect($data['agendamentos_mes'])->toBe(2);
        expect($data['agendamentos_mes_finalizados'])->toBe(1);
        expect((float) $data['receita_mes'])->toBe(80.0);
    });

    it('não inclui dados de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-stats', 'plano' => 'trial', 'ativo' => true]);
        Cliente::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('configuracoes.empresa.stats'))
            ->assertOk()
            ->json();

        expect($data['clientes_total'])->toBe(0);
    });

    it('gestor pode acessar', function () {
        $this->actingAs($this->gestor)
            ->getJson(route('configuracoes.empresa.stats'))
            ->assertOk();
    });

    it('analista não pode acessar (sem permissão de configurações)', function () {
        $this->actingAs($this->analista)
            ->getJson(route('configuracoes.empresa.stats'))
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('configuracoes.empresa.stats'))
            ->assertUnauthorized();
    });
});
