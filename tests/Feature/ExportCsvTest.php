<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Lancamento;
use App\Models\Profissional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Export',
        'slug' => 'empresa-export',
        'plano' => 'trial',
        'whatsapp' => '(11) 99999-0030',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');
});

describe('financeiro csv export', function () {
    it('retorna csv com cabeçalho correto', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('financeiro.exportar'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        expect($response->headers->get('content-disposition'))->toContain('financeiro-');
    });

    it('csv inclui agendamentos do período', function () {
        $profissional = Profissional::create([
            'company_id' => $this->company->id,
            'name' => 'Prof Teste',
            'ativo' => true,
        ]);

        $cliente = Cliente::create([
            'company_id' => $this->company->id,
            'name' => 'Cliente Teste',
        ]);

        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $profissional->id,
            'cliente_id' => $cliente->id,
            'data_hora' => now()->startOfMonth()->addDays(2)->setTime(10, 0),
            'status' => 'finalizado',
            'valor' => 80.00,
            'duracao' => 30,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('financeiro.exportar', ['periodo' => 'month']));

        $response->assertOk();
        $content = $response->streamedContent();
        expect($content)->toContain('Data');
        expect($content)->toContain('Descrição');
        expect($content)->toContain('Valor (R$)');
    });

    it('csv inclui lançamentos do período', function () {
        Lancamento::create([
            'company_id' => $this->company->id,
            'tipo' => 'despesa',
            'descricao' => 'Aluguel exportado',
            'valor' => 500.00,
            'data' => now()->startOfMonth()->format('Y-m-d'),
            'status' => 'pago',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('financeiro.exportar', ['periodo' => 'month']));

        $content = $response->streamedContent();
        expect($content)->toContain('Aluguel exportado');
    });
});

describe('relatorio csv export', function () {
    it('retorna csv com cabeçalho correto', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('relatorios.exportar'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        expect($response->headers->get('content-disposition'))->toContain('relatorio-');
    });

    it('csv inclui colunas esperadas', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('relatorios.exportar', ['preset' => '30d']));

        $content = $response->streamedContent();
        expect($content)->toContain('Cliente');
        expect($content)->toContain('Serviço');
        expect($content)->toContain('Profissional');
    });
});
