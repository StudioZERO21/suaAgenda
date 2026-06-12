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
        'name' => 'Barbearia MetPag', 'slug' => 'barbearia-metpag',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

function makeLancMetodo(string $companyId, string $metodo, float $valor, bool $pago = true): Lancamento
{
    return Lancamento::create([
        'company_id' => $companyId,
        'tipo' => 'receita',
        'descricao' => 'Teste',
        'valor' => $valor,
        'data' => now()->format('Y-m-d'),
        'status' => $pago ? 'pago' : 'pendente',
        'metodo_pagamento' => $metodo,
    ]);
}

describe('financeiro_por_metodo_pagamento', function () {
    it('retorna lista vazia sem lançamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.por-metodo-pagamento'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo_dias', 'total_lancamentos', 'valor_total', 'items']);
        expect($data['total_lancamentos'])->toBe(0);
        expect((float) $data['valor_total'])->toBe(0.0);
        expect($data['items'])->toBeEmpty();
    });

    it('agrupa por método de pagamento', function () {
        makeLancMetodo($this->company->id, 'pix', 200.0);
        makeLancMetodo($this->company->id, 'pix', 100.0);
        makeLancMetodo($this->company->id, 'dinheiro', 150.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.por-metodo-pagamento'))
            ->assertOk()
            ->json();

        expect($data['total_lancamentos'])->toBe(3);
        expect((float) $data['valor_total'])->toBe(450.0);
        expect($data['items'])->toHaveCount(2);

        $pix = collect($data['items'])->firstWhere('metodo', 'pix');
        expect($pix['total_lancamentos'])->toBe(2);
        expect((float) $pix['valor_total'])->toBe(300.0);
    });

    it('ordena por valor total decrescente', function () {
        makeLancMetodo($this->company->id, 'dinheiro', 50.0);
        makeLancMetodo($this->company->id, 'pix', 500.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.por-metodo-pagamento'))
            ->assertOk()
            ->json();

        expect($data['items'][0]['metodo'])->toBe('pix');
    });

    it('ignora lançamentos pendentes', function () {
        makeLancMetodo($this->company->id, 'pix', 100.0, true);
        makeLancMetodo($this->company->id, 'cartao', 200.0, false);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.por-metodo-pagamento'))
            ->assertOk()
            ->json();

        expect($data['total_lancamentos'])->toBe(1);
        expect($data['items'][0]['metodo'])->toBe('pix');
    });

    it('respeita o parâmetro dias', function () {
        makeLancMetodo($this->company->id, 'pix', 100.0);
        Lancamento::create([
            'company_id' => $this->company->id, 'tipo' => 'receita', 'descricao' => 'Antigo',
            'valor' => 200.0, 'data' => now()->subDays(60)->format('Y-m-d'),
            'status' => 'pago', 'metodo_pagamento' => 'pix',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.por-metodo-pagamento', ['dias' => 30]))
            ->assertOk()
            ->json();

        expect($data['total_lancamentos'])->toBe(1);
        expect($data['periodo_dias'])->toBe(30);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('financeiro.por-metodo-pagamento'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('financeiro.por-metodo-pagamento'))
            ->assertUnauthorized();
    });
});
