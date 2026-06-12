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
        'name' => 'Barbearia Seg', 'slug' => 'barbearia-seg',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);
});

function makeSegAg($self, Cliente $cliente, int $diasAtras = 10): void
{
    Agendamento::create([
        'company_id' => $self->company->id,
        'cliente_id' => $cliente->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $self->servico->id,
        'data_hora' => now()->subDays($diasAtras),
        'duracao' => 30,
        'status' => 'finalizado',
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('cliente_segmento_export', function () {
    it('top: exporta apenas clientes com 3+ agendamentos', function () {
        $top = Cliente::create(['company_id' => $this->company->id, 'name' => 'Top Cliente', 'phone' => '11999990001']);
        $baixo = Cliente::create(['company_id' => $this->company->id, 'name' => 'Baixo Cliente', 'phone' => '11999990002']);

        makeSegAg($this, $top);
        makeSegAg($this, $top);
        makeSegAg($this, $top);
        makeSegAg($this, $baixo);

        $content = $this->actingAs($this->user)
            ->get(route('clientes.exportar.segmento', ['tipo' => 'top']))
            ->streamedContent();

        expect($content)->toContain('Top Cliente')
            ->and($content)->not->toContain('Baixo Cliente');
    });

    it('inativos: exporta clientes sem visita nos últimos 90 dias', function () {
        $ativo = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ativo', 'phone' => '11999990001']);
        $inativo = Cliente::create(['company_id' => $this->company->id, 'name' => 'Inativo', 'phone' => '11999990002']);

        makeSegAg($this, $ativo, 10);
        makeSegAg($this, $inativo, 120);

        $content = $this->actingAs($this->user)
            ->get(route('clientes.exportar.segmento', ['tipo' => 'inativos']))
            ->streamedContent();

        expect($content)->toContain('Inativo')
            ->and($content)->not->toContain('Ativo');
    });

    it('aniversariantes: exporta apenas clientes com data_nasc', function () {
        $comNasc = Cliente::create([
            'company_id' => $this->company->id, 'name' => 'Com Nasc',
            'phone' => '11999990001', 'data_nasc' => '1990-06-15',
        ]);
        $semNasc = Cliente::create(['company_id' => $this->company->id, 'name' => 'Sem Nasc', 'phone' => '11999990002']);

        $content = $this->actingAs($this->user)
            ->get(route('clientes.exportar.segmento', ['tipo' => 'aniversariantes']))
            ->streamedContent();

        expect($content)->toContain('Com Nasc')
            ->and($content)->not->toContain('Sem Nasc')
            ->and($content)->toContain('Data de Nascimento');
    });

    it('retorna CSV com header correto', function () {
        $response = $this->actingAs($this->user)
            ->get(route('clientes.exportar.segmento'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    });

    it('não expõe clientes de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-seg', 'plano' => 'trial', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Intruso Externo', 'phone' => '11111111111']);
        makeSegAg($this, $cliOutra, 1); // would be "top" if not isolated

        // Create enough appointments in wrong company context manually
        for ($i = 0; $i < 3; $i++) {
            Agendamento::create([
                'company_id' => $outra->id, 'cliente_id' => $cliOutra->id,
                'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
                'data_hora' => now()->subDays(5 + $i), 'duracao' => 30,
                'status' => 'finalizado', 'cancel_token' => Agendamento::generateCancelToken(),
            ]);
        }

        $content = $this->actingAs($this->user)
            ->get(route('clientes.exportar.segmento', ['tipo' => 'top']))
            ->streamedContent();

        expect($content)->not->toContain('Intruso Externo');
    });

    it('unauthenticated é redirecionado', function () {
        $this->get(route('clientes.exportar.segmento'))
            ->assertRedirect(route('login'));
    });
});
