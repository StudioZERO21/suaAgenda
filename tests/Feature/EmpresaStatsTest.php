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
        'name' => 'Barbearia Stats', 'slug' => 'barbearia-stats',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('empresa_stats', function () {
    it('retorna estrutura correta', function () {
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
    });

    it('conta clientes e profissionais corretamente', function () {
        Cliente::create(['company_id' => $this->company->id, 'name' => 'A', 'phone' => '11111111111', 'ativo' => true]);
        Cliente::create(['company_id' => $this->company->id, 'name' => 'B', 'phone' => '11111111112', 'ativo' => false]);
        Profissional::create(['company_id' => $this->company->id, 'name' => 'P1', 'ativo' => true]);
        Profissional::create(['company_id' => $this->company->id, 'name' => 'P2', 'ativo' => false]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('configuracoes.empresa.stats'))
            ->json();

        expect($data['clientes_total'])->toBe(2);
        expect($data['clientes_ativos'])->toBe(1);
        expect($data['profissionais_total'])->toBe(2);
        expect($data['profissionais_ativos'])->toBe(1);
    });

    it('conta agendamentos do mês', function () {
        $prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'P', 'ativo' => true]);
        $servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'S', 'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true]);
        $cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'C', 'phone' => '11999990001']);

        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $prof->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'data_hora' => now()->startOfMonth()->addDays(2),
            'duracao' => 30,
            'valor' => 50.0,
            'status' => 'finalizado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $prof->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'data_hora' => now()->startOfMonth()->addDays(3),
            'duracao' => 30,
            'status' => 'pendente',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('configuracoes.empresa.stats'))
            ->json();

        expect($data['agendamentos_mes'])->toBe(2);
        expect($data['agendamentos_mes_finalizados'])->toBe(1);
        expect((float) $data['receita_mes'])->toBe(50.0);
    });

    it('retorna zeros quando empresa vazia', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('configuracoes.empresa.stats'))
            ->json();

        expect($data['clientes_total'])->toBe(0);
        expect($data['agendamentos_mes'])->toBe(0);
        expect((float) $data['receita_mes'])->toBe(0.0);
    });

    it('analista pode ver stats', function () {
        $this->actingAs($this->analista)
            ->getJson(route('configuracoes.empresa.stats'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('configuracoes.empresa.stats'))
            ->assertUnauthorized();
    });
});
