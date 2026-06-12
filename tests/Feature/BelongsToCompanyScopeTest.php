<?php

declare(strict_types=1);

use App\Models\Cargo;
use App\Models\Company;
use App\Models\Lancamento;
use App\Models\Notificacao;
use App\Models\PortfolioItem;
use App\Models\Produto;
use App\Models\User;
use App\Models\Venda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);

    $this->empresaA = Company::create(['name' => 'Empresa A', 'slug' => 'empresa-a', 'plano' => 'trial', 'ativo' => true]);
    $this->empresaB = Company::create(['name' => 'Empresa B', 'slug' => 'empresa-b', 'plano' => 'trial', 'ativo' => true]);

    $this->userA = User::create([
        'name' => 'Admin A', 'email' => 'admin-a@teste.com',
        'password' => bcrypt('secret123'), 'empresa_id' => $this->empresaA->id,
    ]);
    $this->userA->assignRole('admin_empresa');

    $this->superAdmin = User::create([
        'name' => 'Super', 'email' => 'super@teste.com',
        'password' => bcrypt('secret123'),
    ]);
    $this->superAdmin->assignRole('super_admin');
});

describe('belongs_to_company_scope', function () {
    it('esconde registros de outra empresa nas queries autenticadas', function () {
        $cargoA = Cargo::create(['company_id' => $this->empresaA->id, 'nome' => 'Barbeiro A', 'nivel' => 'professional']);
        $cargoB = Cargo::create(['company_id' => $this->empresaB->id, 'nome' => 'Barbeiro B', 'nivel' => 'professional']);

        $this->actingAs($this->userA);

        $visiveis = Cargo::pluck('id');
        expect($visiveis)->toContain($cargoA->id)
            ->and($visiveis)->not->toContain($cargoB->id);
    });

    it('aplica o escopo a Produto, Venda, Lancamento, PortfolioItem e Notificacao', function () {
        $modelos = [
            [Produto::class, ['nome' => 'Pomada', 'preco' => 30, 'estoque' => 5]],
            [Venda::class, ['subtotal' => 10, 'desconto' => 0, 'total' => 10, 'metodo_pagamento' => 'pix']],
            [Lancamento::class, ['tipo' => 'receita', 'descricao' => 'Teste', 'valor' => 10, 'data' => now()->format('Y-m-d'), 'status' => 'pago']],
            [PortfolioItem::class, ['titulo' => 'Foto', 'imagem_path' => 'x.jpg']],
            [Notificacao::class, ['tipo' => 'novo_agendamento', 'titulo' => 'T', 'mensagem' => 'M']],
        ];

        foreach ($modelos as [$classe, $attrs]) {
            $classe::create(array_merge($attrs, ['company_id' => $this->empresaB->id]));
        }

        $this->actingAs($this->userA);

        foreach ($modelos as [$classe]) {
            expect($classe::count())->toBe(0, "{$classe} vazou registro de outra empresa");
        }
    });

    it('super_admin sem empresa enxerga registros de todas as empresas', function () {
        Cargo::create(['company_id' => $this->empresaA->id, 'nome' => 'A', 'nivel' => 'professional']);
        Cargo::create(['company_id' => $this->empresaB->id, 'nome' => 'B', 'nivel' => 'professional']);

        $this->actingAs($this->superAdmin);

        expect(Cargo::count())->toBe(2);
    });

    it('preenche company_id automaticamente no creating', function () {
        $this->actingAs($this->userA);

        $cargo = Cargo::create(['nome' => 'Auto', 'nivel' => 'professional']);

        expect($cargo->company_id)->toBe($this->empresaA->id);
    });

    it('retorna 404 ao acessar cargo de outra empresa por rota', function () {
        $cargoB = Cargo::create(['company_id' => $this->empresaB->id, 'nome' => 'B', 'nivel' => 'professional']);

        $this->actingAs($this->userA)
            ->getJson(route('cargos.detalhe', $cargoB))
            ->assertNotFound();
    });
});
