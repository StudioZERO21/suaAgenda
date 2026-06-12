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
        'name' => 'Barbearia Canc', 'slug' => 'barbearia-canc',
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

function makeTaxaAg(array $attrs = []): Agendamento
{
    static $counter = 0;
    $counter++;

    return Agendamento::create(array_merge([
        'company_id' => '',
        'cliente_id' => '',
        'profissional_id' => '',
        'servico_id' => '',
        'data_hora' => now()->subDays(1)->toDateTimeString(),
        'duracao' => 30, 'valor' => 50.0, 'status' => 'pendente',
    ], $attrs));
}

describe('relatorio_taxa_cancelamento', function () {
    it('retorna estrutura correta sem dados', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.taxa-cancelamento'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo', 'total', 'cancelados', 'taxa_cancelamento', 'por_profissional', 'por_servico']);
        expect($data['total'])->toBe(0);
        expect((float) $data['taxa_cancelamento'])->toBe(0.0);
    });

    it('calcula taxa de cancelamento corretamente', function () {
        foreach (['finalizado', 'cancelado', 'cancelado'] as $status) {
            makeTaxaAg([
                'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
                'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
                'status' => $status,
            ]);
        }

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.taxa-cancelamento'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(3);
        expect($data['cancelados'])->toBe(2);
        expect((float) $data['taxa_cancelamento'])->toBe(66.7);
    });

    it('item de por_profissional tem campos esperados', function () {
        makeTaxaAg([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->prof->id, 'servico_id' => $this->servico->id,
            'status' => 'cancelado',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.taxa-cancelamento'))
            ->assertOk()
            ->json();

        expect($data['por_profissional'][0])->toHaveKeys(['id', 'name', 'cor', 'total', 'cancelados', 'taxa']);
        expect($data['por_servico'][0])->toHaveKeys(['id', 'nome', 'cor', 'total', 'cancelados', 'taxa']);
    });

    it('não inclui dados de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-canc', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $servOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'X', 'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'ativo' => true]);

        makeTaxaAg([
            'company_id' => $outra->id, 'cliente_id' => $cliOutra->id,
            'profissional_id' => $profOutra->id, 'servico_id' => $servOutra->id,
            'status' => 'cancelado',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.taxa-cancelamento'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('relatorios.taxa-cancelamento'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('relatorios.taxa-cancelamento'))
            ->assertUnauthorized();
    });
});
