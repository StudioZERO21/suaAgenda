<?php

declare(strict_types=1);

use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia SvcExp', 'slug' => 'barbearia-svcexp',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'phone' => '11999990001']);

    $this->corte = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);

    $this->barba = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Barba',
        'duracao_minutos' => 20, 'preco' => 30.00, 'cor' => '#d4a574', 'ativo' => false,
    ]);
});

describe('servico_export', function () {
    it('exporta CSV com header correto', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('servicos.exportar'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        expect($content)->toContain('Nome')
            ->and($content)->toContain('Duração')
            ->and($content)->toContain('Status');
    });

    it('inclui todos os serviços da empresa', function () {
        $content = $this->actingAs($this->admin)
            ->get(route('servicos.exportar'))
            ->streamedContent();

        expect($content)->toContain('Corte')
            ->and($content)->toContain('Barba');
    });

    it('indica serviço ativo e inativo', function () {
        $content = $this->actingAs($this->admin)
            ->get(route('servicos.exportar'))
            ->streamedContent();

        expect($content)->toContain('Ativo')
            ->and($content)->toContain('Inativo');
    });

    it('não expõe serviços de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-svcexp', 'plano' => 'trial', 'ativo' => true]);
        Servico::create([
            'company_id' => $outra->id, 'nome' => 'Serviço Intruso',
            'duracao_minutos' => 30, 'preco' => 999.0, 'cor' => '#ff0000', 'ativo' => true,
        ]);

        $content = $this->actingAs($this->admin)
            ->get(route('servicos.exportar'))
            ->streamedContent();

        expect($content)->not->toContain('Serviço Intruso');
    });

    it('analista pode exportar', function () {
        $this->actingAs($this->analista)
            ->get(route('servicos.exportar'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->get(route('servicos.exportar'))
            ->assertRedirect(route('login'));
    });
});
