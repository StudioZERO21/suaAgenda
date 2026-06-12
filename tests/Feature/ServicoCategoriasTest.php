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
        'name' => 'Barbearia ServCat', 'slug' => 'barbearia-servcat',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('servico_categorias', function () {
    it('retorna array vazio quando sem categorias', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.categorias'))
            ->assertOk()
            ->json();

        expect($data)->toBeArray()->toHaveCount(0);
    });

    it('lista categorias únicas em ordem alfabética', function () {
        Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao' => 30, 'preco' => 50, 'ativo' => true, 'categoria' => 'Cabelo']);
        Servico::create(['company_id' => $this->company->id, 'nome' => 'Barba', 'duracao' => 20, 'preco' => 35, 'ativo' => true, 'categoria' => 'Barba']);
        Servico::create(['company_id' => $this->company->id, 'nome' => 'Hidratação', 'duracao' => 60, 'preco' => 80, 'ativo' => true, 'categoria' => 'Cabelo']);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.categorias'))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(2);
        expect($data[0])->toBe('Barba');
        expect($data[1])->toBe('Cabelo');
    });

    it('não retorna serviços sem categoria', function () {
        Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao' => 30, 'preco' => 50, 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.categorias'))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(0);
    });

    it('não retorna categorias de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-servcat', 'plano' => 'trial', 'ativo' => true]);
        Servico::create(['company_id' => $outra->id, 'nome' => 'X', 'duracao' => 30, 'preco' => 10, 'ativo' => true, 'categoria' => 'Outra Cat']);
        Servico::create(['company_id' => $this->company->id, 'nome' => 'Y', 'duracao' => 30, 'preco' => 10, 'ativo' => true, 'categoria' => 'Minha Cat']);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.categorias'))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(1);
        expect($data[0])->toBe('Minha Cat');
    });

    it('analista pode listar categorias', function () {
        $this->actingAs($this->analista)
            ->getJson(route('servicos.categorias'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('servicos.categorias'))
            ->assertUnauthorized();
    });
});
