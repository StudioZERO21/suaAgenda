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
        'name' => 'Barbearia TopCat', 'slug' => 'barbearia-topcat',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

function makeLanc(string $companyId, string $categoria, string $tipo, float $valor, string $status = 'pago'): Lancamento
{
    return Lancamento::create([
        'company_id' => $companyId,
        'tipo' => $tipo,
        'descricao' => "Teste {$categoria}",
        'categoria' => $categoria,
        'valor' => $valor,
        'data' => today(),
        'status' => $status,
    ]);
}

describe('financeiro_top_categorias', function () {
    it('retorna estrutura correta sem lançamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.top-categorias'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['total_categorias', 'items']);
        expect($data['total_categorias'])->toBe(0);
        expect($data['items'])->toBeEmpty();
    });

    it('agrupa lançamentos por categoria com saldo', function () {
        makeLanc($this->company->id, 'Aluguel', 'despesa', 1000);
        makeLanc($this->company->id, 'Aluguel', 'despesa', 500);
        makeLanc($this->company->id, 'Serviços', 'receita', 2000);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.top-categorias'))
            ->assertOk()
            ->json();

        expect($data['total_categorias'])->toBe(2);

        $servicos = collect($data['items'])->firstWhere('categoria', 'Serviços');
        expect((float) $servicos['total_receitas'])->toBe(2000.0);
        expect((float) $servicos['total_despesas'])->toBe(0.0);
        expect((float) $servicos['saldo'])->toBe(2000.0);

        $aluguel = collect($data['items'])->firstWhere('categoria', 'Aluguel');
        expect((float) $aluguel['total_despesas'])->toBe(1500.0);
        expect($aluguel['total_lancamentos'])->toBe(2);
    });

    it('filtra por tipo receita', function () {
        makeLanc($this->company->id, 'Serviços', 'receita', 200);
        makeLanc($this->company->id, 'Aluguel', 'despesa', 100);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.top-categorias', ['tipo' => 'receita']))
            ->assertOk()
            ->json();

        expect($data['total_categorias'])->toBe(1);
        expect($data['items'][0]['categoria'])->toBe('Serviços');
    });

    it('ignora lançamentos cancelados', function () {
        makeLanc($this->company->id, 'Serviços', 'receita', 200, 'cancelado');

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.top-categorias'))
            ->assertOk()
            ->json();

        expect($data['total_categorias'])->toBe(0);
    });

    it('não retorna lançamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-topcat', 'plano' => 'trial', 'ativo' => true]);
        makeLanc($outra->id, 'Serviços', 'receita', 500);

        $data = $this->actingAs($this->admin)
            ->getJson(route('financeiro.top-categorias'))
            ->assertOk()
            ->json();

        expect($data['total_categorias'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('financeiro.top-categorias'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('financeiro.top-categorias'))
            ->assertUnauthorized();
    });
});
