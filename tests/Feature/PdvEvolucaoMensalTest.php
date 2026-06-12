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
        'name' => 'Barbearia EM', 'slug' => 'barbearia-em',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

function makeVendaEM(string $companyId, float $total, Carbon $createdAt): Venda
{
    $venda = Venda::create([
        'company_id' => $companyId,
        'total' => $total,
        'desconto' => 0,
        'subtotal' => $total,
        'metodo_pagamento' => 'dinheiro',
    ]);
    $venda->created_at = $createdAt;
    $venda->save();

    return $venda;
}

describe('pdv_evolucao_mensal', function () {
    it('retorna estrutura correta sem vendas', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.evolucao-mensal'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['meses', 'serie']);
        expect($data['meses'])->toBe(6);
        expect($data['serie'])->toHaveCount(6);
    });

    it('cada item da série tem campos corretos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.evolucao-mensal'))
            ->assertOk()
            ->json();

        $item = $data['serie'][0];
        expect($item)->toHaveKeys(['mes', 'ano', 'mes_fmt', 'total_vendas', 'valor_total', 'ticket_medio']);
    });

    it('respeita parâmetro meses', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.evolucao-mensal', ['meses' => 3]))
            ->assertOk()
            ->json();

        expect($data['meses'])->toBe(3);
        expect($data['serie'])->toHaveCount(3);
    });

    it('calcula totais corretamente para o mês atual', function () {
        makeVendaEM($this->company->id, 100.0, now()->startOfMonth());
        makeVendaEM($this->company->id, 200.0, now()->startOfMonth()->addDays(5));

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.evolucao-mensal', ['meses' => 2]))
            ->assertOk()
            ->json();

        $mesAtual = $data['serie'][1];
        expect($mesAtual['total_vendas'])->toBe(2);
        expect((float) $mesAtual['valor_total'])->toBe(300.0);
        expect((float) $mesAtual['ticket_medio'])->toBe(150.0);
    });

    it('ignora vendas de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra EM', 'slug' => 'outra-em', 'plano' => 'trial', 'ativo' => true]);
        makeVendaEM($outra->id, 500.0, now());

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.evolucao-mensal', ['meses' => 2]))
            ->assertOk()
            ->json();

        expect($data['serie'][1]['total_vendas'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('pdv.evolucao-mensal'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('pdv.evolucao-mensal'))
            ->assertUnauthorized();
    });
});
