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
        'name' => 'Barbearia Fid', 'slug' => 'barbearia-fid',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);

    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana Top', 'phone' => '11999990001']);
    $this->cliente2 = Cliente::create(['company_id' => $this->company->id, 'name' => 'Bob Menor', 'phone' => '11999990002']);
});

function makeFidAg($self, $cliente, $status = 'finalizado', $valor = 50.00): void
{
    Agendamento::create([
        'company_id' => $self->company->id,
        'cliente_id' => $cliente->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $self->servico->id,
        'data_hora' => now()->subDays(3)->setTime(10, 0),
        'duracao' => 30,
        'status' => $status,
        'valor' => $valor,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('fidelidade', function () {
    it('exibe aba fidelidade na página de relatórios', function () {
        $this->actingAs($this->user)
            ->get(route('relatorios'))
            ->assertOk()
            ->assertSee('Fidelidade');
    });

    it('retorna fidelidade com clientes ordenados por visitas', function () {
        makeFidAg($this, $this->cliente);
        makeFidAg($this, $this->cliente);
        makeFidAg($this, $this->cliente2);

        $stats = $this->actingAs($this->user)->get(route('relatorios'))->viewData('fidelidade');
        expect($stats->first()['name'])->toBe('Ana Top');
        expect($stats->first()['visitas'])->toBe(2);
    });

    it('não conta agendamentos cancelados na fidelidade', function () {
        makeFidAg($this, $this->cliente, 'cancelado');
        makeFidAg($this, $this->cliente2, 'finalizado');

        $stats = $this->actingAs($this->user)->get(route('relatorios'))->viewData('fidelidade');
        // cliente cancelado não aparece, cliente2 aparece
        expect($stats->where('name', 'Ana Top')->count())->toBe(0);
        expect($stats->where('name', 'Bob Menor')->count())->toBe(1);
    });

    it('calcula total_gasto só dos finalizados', function () {
        makeFidAg($this, $this->cliente, 'finalizado', 100.00);
        makeFidAg($this, $this->cliente, 'pendente', 100.00);

        $stats = $this->actingAs($this->user)->get(route('relatorios'))->viewData('fidelidade');
        $row = $stats->firstWhere('name', 'Ana Top');
        expect($row['gasto'])->toBe(100.0);
        expect($row['visitas'])->toBe(2);
    });

    it('exporta CSV de fidelidade', function () {
        makeFidAg($this, $this->cliente);

        $response = $this->actingAs($this->user)->get(route('relatorios.fidelidade.exportar'));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        expect($response->streamedContent())->toContain('Ana Top');
        expect($response->streamedContent())->toContain('Posição');
    });

    it('não expõe fidelidade de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra3', 'plano' => 'trial', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Fulano Externo', 'phone' => '11900001111']);
        Agendamento::create([
            'company_id' => $outra->id, 'cliente_id' => $cliOutra->id,
            'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
            'data_hora' => now()->subDay()->setTime(9, 0), 'duracao' => 30,
            'status' => 'finalizado', 'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $stats = $this->actingAs($this->user)->get(route('relatorios'))->viewData('fidelidade');
        expect($stats->where('name', 'Fulano Externo')->count())->toBe(0);
    });
});
