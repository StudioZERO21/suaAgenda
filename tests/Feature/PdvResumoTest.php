<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Produto;
use App\Models\User;
use App\Models\Venda;
use App\Models\VendaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia PdvResumo', 'slug' => 'barbearia-pdvresumo',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->produto = Produto::create([
        'company_id' => $this->company->id,
        'nome' => 'Pomada', 'preco' => 35.00, 'estoque' => 10, 'ativo' => true,
    ]);
});

function makePdvVenda($self, float $total = 100.0, float $desconto = 0.0): Venda
{
    $venda = Venda::create([
        'company_id' => $self->company->id,
        'subtotal' => $total,
        'desconto' => $desconto,
        'total' => $total - $desconto,
        'metodo_pagamento' => 'pix',
    ]);

    VendaItem::create([
        'venda_id' => $venda->id,
        'produto_id' => $self->produto->id,
        'descricao' => 'Pomada',
        'qtd' => 2,
        'preco_unit' => 35.00,
        'total' => 70.00,
    ]);

    return $venda;
}

describe('pdv_resumo', function () {
    it('retorna estrutura correta', function () {
        $this->actingAs($this->admin)
            ->getJson(route('pdv.resumo'))
            ->assertOk()
            ->assertJsonStructure([
                'periodo', 'total_vendas', 'receita_total',
                'desconto_total', 'total_itens', 'ticket_medio',
            ]);
    });

    it('conta vendas de hoje corretamente', function () {
        makePdvVenda($this, 100.0);
        makePdvVenda($this, 200.0, 10.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.resumo'))
            ->json();

        expect($data['total_vendas'])->toBe(2);
        expect((float) $data['receita_total'])->toBe(290.0);
        expect((float) $data['desconto_total'])->toBe(10.0);
    });

    it('retorna zeros quando sem vendas', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.resumo'))
            ->json();

        expect($data['total_vendas'])->toBe(0);
        expect((float) $data['receita_total'])->toBe(0.0);
        expect((float) $data['ticket_medio'])->toBe(0.0);
    });

    it('não expõe vendas de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-pdvresumo', 'plano' => 'trial', 'ativo' => true]);
        $prodOutra = Produto::create(['company_id' => $outra->id, 'nome' => 'Prod', 'preco' => 30.0, 'estoque' => 5, 'ativo' => true]);
        $vendaOutra = Venda::create(['company_id' => $outra->id, 'subtotal' => 500.0, 'desconto' => 0, 'total' => 500.0, 'metodo_pagamento' => 'pix']);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.resumo'))
            ->json();

        expect($data['total_vendas'])->toBe(0);
    });

    it('analista pode ver resumo', function () {
        $this->actingAs($this->analista)
            ->getJson(route('pdv.resumo'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('pdv.resumo'))
            ->assertUnauthorized();
    });
});
