<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Lancamento;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia LancVenc', 'slug' => 'barbearia-lancvenc',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

function makeLancVenc(string $companyId, string $tipo, float $valor, int $diasFuturos, string $status = 'pendente'): Lancamento
{
    return Lancamento::create([
        'company_id' => $companyId,
        'descricao' => "Lanc {$tipo} +{$diasFuturos}d",
        'tipo' => $tipo,
        'valor' => $valor,
        'data' => today()->addDays($diasFuturos)->format('Y-m-d'),
        'status' => $status,
    ]);
}

describe('financeiro_lancamentos_vencendo', function () {
    it('retorna estrutura correta sem lançamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.lancamentos-vencendo'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['dias', 'data_inicio', 'data_fim', 'total', 'total_receitas', 'total_despesas', 'items']);
        expect($data['total'])->toBe(0);
        expect($data['dias'])->toBe(7);
        expect($data['items'])->toBeEmpty();
    });

    it('retorna apenas lancamentos no período e pendentes', function () {
        makeLancVenc($this->company->id, 'receita', 300.0, 3);
        makeLancVenc($this->company->id, 'despesa', 150.0, 5);
        makeLancVenc($this->company->id, 'receita', 500.0, 10);
        makeLancVenc($this->company->id, 'receita', 200.0, 3, 'pago');

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.lancamentos-vencendo', ['dias' => 7]))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(2);
        expect((float) $data['total_receitas'])->toBe(300.0);
        expect((float) $data['total_despesas'])->toBe(150.0);
    });

    it('respeita parâmetro dias', function () {
        makeLancVenc($this->company->id, 'receita', 100.0, 5);
        makeLancVenc($this->company->id, 'receita', 200.0, 15);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.lancamentos-vencendo', ['dias' => 10]))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['dias'])->toBe(10);
    });

    it('filtro por tipo funciona', function () {
        makeLancVenc($this->company->id, 'receita', 300.0, 3);
        makeLancVenc($this->company->id, 'despesa', 150.0, 4);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.lancamentos-vencendo', ['tipo' => 'despesa']))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['items'][0]['tipo'])->toBe('despesa');
    });

    it('items têm campo dias_para_vencer correto', function () {
        makeLancVenc($this->company->id, 'receita', 200.0, 3);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.lancamentos-vencendo'))
            ->assertOk()
            ->json();

        expect($data['items'][0])->toHaveKeys(['id', 'descricao', 'categoria', 'tipo', 'valor', 'data_vencimento', 'dias_para_vencer']);
        expect($data['items'][0]['dias_para_vencer'])->toBe(3);
    });

    it('ignora lancamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra LV', 'slug' => 'outra-lv', 'plano' => 'trial', 'ativo' => true]);
        makeLancVenc($outra->id, 'receita', 500.0, 2);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.lancamentos-vencendo'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('financeiro.lancamentos-vencendo'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('financeiro.lancamentos-vencendo'))
            ->assertUnauthorized();
    });
});
