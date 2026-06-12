<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Lancamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Comp', 'slug' => 'barbearia-comp',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

function makeLancComp(string $companyId, string $tipo, float $valor, string $data): Lancamento
{
    return Lancamento::create([
        'company_id' => $companyId,
        'tipo' => $tipo,
        'descricao' => 'Teste',
        'valor' => $valor,
        'data' => $data,
        'status' => 'pago',
    ]);
}

describe('financeiro_comparativo', function () {
    it('retorna estrutura correta sem lançamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.comparativo'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo_atual', 'periodo_anterior', 'variacao_receita_pct', 'variacao_despesa_pct', 'variacao_saldo_pct']);
        expect($data['periodo_atual'])->toHaveKeys(['mes', 'ano', 'mes_nome', 'total_lancamentos', 'receita', 'despesa', 'saldo']);
        expect((float) $data['periodo_atual']['receita'])->toBe(0.0);
        expect($data['variacao_receita_pct'])->toBeNull();
    });

    it('calcula receita e despesa do mês atual', function () {
        $mes = now()->month;
        $ano = now()->year;
        $dataAtual = now()->format('Y-m-d');

        makeLancComp($this->company->id, 'receita', 500.0, $dataAtual);
        makeLancComp($this->company->id, 'receita', 300.0, $dataAtual);
        makeLancComp($this->company->id, 'despesa', 100.0, $dataAtual);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.comparativo', ['mes' => $mes, 'ano' => $ano]))
            ->assertOk()
            ->json();

        expect((float) $data['periodo_atual']['receita'])->toBe(800.0);
        expect((float) $data['periodo_atual']['despesa'])->toBe(100.0);
        expect((float) $data['periodo_atual']['saldo'])->toBe(700.0);
        expect($data['periodo_atual']['total_lancamentos'])->toBe(3);
    });

    it('calcula variação percentual entre períodos', function () {
        $anterior = now()->subMonth();
        $dataAnterior = $anterior->copy()->startOfMonth()->format('Y-m-d');
        $dataAtual = now()->format('Y-m-d');

        makeLancComp($this->company->id, 'receita', 1000.0, $dataAnterior);
        makeLancComp($this->company->id, 'receita', 1200.0, $dataAtual);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.comparativo'))
            ->assertOk()
            ->json();

        expect((float) $data['periodo_anterior']['receita'])->toBe(1000.0);
        expect((float) $data['periodo_atual']['receita'])->toBe(1200.0);
        expect((float) $data['variacao_receita_pct'])->toBe(20.0);
    });

    it('ignora lançamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-comp', 'plano' => 'trial', 'ativo' => true]);
        makeLancComp($outra->id, 'receita', 9999.0, now()->format('Y-m-d'));

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.comparativo'))
            ->assertOk()
            ->json();

        expect((float) $data['periodo_atual']['receita'])->toBe(0.0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('financeiro.comparativo'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('financeiro.comparativo'))
            ->assertUnauthorized();
    });
});
