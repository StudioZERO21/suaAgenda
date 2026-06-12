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
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Media Ticket', 'slug' => 'barbearia-mt',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create([
        'company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte', 'preco' => 50.0,
        'duracao_minutos' => 30, 'ativo' => true,
    ]);

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id, 'name' => 'João', 'ativo' => true,
    ]);
});

describe('relatorio_media_ticket', function () {
    it('retorna estrutura correta sem dados', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.media-ticket'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo', 'ticket_medio', 'por_servico', 'por_profissional']);
        expect((float) $data['ticket_medio'])->toBe(0.0);
        expect($data['por_servico'])->toBeEmpty();
        expect($data['por_profissional'])->toBeEmpty();
    });

    it('calcula ticket médio geral corretamente', function () {
        foreach ([60.0, 40.0, 50.0] as $valor) {
            Agendamento::create([
                'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
                'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
                'data_hora' => now()->subDays(1)->toDateTimeString(),
                'duracao' => 30, 'valor' => $valor, 'status' => 'finalizado',
            ]);
        }

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.media-ticket'))
            ->assertOk()
            ->json();

        expect((float) $data['ticket_medio'])->toBe(50.0);
    });

    it('item de por_servico tem campos esperados', function () {
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
            'data_hora' => now()->subDays(1)->toDateTimeString(),
            'duracao' => 30, 'valor' => 50.0, 'status' => 'finalizado',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.media-ticket'))
            ->assertOk()
            ->json();

        expect($data['por_servico'][0])->toHaveKeys(['id', 'nome', 'cor', 'total', 'receita', 'ticket_medio']);
        expect($data['por_profissional'][0])->toHaveKeys(['id', 'name', 'cor', 'total', 'receita', 'ticket_medio']);
    });

    it('ignora agendamentos não finalizados', function () {
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
            'data_hora' => now()->subDays(1)->toDateTimeString(),
            'duracao' => 30, 'valor' => 200.0, 'status' => 'pendente',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.media-ticket'))
            ->assertOk()
            ->json();

        expect((float) $data['ticket_medio'])->toBe(0.0);
    });

    it('não inclui dados de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-mt', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'X', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'ativo' => true]);
        Agendamento::create([
            'company_id' => $outra->id, 'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id, 'servico_id' => $servOutra->id,
            'data_hora' => now()->subDays(1)->toDateTimeString(),
            'duracao' => 30, 'valor' => 500.0, 'status' => 'finalizado',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.media-ticket'))
            ->assertOk()
            ->json();

        expect((float) $data['ticket_medio'])->toBe(0.0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('relatorios.media-ticket'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('relatorios.media-ticket'))
            ->assertUnauthorized();
    });
});
