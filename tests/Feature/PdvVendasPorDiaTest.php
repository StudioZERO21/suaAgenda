<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use App\Models\Venda;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia PorDia', 'slug' => 'barbearia-pordia',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

function makeVendaDia(string $companyId, float $total, Carbon $data): Venda
{
    $venda = Venda::create([
        'company_id' => $companyId,
        'subtotal' => $total,
        'desconto' => 0,
        'total' => $total,
        'metodo_pagamento' => 'pix',
    ]);
    $venda->created_at = $data;
    $venda->save();

    return $venda;
}

describe('pdv_vendas_por_dia', function () {
    it('retorna estrutura com todos os dias do mês', function () {
        $mes = now()->month;
        $ano = now()->year;
        $diasNoMes = now()->daysInMonth;

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.vendas.por-dia', ['mes' => $mes, 'ano' => $ano]))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['mes', 'ano', 'total_mes', 'receita_mes', 'dias']);
        expect($data['dias'])->toHaveCount($diasNoMes);
        expect($data['dias'][0])->toHaveKeys(['data', 'total_vendas', 'receita']);
    });

    it('conta vendas no dia correto', function () {
        $mes = now()->month;
        $ano = now()->year;
        $primeiroDia = Carbon::createFromDate($ano, $mes, 1)->startOfDay();

        makeVendaDia($this->company->id, 150.0, $primeiroDia->copy()->setTime(10, 0));
        makeVendaDia($this->company->id, 200.0, $primeiroDia->copy()->setTime(14, 0));

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.vendas.por-dia', ['mes' => $mes, 'ano' => $ano]))
            ->assertOk()
            ->json();

        expect($data['total_mes'])->toBe(2);
        expect((float) $data['receita_mes'])->toBe(350.0);
        expect($data['dias'][0]['total_vendas'])->toBe(2);
        expect((float) $data['dias'][0]['receita'])->toBe(350.0);
    });

    it('aceita parâmetros mes e ano', function () {
        $mesAnterior = now()->subMonth();
        $data = now()->subMonth()->startOfMonth()->setTime(10, 0);
        makeVendaDia($this->company->id, 100.0, $data);

        $response = $this->actingAs($this->admin)
            ->getJson(route('pdv.vendas.por-dia', ['mes' => $mesAnterior->month, 'ano' => $mesAnterior->year]))
            ->assertOk()
            ->json();

        expect($response['total_mes'])->toBe(1);
        expect($response['mes'])->toBe($mesAnterior->month);
    });

    it('ignora vendas de outro mês', function () {
        $mesAnterior = now()->subMonth()->startOfMonth()->setTime(10, 0);
        makeVendaDia($this->company->id, 999.0, $mesAnterior);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.vendas.por-dia'))
            ->assertOk()
            ->json();

        expect($data['total_mes'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('pdv.vendas.por-dia'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('pdv.vendas.por-dia'))
            ->assertUnauthorized();
    });
});
