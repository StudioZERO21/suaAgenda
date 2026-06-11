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
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Receita', 'slug' => 'barbearia-receita',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->profissional = Profissional::create([
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

function makeReceitaAg(string $companyId, string $clienteId, string $profId, string $servicoId, string $dataHora, float $valor, string $status = 'finalizado'): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'cliente_id' => $clienteId,
        'profissional_id' => $profId,
        'servico_id' => $servicoId,
        'data_hora' => $dataHora,
        'duracao' => 30,
        'valor' => $valor,
        'status' => $status,
    ]);
}

describe('dashboard_receita', function () {
    it('retorna estrutura correta com zeros quando sem agendamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('dashboard.receita'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['hoje', 'semana', 'mes']);
        expect($data['hoje'])->toHaveKeys(['receita', 'agendamentos', 'variacao_vs_ontem']);
        expect($data['semana'])->toHaveKeys(['receita', 'agendamentos', 'variacao_vs_semana_anterior']);
        expect($data['mes'])->toHaveKeys(['receita', 'agendamentos', 'variacao_vs_mes_anterior']);

        expect((float) $data['hoje']['receita'])->toBe(0.0);
        expect($data['hoje']['agendamentos'])->toBe(0);
    });

    it('soma receita de agendamentos finalizados hoje', function () {
        makeReceitaAg($this->company->id, $this->cliente->id, $this->profissional->id, $this->servico->id, now()->toDateString().' 10:00:00', 80.0);
        makeReceitaAg($this->company->id, $this->cliente->id, $this->profissional->id, $this->servico->id, now()->toDateString().' 11:00:00', 60.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('dashboard.receita'))
            ->assertOk()
            ->json();

        expect((float) $data['hoje']['receita'])->toBe(140.0);
        expect($data['hoje']['agendamentos'])->toBe(2);
    });

    it('ignora agendamentos não finalizados na receita', function () {
        makeReceitaAg($this->company->id, $this->cliente->id, $this->profissional->id, $this->servico->id, now()->toDateString().' 10:00:00', 100.0, 'confirmado');
        makeReceitaAg($this->company->id, $this->cliente->id, $this->profissional->id, $this->servico->id, now()->toDateString().' 11:00:00', 50.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('dashboard.receita'))
            ->assertOk()
            ->json();

        expect((float) $data['hoje']['receita'])->toBe(50.0);
        expect($data['hoje']['agendamentos'])->toBe(1);
    });

    it('soma receita da semana corretamente', function () {
        $semanaInicio = now()->startOfWeek()->toDateString();
        makeReceitaAg($this->company->id, $this->cliente->id, $this->profissional->id, $this->servico->id, $semanaInicio.' 09:00:00', 120.0);
        makeReceitaAg($this->company->id, $this->cliente->id, $this->profissional->id, $this->servico->id, now()->toDateString().' 10:00:00', 80.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('dashboard.receita'))
            ->assertOk()
            ->json();

        expect((float) $data['semana']['receita'])->toBeGreaterThan(199.99);
        expect($data['semana']['agendamentos'])->toBeGreaterThan(1);
    });

    it('não conta agendamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-rec', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'X', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);
        $clienteOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'ativo' => true]);
        makeReceitaAg($outra->id, $clienteOutra->id, $profOutra->id, $servOutra->id, now()->toDateString().' 10:00:00', 500.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('dashboard.receita'))
            ->assertOk()
            ->json();

        expect((float) $data['hoje']['receita'])->toBe(0.0);
    });

    it('analista pode acessar receita', function () {
        $this->actingAs($this->analista)
            ->getJson(route('dashboard.receita'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('dashboard.receita'))
            ->assertUnauthorized();
    });

    it('variacao_vs_ontem é null quando ambos zero', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('dashboard.receita'))
            ->assertOk()
            ->json();

        expect($data['hoje']['variacao_vs_ontem'])->toBeNull();
    });
});
