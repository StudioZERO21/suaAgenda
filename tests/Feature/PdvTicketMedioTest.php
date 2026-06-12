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
        'name' => 'Barbearia TicketM', 'slug' => 'barbearia-ticketm',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

function makeVendaTM(string $companyId, float $total, int $diasAtras = 2): Venda
{
    $produto = Produto::create([
        'company_id' => $companyId, 'nome' => "Prod TM {$total}", 'preco' => $total,
        'estoque' => 100, 'ativo' => true,
    ]);
    $venda = Venda::create([
        'company_id' => $companyId, 'subtotal' => $total,
        'desconto' => 0, 'total' => $total, 'metodo_pagamento' => 'dinheiro',
    ]);
    $venda->created_at = now()->subDays($diasAtras);
    $venda->save();

    VendaItem::create([
        'venda_id' => $venda->id, 'produto_id' => $produto->id,
        'descricao' => 'Item', 'qtd' => 1, 'preco_unit' => $total, 'total' => $total,
    ]);

    return $venda;
}

describe('pdv_ticket_medio', function () {
    it('retorna estrutura correta sem vendas', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.ticket-medio'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo_dias', 'total_vendas', 'valor_total', 'ticket_medio', 'ticket_min', 'ticket_max', 'por_dia_semana']);
        expect($data['total_vendas'])->toBe(0);
        expect($data['ticket_medio'])->toBeNull();
        expect($data['por_dia_semana'])->toHaveCount(7);
    });

    it('calcula ticket medio, min e max corretamente', function () {
        makeVendaTM($this->company->id, 100.0);
        makeVendaTM($this->company->id, 50.0);
        makeVendaTM($this->company->id, 200.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.ticket-medio'))
            ->assertOk()
            ->json();

        expect($data['total_vendas'])->toBe(3);
        expect((float) $data['ticket_medio'])->toBe(116.67);
        expect((float) $data['ticket_min'])->toBe(50.0);
        expect((float) $data['ticket_max'])->toBe(200.0);
        expect((float) $data['valor_total'])->toBe(350.0);
    });

    it('respeita parâmetro dias', function () {
        makeVendaTM($this->company->id, 100.0, 5);
        makeVendaTM($this->company->id, 200.0, 60);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.ticket-medio', ['dias' => 30]))
            ->assertOk()
            ->json();

        expect($data['total_vendas'])->toBe(1);
        expect($data['periodo_dias'])->toBe(30);
    });

    it('ignora vendas de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra TM', 'slug' => 'outra-tm', 'plano' => 'trial', 'ativo' => true]);
        makeVendaTM($outra->id, 150.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('pdv.ticket-medio'))
            ->assertOk()
            ->json();

        expect($data['total_vendas'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('pdv.ticket-medio'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('pdv.ticket-medio'))
            ->assertUnauthorized();
    });
});
