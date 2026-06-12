<?php

declare(strict_types=1);

use App\Models\Cargo;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia CProf', 'slug' => 'barbearia-cprof',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cargo = Cargo::create([
        'company_id' => $this->company->id,
        'nome' => 'Barbeiro',
        'nivel' => 'analista',
        'cor' => '#6b7280',
    ]);
});

describe('cargo_profissionais', function () {
    it('retorna estrutura correta quando cargo vazio', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('cargos.profissionais', $this->cargo))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['cargo_id', 'cargo_nome', 'total', 'items']);
        expect($data['cargo_id'])->toBe($this->cargo->id);
        expect($data['cargo_nome'])->toBe('Barbeiro');
        expect($data['total'])->toBe(0);
        expect($data['items'])->toBeArray()->toHaveCount(0);
    });

    it('lista profissionais do cargo com estrutura correta', function () {
        Profissional::create([
            'company_id' => $this->company->id,
            'cargo_id' => $this->cargo->id,
            'name' => 'Carlos',
            'ativo' => true,
            'especialidade' => 'Corte masculino',
        ]);
        Profissional::create([
            'company_id' => $this->company->id,
            'cargo_id' => $this->cargo->id,
            'name' => 'Ana',
            'ativo' => false,
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('cargos.profissionais', $this->cargo))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(2);
        expect($data['items'][0])->toHaveKeys(['id', 'name', 'ativo', 'especialidade', 'cor']);
        $nomes = collect($data['items'])->pluck('nome')->all();
        expect($data['items'][0]['name'])->toBe('Ana');
        expect($data['items'][0]['ativo'])->toBeFalse();
    });

    it('não inclui profissionais de outro cargo', function () {
        $outroCargo = Cargo::create(['company_id' => $this->company->id, 'nome' => 'Outro', 'nivel' => 'analista', 'cor' => '#000']);
        Profissional::create(['company_id' => $this->company->id, 'cargo_id' => $outroCargo->id, 'name' => 'Externo', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('cargos.profissionais', $this->cargo))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('cargos.profissionais', $this->cargo))
            ->assertOk();
    });

    it('não pode acessar cargo de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-cprof', 'plano' => 'trial', 'ativo' => true]);
        $cargoOutra = Cargo::create(['company_id' => $outra->id, 'nome' => 'Hack', 'nivel' => 'analista', 'cor' => '#000']);

        $this->actingAs($this->admin)
            ->getJson(route('cargos.profissionais', $cargoOutra))
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('cargos.profissionais', $this->cargo))
            ->assertUnauthorized();
    });
});
