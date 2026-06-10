<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Ocup', 'slug' => 'barbearia-ocup',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Marcos', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Luís', 'phone' => '11999990099']);
});

describe('relatorio_ocupacao', function () {
    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.ocupacao'))
            ->assertOk()
            ->json();

        expect($data)->toBeArray();
        expect($data[0])->toHaveKeys([
            'profissional_id', 'profissional_nome', 'cor',
            'total_agendamentos', 'finalizados', 'cancelados',
            'taxa_conclusao', 'receita_total', 'duracao_media_min',
        ]);
    });

    it('conta agendamentos e calcula taxa de conclusão', function () {
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->subDay(),
            'duracao' => 30,
            'valor' => 50.0,
            'status' => 'finalizado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->subDay(),
            'duracao' => 30,
            'valor' => 50.0,
            'status' => 'cancelado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.ocupacao'))
            ->json();

        $row = collect($data)->firstWhere('profissional_id', $this->prof->id);
        expect($row['total_agendamentos'])->toBe(2);
        expect($row['finalizados'])->toBe(1);
        expect($row['cancelados'])->toBe(1);
        expect((float) $row['taxa_conclusao'])->toBe(50.0);
        expect((float) $row['receita_total'])->toBe(50.0);
    });

    it('retorna zeros quando sem agendamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.ocupacao'))
            ->json();

        $row = collect($data)->firstWhere('profissional_id', $this->prof->id);
        expect($row['total_agendamentos'])->toBe(0);
        expect((float) $row['taxa_conclusao'])->toBe(0.0);
        expect((float) $row['receita_total'])->toBe(0.0);
    });

    it('não inclui profissionais de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-ocup', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'Estranho', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.ocupacao'))
            ->json();

        $ids = collect($data)->pluck('profissional_id')->all();
        expect($ids)->not->toContain($profOutra->id);
    });

    it('não inclui profissionais inativos', function () {
        $inativo = Profissional::create(['company_id' => $this->company->id, 'name' => 'Inativo', 'ativo' => false]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.ocupacao'))
            ->json();

        $ids = collect($data)->pluck('profissional_id')->all();
        expect($ids)->not->toContain($inativo->id);
    });

    it('analista pode ver relatório', function () {
        $this->actingAs($this->analista)
            ->getJson(route('relatorios.ocupacao'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('relatorios.ocupacao'))
            ->assertUnauthorized();
    });
});
