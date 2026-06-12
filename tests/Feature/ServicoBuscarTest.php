<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia SvcBuscar', 'slug' => 'barbearia-svcbuscar',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte Simples', 'duracao_minutos' => 30, 'preco' => 35.00, 'cor' => '#111111', 'ativo' => true]);
    Servico::create(['company_id' => $this->company->id, 'nome' => 'Barba', 'duracao_minutos' => 20, 'preco' => 25.00, 'cor' => '#222222', 'ativo' => true]);
    Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte + Barba', 'duracao_minutos' => 50, 'preco' => 55.00, 'cor' => '#333333', 'ativo' => true]);
    Servico::create(['company_id' => $this->company->id, 'nome' => 'Inativo', 'duracao_minutos' => 30, 'preco' => 10.00, 'cor' => '#444444', 'ativo' => false]);
});

describe('servico_buscar', function () {
    it('retorna serviços ativos sem query', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.buscar'))
            ->assertOk()
            ->json();

        expect(count($data))->toBe(3);
    });

    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.buscar', ['q' => 'Barba']))
            ->assertOk()
            ->json();

        expect($data[0])->toHaveKeys(['id', 'nome', 'cor', 'duracao_minutos', 'preco']);
    });

    it('filtra por nome quando q é fornecido', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.buscar', ['q' => 'Corte']))
            ->assertOk()
            ->json();

        expect(count($data))->toBe(2);
        $nomes = collect($data)->pluck('nome')->all();
        expect($nomes)->toContain('Corte Simples');
        expect($nomes)->toContain('Corte + Barba');
    });

    it('não retorna serviços inativos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.buscar', ['q' => 'Inativo']))
            ->assertOk()
            ->json();

        expect($data)->toBeEmpty();
    });

    it('não retorna serviços de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-svcbuscar', 'plano' => 'trial', 'ativo' => true]);
        Servico::create(['company_id' => $outra->id, 'nome' => 'Serviço Outra', 'duracao_minutos' => 30, 'preco' => 30.00, 'cor' => '#000', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.buscar', ['q' => 'Outra']))
            ->assertOk()
            ->json();

        expect($data)->toBeEmpty();
    });

    it('analista pode buscar serviços', function () {
        $this->actingAs($this->analista)
            ->getJson(route('servicos.buscar'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('servicos.buscar'))
            ->assertUnauthorized();
    });
});
