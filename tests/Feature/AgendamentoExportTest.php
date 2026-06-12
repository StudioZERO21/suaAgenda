<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'name' => 'Barbearia Export', 'slug' => 'barbearia-export',
        'plano' => 'trial', 'ativo' => true,
    ]);

    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana', 'phone' => '11999990001']);

    $this->ag = Agendamento::create([
        'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
        'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
        'data_hora' => now()->subDay()->setTime(10, 0), 'duracao' => 30,
        'valor' => 50.00, 'status' => 'finalizado',
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
});

describe('agendamento_export', function () {
    it('exporta CSV com cabeçalho correto', function () {
        $response = $this->actingAs($this->user)
            ->get(route('agendamentos.exportar'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        expect($content)->toContain('Cliente')
            ->toContain('Profissional')
            ->toContain('Serviço')
            ->toContain('Status')
            ->toContain('Avaliação');
    });

    it('exporta linha com dados do agendamento', function () {
        $content = $this->actingAs($this->user)
            ->get(route('agendamentos.exportar'))
            ->streamedContent();

        expect($content)->toContain('Ana')
            ->toContain('Carlos')
            ->toContain('Corte')
            ->toContain('Finalizado');
    });

    it('exporta nota de avaliação quando existe', function () {
        Avaliacao::create([
            'company_id' => $this->company->id,
            'agendamento_id' => $this->ag->id,
            'nota' => 5,
        ]);

        $content = $this->actingAs($this->user)
            ->get(route('agendamentos.exportar'))
            ->streamedContent();

        expect($content)->toContain('5/5');
    });

    it('coluna avaliação vazia quando sem avaliação', function () {
        $content = $this->actingAs($this->user)
            ->get(route('agendamentos.exportar'))
            ->streamedContent();

        expect($content)->not->toContain('/5');
    });

    it('view agendamentos exibe ícone estrela quando avaliado', function () {
        Avaliacao::create([
            'company_id' => $this->company->id,
            'agendamento_id' => $this->ag->id,
            'nota' => 4,
        ]);

        $this->actingAs($this->user)
            ->get(route('agendamentos.index', ['status' => 'finalizado']))
            ->assertOk()
            ->assertSee('Avaliação: 4/5', false);
    });
});
