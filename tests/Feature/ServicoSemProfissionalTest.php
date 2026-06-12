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
        'name' => 'Barbearia SemProf', 'slug' => 'barbearia-semprof',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('servico_sem_profissional', function () {
    it('retorna serviços sem profissional atribuído', function () {
        Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
        Servico::create(['company_id' => $this->company->id, 'nome' => 'Barba', 'duracao_minutos' => 20, 'preco' => 30, 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.sem-profissional'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['total', 'items']);
        expect($data['total'])->toBe(2);
        expect($data['items'][0])->toHaveKeys(['id', 'nome', 'cor', 'duracao_minutos', 'preco', 'ativo']);
    });

    it('exclui serviços que têm profissional', function () {
        $comProf = Servico::create(['company_id' => $this->company->id, 'nome' => 'Com Prof', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
        $semProf = Servico::create(['company_id' => $this->company->id, 'nome' => 'Sem Prof', 'duracao_minutos' => 20, 'preco' => 30, 'ativo' => true]);
        $prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
        $comProf->profissionais()->attach($prof->id);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.sem-profissional'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['items'][0]['nome'])->toBe('Sem Prof');
    });

    it('retorna zero quando todos têm profissional', function () {
        $servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
        $prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Bruno', 'ativo' => true]);
        $servico->profissionais()->attach($prof->id);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.sem-profissional'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('ignora serviços de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-semprof', 'plano' => 'trial', 'ativo' => true]);
        Servico::create(['company_id' => $outra->id, 'nome' => 'Fora', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.sem-profissional'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('servicos.sem-profissional'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('servicos.sem-profissional'))
            ->assertUnauthorized();
    });
});
