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
        'name' => 'Barbearia CJson', 'slug' => 'barbearia-cjson',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('cargos_json', function () {
    it('retorna array vazio quando sem cargos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('cargos.json'))
            ->assertOk()
            ->json();

        expect($data)->toBeArray()->toHaveCount(0);
    });

    it('lista cargos com estrutura correta', function () {
        Cargo::create(['company_id' => $this->company->id, 'nome' => 'Barbeiro', 'nivel' => 'analista', 'cor' => '#6b7280']);
        Cargo::create(['company_id' => $this->company->id, 'nome' => 'Assistente', 'nivel' => 'analista', 'cor' => '#3b82f6']);

        $data = $this->actingAs($this->admin)
            ->getJson(route('cargos.json'))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(2);
        expect($data[0])->toHaveKeys(['id', 'nome', 'nivel', 'cor', 'descricao', 'comissao', 'membros']);
        expect($data[0]['nome'])->toBe('Assistente');
    });

    it('conta profissionais por cargo', function () {
        $cargo = Cargo::create(['company_id' => $this->company->id, 'nome' => 'Barbeiro', 'nivel' => 'analista', 'cor' => '#000']);
        Profissional::create(['company_id' => $this->company->id, 'cargo_id' => $cargo->id, 'name' => 'Carlos', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('cargos.json'))
            ->assertOk()
            ->json();

        expect($data[0]['membros'])->toBe(1);
    });

    it('não retorna cargos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-cjson', 'plano' => 'trial', 'ativo' => true]);
        Cargo::create(['company_id' => $outra->id, 'nome' => 'Outro', 'nivel' => 'analista', 'cor' => '#000']);
        Cargo::create(['company_id' => $this->company->id, 'nome' => 'Meu', 'nivel' => 'analista', 'cor' => '#000']);

        $data = $this->actingAs($this->admin)
            ->getJson(route('cargos.json'))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(1);
        expect($data[0]['nome'])->toBe('Meu');
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('cargos.json'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('cargos.json'))
            ->assertUnauthorized();
    });
});
