<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Lancamento;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia SumFin', 'slug' => 'barbearia-sumfin',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof SF', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte SF', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente SF', 'lgpd_consent' => true]);
});

function makeAgSumFin(string $companyId, string $profId, string $clienteId, string $servicoId, float $valor): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => now()->startOfMonth()->addDays(3),
        'duracao' => 30,
        'valor' => $valor,
        'status' => Agendamento::STATUS_FINALIZADO,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

function makeLancSumFin(string $companyId, string $tipo, float $valor, string $status = 'pago', int $diasAtras = 2): Lancamento
{
    return Lancamento::create([
        'company_id' => $companyId,
        'descricao' => "Lanc {$tipo}",
        'tipo' => $tipo,
        'valor' => $valor,
        'data' => today()->subDays($diasAtras)->format('Y-m-d'),
        'status' => $status,
    ]);
}

describe('dashboard_sumario_financeiro', function () {
    it('retorna estrutura correta com zeros', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('dashboard.sumario-financeiro'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['mes', 'ano', 'receita_agendamentos', 'receita_lancamentos', 'receita_total', 'despesa_total', 'saldo', 'a_receber', 'a_pagar', 'inadimplentes_count']);
        expect((float) $data['receita_total'])->toBe(0.0);
        expect((float) $data['saldo'])->toBe(0.0);
        expect($data['inadimplentes_count'])->toBe(0);
    });

    it('soma agendamentos finalizados do mês', function () {
        makeAgSumFin($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 200.0);
        makeAgSumFin($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 150.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('dashboard.sumario-financeiro'))
            ->assertOk()
            ->json();

        expect((float) $data['receita_agendamentos'])->toBe(350.0);
    });

    it('combina agendamentos e lançamentos pagos na receita total', function () {
        makeAgSumFin($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 300.0);
        makeLancSumFin($this->company->id, 'receita', 100.0, 'pago');
        makeLancSumFin($this->company->id, 'despesa', 80.0, 'pago');

        $data = $this->actingAs($this->admin)
            ->getJson(route('dashboard.sumario-financeiro'))
            ->assertOk()
            ->json();

        expect((float) $data['receita_total'])->toBe(400.0);
        expect((float) $data['despesa_total'])->toBe(80.0);
        expect((float) $data['saldo'])->toBe(320.0);
    });

    it('calcula a_receber e a_pagar dos pendentes', function () {
        makeLancSumFin($this->company->id, 'receita', 500.0, 'pendente');
        makeLancSumFin($this->company->id, 'despesa', 200.0, 'pendente');

        $data = $this->actingAs($this->admin)
            ->getJson(route('dashboard.sumario-financeiro'))
            ->assertOk()
            ->json();

        expect((float) $data['a_receber'])->toBe(500.0);
        expect((float) $data['a_pagar'])->toBe(200.0);
    });

    it('conta inadimplentes corretamente', function () {
        Lancamento::create(['company_id' => $this->company->id, 'descricao' => 'Vencido', 'tipo' => 'receita', 'valor' => 100, 'data' => today()->subDays(5)->format('Y-m-d'), 'status' => 'pendente']);
        Lancamento::create(['company_id' => $this->company->id, 'descricao' => 'Vencido 2', 'tipo' => 'despesa', 'valor' => 50, 'data' => today()->subDays(10)->format('Y-m-d'), 'status' => 'pendente']);

        $data = $this->actingAs($this->admin)
            ->getJson(route('dashboard.sumario-financeiro'))
            ->assertOk()
            ->json();

        expect($data['inadimplentes_count'])->toBe(2);
    });

    it('gestor pode acessar', function () {
        $this->actingAs($this->gestor)
            ->getJson(route('dashboard.sumario-financeiro'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('dashboard.sumario-financeiro'))
            ->assertUnauthorized();
    });
});
