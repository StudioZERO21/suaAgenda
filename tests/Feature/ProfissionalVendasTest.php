<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\User;
use App\Models\Venda;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia PV', 'slug' => 'barbearia-pv',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
});

function makeProfVenda(string $companyId, string $profId, float $total): Venda
{
    return Venda::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'subtotal' => $total,
        'desconto' => 0,
        'total' => $total,
        'metodo_pagamento' => 'pix',
    ]);
}

describe('profissional_vendas', function () {
    it('retorna estrutura correta quando sem vendas', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.vendas', $this->prof))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['total_vendas', 'total_receita', 'items']);
        expect($data['total_vendas'])->toBe(0);
        expect((float) $data['total_receita'])->toBe(0.0);
    });

    it('retorna vendas do profissional', function () {
        makeProfVenda($this->company->id, $this->prof->id, 80.0);
        makeProfVenda($this->company->id, $this->prof->id, 60.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.vendas', $this->prof))
            ->assertOk()
            ->json();

        expect($data['total_vendas'])->toBe(2);
        expect((float) $data['total_receita'])->toBe(140.0);
        expect($data['items'][0])->toHaveKeys(['id', 'data', 'total', 'metodo_pagamento', 'total_itens']);
    });

    it('não inclui vendas de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-pv', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        makeProfVenda($outra->id, $profOutra->id, 999.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.vendas', $this->prof))
            ->assertOk()
            ->json();

        expect($data['total_vendas'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('profissionais.vendas', $this->prof))
            ->assertOk();
    });

    it('não pode ver vendas de profissional de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra2', 'slug' => 'outra2-pv', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'Y', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->getJson(route('profissionais.vendas', $profOutra))
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('profissionais.vendas', $this->prof))
            ->assertUnauthorized();
    });
});
