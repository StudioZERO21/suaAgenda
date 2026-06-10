<?php

declare(strict_types=1);

use App\Models\Cliente;
use App\Models\Company;
use App\Models\Notificacao;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'name' => 'Barbearia Aniversário',
        'slug' => 'barbearia-aniversario',
        'plano' => 'trial',
        'ativo' => true,
    ]);
});

describe('aniversarios_command', function () {
    it('cria notificação para aniversariante do dia', function () {
        Cliente::create([
            'company_id' => $this->company->id,
            'name' => 'Maria Hoje',
            'ativo' => true,
            'data_nasc' => now()->setYear(1990)->toDateString(),
        ]);

        $this->artisan('clientes:aniversarios')->assertSuccessful();

        expect(Notificacao::where('company_id', $this->company->id)->where('tipo', 'aniversario')->count())->toBe(1);
    });

    it('não cria notificação quando ninguém faz aniversário hoje', function () {
        Cliente::create([
            'company_id' => $this->company->id,
            'name' => 'Pedro Outro Dia',
            'ativo' => true,
            'data_nasc' => now()->addDay()->setYear(1990)->toDateString(),
        ]);

        $this->artisan('clientes:aniversarios')->assertSuccessful();

        expect(Notificacao::where('tipo', 'aniversario')->count())->toBe(0);
    });

    it('agrupa múltiplos aniversariantes em uma notificação por empresa', function () {
        Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana', 'ativo' => true, 'data_nasc' => now()->setYear(1990)->toDateString()]);
        Cliente::create(['company_id' => $this->company->id, 'name' => 'Bia', 'ativo' => true, 'data_nasc' => now()->setYear(1995)->toDateString()]);

        $this->artisan('clientes:aniversarios')->assertSuccessful();

        $notif = Notificacao::where('company_id', $this->company->id)->where('tipo', 'aniversario')->first();
        expect($notif)->not->toBeNull()
            ->and($notif->mensagem)->toContain('Ana')
            ->and($notif->mensagem)->toContain('Bia');

        expect(Notificacao::where('tipo', 'aniversario')->count())->toBe(1);
    });

    it('isolamento: gera notificação separada por empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-aniv', 'plano' => 'trial', 'ativo' => true]);

        Cliente::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true, 'data_nasc' => now()->setYear(1988)->toDateString()]);
        Cliente::create(['company_id' => $outra->id, 'name' => 'Daniela', 'ativo' => true, 'data_nasc' => now()->setYear(1992)->toDateString()]);

        $this->artisan('clientes:aniversarios')->assertSuccessful();

        expect(Notificacao::where('tipo', 'aniversario')->count())->toBe(2);
        expect(Notificacao::where('company_id', $this->company->id)->where('tipo', 'aniversario')->count())->toBe(1);
        expect(Notificacao::where('company_id', $outra->id)->where('tipo', 'aniversario')->count())->toBe(1);
    });

    it('ignora clientes inativos', function () {
        Cliente::create([
            'company_id' => $this->company->id,
            'name' => 'Inativo',
            'ativo' => false,
            'data_nasc' => now()->setYear(1985)->toDateString(),
        ]);

        $this->artisan('clientes:aniversarios')->assertSuccessful();

        expect(Notificacao::where('tipo', 'aniversario')->count())->toBe(0);
    });

    it('ignora clientes sem data de nascimento', function () {
        Cliente::create([
            'company_id' => $this->company->id,
            'name' => 'Sem Data',
            'ativo' => true,
        ]);

        $this->artisan('clientes:aniversarios')->assertSuccessful();

        expect(Notificacao::where('tipo', 'aniversario')->count())->toBe(0);
    });
});
