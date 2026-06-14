<?php

declare(strict_types=1);

use App\Models\Agendamento;
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

    $this->company = Company::create([
        'name' => 'Barbearia Dash', 'slug' => 'barbearia-dash',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Carlos', 'ativo' => true,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id,
        'nome' => 'Corte', 'duracao_minutos' => 30,
        'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id,
        'name' => 'João Teste',
        'phone' => '11999990001',
    ]);
});

describe('dashboard_semanal', function () {
    it('exibe card de últimos 7 dias', function () {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Últimos 7 dias');
    });

    it('inclui dados de agendamentos dos últimos 7 dias na view', function () {
        Agendamento::create([
            'company_id' => $this->company->id,
            'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->startOfDay()->setTime(10, 0),
            'duracao' => 30,
            'status' => 'confirmado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));
        $response->assertOk();

        $stats = $response->viewData('stats');
        $semana = $stats['semana'];
        expect($semana)->toHaveCount(7);

        $today = collect($semana)->firstWhere('isToday', true);
        expect($today)->not->toBeNull();
        expect($today['agendamentos'])->toBe(1);
    });

    it('não conta agendamentos cancelados na semana', function () {
        Agendamento::create([
            'company_id' => $this->company->id,
            'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->startOfDay()->setTime(10, 0),
            'duracao' => 30,
            'status' => 'cancelado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $stats = $this->actingAs($this->user)->get(route('dashboard'))->viewData('stats');
        $today = collect($stats['semana'])->firstWhere('isToday', true);
        expect($today['agendamentos'])->toBe(0);
    });

    it('maxSemana é pelo menos 1 quando há agendamentos', function () {
        Agendamento::create([
            'company_id' => $this->company->id,
            'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->startOfDay()->setTime(9, 0),
            'duracao' => 30,
            'status' => 'pendente',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $stats = $this->actingAs($this->user)->get(route('dashboard'))->viewData('stats');
        expect($stats['maxSemana'])->toBeGreaterThanOrEqual(1);
    });
});

describe('clientes_exportar_csv', function () {
    it('exporta CSV com cabeçalho correto', function () {
        $response = $this->actingAs($this->user)->get(route('clientes.exportar'));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        expect($response->streamedContent())->toContain('Nome');
        expect($response->streamedContent())->toContain('Telefone');
    });

    it('inclui cliente no CSV', function () {
        Cliente::create([
            'company_id' => $this->company->id,
            'name' => 'Maria Exportada',
            'phone' => '11900000001',
        ]);

        $content = $this->actingAs($this->user)->get(route('clientes.exportar'))->streamedContent();
        expect($content)->toContain('Maria Exportada');
    });

    it('não expõe clientes de outra empresa no CSV', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra2', 'plano' => 'trial', 'ativo' => true]);
        Cliente::create(['company_id' => $outra->id, 'name' => 'Pedro Secreto', 'phone' => '11900000002']);

        $content = $this->actingAs($this->user)->get(route('clientes.exportar'))->streamedContent();
        expect($content)->not->toContain('Pedro Secreto');
    });
});

describe('clientes_exportar_pdf', function () {
    it('exporta PDF com content-type correto', function () {
        $response = $this->actingAs($this->user)->get(route('clientes.exportar.pdf'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        expect(str_starts_with($response->getContent(), '%PDF'))->toBeTrue();
    });

    it('inclui cliente no PDF', function () {
        Cliente::create([
            'company_id' => $this->company->id,
            'name' => 'João PDF Teste',
            'phone' => '11900000003',
        ]);

        $response = $this->actingAs($this->user)->get(route('clientes.exportar.pdf'));

        $response->assertOk();
        expect($response->getContent())->not->toBeEmpty();
    });

    it('não expõe clientes de outra empresa no PDF', function () {
        $outra = Company::create(['name' => 'Outra PDF', 'slug' => 'outra-pdf', 'plano' => 'trial', 'ativo' => true]);
        Cliente::create(['company_id' => $outra->id, 'name' => 'Cliente Oculto PDF', 'phone' => '11900000004']);

        $html = view('clientes.export-pdf', [
            'clientes' => Cliente::where('company_id', $this->company->id)->withCount('agendamentos')->with(['agendamentos' => fn ($q) => $q->latest('data_hora')->limit(1)])->get(),
            'company' => $this->company,
        ])->render();

        expect($html)->not->toContain('Cliente Oculto PDF');
    });
});
