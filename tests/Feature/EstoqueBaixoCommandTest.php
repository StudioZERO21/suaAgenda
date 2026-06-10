<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Notificacao;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'name' => 'Loja Estoque',
        'slug' => 'loja-estoque',
        'plano' => 'starter',
        'ativo' => true,
    ]);
});

describe('estoque_baixo_command', function () {
    it('cria notificação quando produto tem estoque <= mínimo', function () {
        Produto::create([
            'company_id' => $this->company->id,
            'nome' => 'Shampoo',
            'preco' => 25.00,
            'estoque' => 2,
            'estoque_min' => 5,
            'ativo' => true,
        ]);

        $this->artisan('produtos:estoque-baixo')->assertSuccessful();

        expect(Notificacao::where('company_id', $this->company->id)->where('tipo', 'estoque_baixo')->count())->toBe(1);
    });

    it('não cria notificação quando estoque está acima do mínimo', function () {
        Produto::create([
            'company_id' => $this->company->id,
            'nome' => 'Condicionador',
            'preco' => 20.00,
            'estoque' => 10,
            'estoque_min' => 5,
            'ativo' => true,
        ]);

        $this->artisan('produtos:estoque-baixo')->assertSuccessful();

        expect(Notificacao::where('tipo', 'estoque_baixo')->count())->toBe(0);
    });

    it('não cria notificação quando estoque exatamente acima do mínimo', function () {
        Produto::create([
            'company_id' => $this->company->id,
            'nome' => 'Produto OK',
            'preco' => 10.00,
            'estoque' => 6,
            'estoque_min' => 5,
            'ativo' => true,
        ]);

        $this->artisan('produtos:estoque-baixo')->assertSuccessful();

        expect(Notificacao::where('tipo', 'estoque_baixo')->count())->toBe(0);
    });

    it('agrupa múltiplos produtos baixos em uma notificação por empresa', function () {
        Produto::create(['company_id' => $this->company->id, 'nome' => 'A', 'preco' => 10, 'estoque' => 1, 'estoque_min' => 5, 'ativo' => true]);
        Produto::create(['company_id' => $this->company->id, 'nome' => 'B', 'preco' => 10, 'estoque' => 2, 'estoque_min' => 5, 'ativo' => true]);

        $this->artisan('produtos:estoque-baixo')->assertSuccessful();

        $notif = Notificacao::where('company_id', $this->company->id)->where('tipo', 'estoque_baixo')->first();
        expect($notif)->not->toBeNull()
            ->and($notif->mensagem)->toContain('A')
            ->and($notif->mensagem)->toContain('B');
        expect(Notificacao::where('tipo', 'estoque_baixo')->count())->toBe(1);
    });

    it('isolamento: gera notificação separada por empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-est', 'plano' => 'trial', 'ativo' => true]);

        Produto::create(['company_id' => $this->company->id, 'nome' => 'Prod1', 'preco' => 10, 'estoque' => 1, 'estoque_min' => 5, 'ativo' => true]);
        Produto::create(['company_id' => $outra->id, 'nome' => 'Prod2', 'preco' => 10, 'estoque' => 1, 'estoque_min' => 5, 'ativo' => true]);

        $this->artisan('produtos:estoque-baixo')->assertSuccessful();

        expect(Notificacao::where('tipo', 'estoque_baixo')->count())->toBe(2);
    });

    it('ignora produtos inativos', function () {
        Produto::create([
            'company_id' => $this->company->id,
            'nome' => 'Inativo',
            'preco' => 10.00,
            'estoque' => 0,
            'estoque_min' => 5,
            'ativo' => false,
        ]);

        $this->artisan('produtos:estoque-baixo')->assertSuccessful();

        expect(Notificacao::where('tipo', 'estoque_baixo')->count())->toBe(0);
    });
});
