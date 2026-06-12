<?php

declare(strict_types=1);

use App\Models\Cargo;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia CR', 'slug' => 'barbearia-cr',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('cargo_resumo', function () {
    it('retorna estrutura correta sem cargos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('cargos.resumo'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['total_cargos', 'items']);
        expect($data['total_cargos'])->toBe(0);
        expect($data['items'])->toBeEmpty();
    });

    it('lista todos os cargos da empresa', function () {
        Cargo::create(['company_id' => $this->company->id, 'nome' => 'Barbeiro CR', 'nivel' => 'junior']);
        Cargo::create(['company_id' => $this->company->id, 'nome' => 'Cabeleireiro CR', 'nivel' => 'senior']);

        $data = $this->actingAs($this->admin)
            ->getJson(route('cargos.resumo'))
            ->assertOk()
            ->json();

        expect($data['total_cargos'])->toBe(2);
        expect($data['items'])->toHaveCount(2);
    });

    it('items têm campos corretos', function () {
        Cargo::create(['company_id' => $this->company->id, 'nome' => 'Barb CR', 'nivel' => 'pleno']);

        $data = $this->actingAs($this->admin)
            ->getJson(route('cargos.resumo'))
            ->assertOk()
            ->json();

        $item = $data['items'][0];
        expect($item)->toHaveKeys(['cargo_id', 'cargo_nome', 'nivel', 'cor', 'comissao_pct', 'total_profissionais', 'ativos', 'inativos', 'receita_mes', 'media_avaliacao']);
    });

    it('conta profissionais por cargo', function () {
        $cargo = Cargo::create(['company_id' => $this->company->id, 'nome' => 'Barb Prof CR', 'nivel' => 'pleno']);
        Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof 1 CR', 'ativo' => true, 'cargo_id' => $cargo->id]);
        Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof 2 CR', 'ativo' => false, 'cargo_id' => $cargo->id]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('cargos.resumo'))
            ->assertOk()
            ->json();

        expect($data['items'][0]['total_profissionais'])->toBe(2);
        expect($data['items'][0]['ativos'])->toBe(1);
        expect($data['items'][0]['inativos'])->toBe(1);
    });

    it('ignora cargos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra CR', 'slug' => 'outra-cr', 'plano' => 'trial', 'ativo' => true]);
        Cargo::create(['company_id' => $outra->id, 'nome' => 'Cargo Outra CR', 'nivel' => 'pleno']);

        $data = $this->actingAs($this->admin)
            ->getJson(route('cargos.resumo'))
            ->assertOk()
            ->json();

        expect($data['total_cargos'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('cargos.resumo'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('cargos.resumo'))
            ->assertUnauthorized();
    });
});
