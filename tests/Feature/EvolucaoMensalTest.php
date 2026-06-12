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

    $this->company = Company::create([
        'name' => 'Barbearia Evo', 'slug' => 'barbearia-evo',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);
    $this->cliente = Cliente::create([
        'company_id' => $this->company->id, 'name' => 'João', 'phone' => '11999990001',
    ]);
});

describe('evolucao_mensal', function () {
    it('retorna 6 meses de dados na view de relatórios', function () {
        $data = $this->actingAs($this->user)->get(route('relatorios'))->viewData('evolucaoMensal');
        expect($data)->toHaveCount(6);
    });

    it('inclui agendamentos finalizados do mês atual na evolução', function () {
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
            'data_hora' => now()->startOfMonth()->addDays(2)->setTime(10, 0),
            'duracao' => 30, 'status' => 'finalizado', 'valor' => 80.00,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->user)->get(route('relatorios'))->viewData('evolucaoMensal');
        $mesAtual = collect($data)->last();
        expect($mesAtual['agendamentos'])->toBe(1);
        expect($mesAtual['receita'])->toBe(80.0);
    });

    it('não soma receita de agendamentos pendentes na evolução', function () {
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
            'data_hora' => now()->startOfMonth()->addDays(2)->setTime(10, 0),
            'duracao' => 30, 'status' => 'pendente', 'valor' => 80.00,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->user)->get(route('relatorios'))->viewData('evolucaoMensal');
        $mesAtual = collect($data)->last();
        expect($mesAtual['agendamentos'])->toBe(1); // conta na contagem
        expect($mesAtual['receita'])->toBe(0.0);    // mas não soma receita
    });

    it('exibe card de evolução mensal na página', function () {
        $this->actingAs($this->user)
            ->get(route('relatorios'))
            ->assertOk()
            ->assertSee('Evolução Mensal');
    });

    it('maxEvolucaoAg é pelo menos 1 quando há agendamentos', function () {
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
            'data_hora' => now()->startOfMonth()->addDays(1)->setTime(9, 0),
            'duracao' => 30, 'status' => 'confirmado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $max = $this->actingAs($this->user)->get(route('relatorios'))->viewData('maxEvolucaoAg');
        expect($max)->toBeGreaterThanOrEqual(1);
    });
});
