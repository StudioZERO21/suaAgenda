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
        'name' => 'Barbearia Inadimp', 'slug' => 'barbearia-inadimp',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

function makeLancInadimpl(string $companyId, float $valor, string $status, int $diasAtras, string $tipo = 'despesa'): Lancamento
{
    return Lancamento::create([
        'company_id' => $companyId,
        'tipo' => $tipo,
        'descricao' => 'Teste',
        'valor' => $valor,
        'data' => today()->subDays($diasAtras),
        'status' => $status,
    ]);
}

describe('financeiro_inadimplentes', function () {
    it('retorna estrutura correta sem inadimplentes', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.inadimplentes'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['total', 'valor_total', 'items']);
        expect($data['total'])->toBe(0);
        expect((float) $data['valor_total'])->toBe(0.0);
    });

    it('retorna apenas pendentes com data passada', function () {
        // Vencido — deve aparecer
        makeLancInadimpl($this->company->id, 500, 'pendente', 5);
        // Pago — não deve aparecer
        makeLancInadimpl($this->company->id, 300, 'pago', 5);
        // Pendente mas vence hoje — não deve aparecer
        Lancamento::create([
            'company_id' => $this->company->id,
            'tipo' => 'despesa',
            'descricao' => 'Hoje',
            'valor' => 200,
            'data' => today(),
            'status' => 'pendente',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.inadimplentes'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect((float) $data['valor_total'])->toBe(500.0);
        expect($data['items'][0]['dias_atraso'])->toBe(5);
    });

    it('calcula dias_atraso corretamente', function () {
        makeLancInadimpl($this->company->id, 100, 'pendente', 10);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.inadimplentes'))
            ->assertOk()
            ->json();

        expect($data['items'][0]['dias_atraso'])->toBe(10);
    });

    it('não retorna lançamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-inadimp', 'plano' => 'trial', 'ativo' => true]);
        makeLancInadimpl($outra->id, 999, 'pendente', 3);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.inadimplentes'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('financeiro.inadimplentes'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('financeiro.inadimplentes'))
            ->assertUnauthorized();
    });
});
