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
    Role::firstOrCreate(['name' => 'analista',      'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Teste',
        'slug' => 'empresa-teste',
        'plano' => 'trial',
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('relatorios', function () {
    it('qualquer role pode acessar relatórios', function () {
        $this->actingAs($this->analista)
            ->get(route('relatorios'))
            ->assertOk();
    });

    it('exibe KPIs zerados quando não há dados', function () {
        $this->actingAs($this->admin)
            ->get(route('relatorios'))
            ->assertOk()
            ->assertSee('R$ 0,00');
    });

    it('filtra por preset 7d', function () {
        $this->actingAs($this->admin)
            ->get(route('relatorios', ['preset' => '7d']))
            ->assertOk();
    });

    it('filtra por preset custom com datas', function () {
        $this->actingAs($this->admin)
            ->get(route('relatorios', ['preset' => 'custom', 'de' => '2026-01-01', 'ate' => '2026-01-31']))
            ->assertOk();
    });

    it('soma receita de agendamentos finalizados no período', function () {
        $profissional = Profissional::create([
            'company_id' => $this->company->id,
            'name' => 'Carlos',
            'ativo' => true,
        ]);
        $servico = Servico::create([
            'company_id' => $this->company->id,
            'nome' => 'Barba',
            'duracao_minutos' => 30,
            'preco' => 50,
            'cor' => '#1a1a1a',
            'ativo' => true,
        ]);

        $cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Maria']);

        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $profissional->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'data_hora' => now()->subDays(5),
            'duracao' => 30,
            'valor' => 50.00,
            'status' => 'finalizado',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('relatorios', ['preset' => '30d']))
            ->assertOk();

        $response->assertSee('50,00');
    });

    it('exibe receita por serviço', function () {
        $profissional = Profissional::create([
            'company_id' => $this->company->id,
            'name' => 'Ana',
            'ativo' => true,
        ]);
        $servico = Servico::create([
            'company_id' => $this->company->id,
            'nome' => 'Coloração Especial',
            'duracao_minutos' => 60,
            'preco' => 120,
            'cor' => '#d4a574',
            'ativo' => true,
        ]);

        $cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana']);

        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $profissional->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'data_hora' => now()->subDays(2),
            'duracao' => 60,
            'valor' => 120.00,
            'status' => 'finalizado',
        ]);

        $this->actingAs($this->admin)
            ->get(route('relatorios', ['preset' => '30d']))
            ->assertOk()
            ->assertSee('Coloração Especial');
    });
});
