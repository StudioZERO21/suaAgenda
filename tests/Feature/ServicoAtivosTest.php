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
        'name' => 'Barbearia ServAtivos', 'slug' => 'barbearia-servativos',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('servico_ativos', function () {
    it('retorna apenas serviços ativos', function () {
        Servico::create(['company_id' => $this->company->id, 'nome' => 'Ativo', 'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true]);
        Servico::create(['company_id' => $this->company->id, 'nome' => 'Inativo', 'duracao_minutos' => 20, 'preco' => 30.0, 'cor' => '#222', 'ativo' => false]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.ativos'))
            ->assertOk()
            ->json();

        expect(count($data))->toBe(1);
        expect($data[0]['nome'])->toBe('Ativo');
    });

    it('retorna estrutura correta', function () {
        Servico::create(['company_id' => $this->company->id, 'nome' => 'S', 'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.ativos'))
            ->json();

        expect($data[0])->toHaveKeys(['id', 'nome', 'cor', 'duracao_minutos', 'preco']);
    });

    it('não retorna serviços de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-servativos', 'plano' => 'trial', 'ativo' => true]);
        Servico::create(['company_id' => $outra->id, 'nome' => 'X', 'duracao_minutos' => 30, 'preco' => 10.0, 'cor' => '#000', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.ativos'))
            ->json();

        expect($data)->toBeEmpty();
    });

    it('ordena por nome', function () {
        Servico::create(['company_id' => $this->company->id, 'nome' => 'Zumba', 'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true]);
        Servico::create(['company_id' => $this->company->id, 'nome' => 'Barba', 'duracao_minutos' => 20, 'preco' => 30.0, 'cor' => '#222', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.ativos'))
            ->json();

        expect($data[0]['nome'])->toBe('Barba');
        expect($data[1]['nome'])->toBe('Zumba');
    });

    it('analista pode ver ativos', function () {
        $this->actingAs($this->analista)
            ->getJson(route('servicos.ativos'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('servicos.ativos'))
            ->assertUnauthorized();
    });
});
