<?php

declare(strict_types=1);

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
        'name' => 'Barbearia ProfSvc', 'slug' => 'barbearia-profsvc',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);

    $this->svc1 = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#111', 'ativo' => true]);
    $this->svc2 = Servico::create(['company_id' => $this->company->id, 'nome' => 'Barba', 'duracao_minutos' => 20, 'preco' => 30.00, 'cor' => '#222', 'ativo' => true]);
    $this->svcInativo = Servico::create(['company_id' => $this->company->id, 'nome' => 'Inativo', 'duracao_minutos' => 30, 'preco' => 10.00, 'cor' => '#333', 'ativo' => false]);

    $this->prof->servicos()->sync([$this->svc1->id, $this->svc2->id, $this->svcInativo->id]);
});

describe('profissional_servicos', function () {
    it('retorna serviços ativos do profissional', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.servicos', $this->prof))
            ->assertOk()
            ->json();

        expect(count($data))->toBe(2);
        $nomes = collect($data)->pluck('nome')->all();
        expect($nomes)->toContain('Corte');
        expect($nomes)->toContain('Barba');
    });

    it('não retorna serviços inativos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.servicos', $this->prof))
            ->json();

        $nomes = collect($data)->pluck('nome')->all();
        expect($nomes)->not->toContain('Inativo');
    });

    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.servicos', $this->prof))
            ->json();

        expect($data[0])->toHaveKeys(['id', 'nome', 'cor', 'duracao_minutos', 'preco']);
    });

    it('retorna lista vazia quando profissional sem serviços', function () {
        $profSemSvc = Profissional::create(['company_id' => $this->company->id, 'name' => 'Novo', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.servicos', $profSemSvc))
            ->assertOk()
            ->json();

        expect($data)->toBeEmpty();
    });

    it('não acessa serviços de profissional de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-profsvc', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->getJson(route('profissionais.servicos', $profOutra))
            ->assertForbidden();
    });

    it('analista pode listar serviços', function () {
        $this->actingAs($this->analista)
            ->getJson(route('profissionais.servicos', $this->prof))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('profissionais.servicos', $this->prof))
            ->assertUnauthorized();
    });
});
