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
        'name' => 'Barbearia SvcProf', 'slug' => 'barbearia-svcprof',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#111', 'ativo' => true]);

    $this->prof1 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->prof2 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Ana', 'ativo' => true]);
    $this->profInativo = Profissional::create(['company_id' => $this->company->id, 'name' => 'Inativo', 'ativo' => false]);

    $this->servico->profissionais()->sync([$this->prof1->id, $this->prof2->id, $this->profInativo->id]);
});

describe('servico_profissionais', function () {
    it('retorna profissionais ativos do serviço', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.profissionais', $this->servico))
            ->assertOk()
            ->json();

        expect(count($data))->toBe(2);
        $nomes = collect($data)->pluck('name')->all();
        expect($nomes)->toContain('Carlos');
        expect($nomes)->toContain('Ana');
    });

    it('não retorna profissionais inativos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.profissionais', $this->servico))
            ->json();

        $nomes = collect($data)->pluck('name')->all();
        expect($nomes)->not->toContain('Inativo');
    });

    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.profissionais', $this->servico))
            ->json();

        expect($data[0])->toHaveKeys(['id', 'name', 'especialidade', 'cor']);
    });

    it('retorna lista vazia quando serviço sem profissionais', function () {
        $svcSemProf = Servico::create(['company_id' => $this->company->id, 'nome' => 'Novo', 'duracao_minutos' => 30, 'preco' => 30.0, 'cor' => '#000', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.profissionais', $svcSemProf))
            ->assertOk()
            ->json();

        expect($data)->toBeEmpty();
    });

    it('não acessa profissionais de serviço de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-svcprof', 'plano' => 'trial', 'ativo' => true]);
        $svcOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'S', 'duracao_minutos' => 30, 'preco' => 30.0, 'cor' => '#000', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->getJson(route('servicos.profissionais', $svcOutra))
            ->assertForbidden();
    });

    it('analista pode listar profissionais', function () {
        $this->actingAs($this->analista)
            ->getJson(route('servicos.profissionais', $this->servico))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('servicos.profissionais', $this->servico))
            ->assertUnauthorized();
    });
});
