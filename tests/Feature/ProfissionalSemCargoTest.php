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
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia SemCargo', 'slug' => 'barbearia-semcargo',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cargo = Cargo::create([
        'company_id' => $this->company->id,
        'nome' => 'Barbeiro Senior',
        'nivel' => 'Senior',
        'cor' => '#222',
    ]);
});

describe('profissionais_sem_cargo', function () {
    it('retorna lista vazia quando todos têm cargo', function () {
        Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true, 'cargo_id' => $this->cargo->id]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.sem-cargo'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
        expect($data['items'])->toHaveCount(0);
    });

    it('retorna profissionais sem cargo', function () {
        Profissional::create(['company_id' => $this->company->id, 'name' => 'Ana', 'ativo' => true]);
        Profissional::create(['company_id' => $this->company->id, 'name' => 'Bob', 'ativo' => true, 'cargo_id' => $this->cargo->id]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.sem-cargo'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['items'][0]['name'])->toBe('Ana');
        expect($data['items'][0])->toHaveKeys(['id', 'name', 'ativo', 'especialidade', 'cor']);
    });

    it('retorna profissionais em ordem alfabética', function () {
        Profissional::create(['company_id' => $this->company->id, 'name' => 'Zeca', 'ativo' => true]);
        Profissional::create(['company_id' => $this->company->id, 'name' => 'Ana', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.sem-cargo'))
            ->assertOk()
            ->json();

        expect($data['items'][0]['name'])->toBe('Ana');
        expect($data['items'][1]['name'])->toBe('Zeca');
    });

    it('não retorna profissionais de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-semcargo', 'plano' => 'trial', 'ativo' => true]);
        Profissional::create(['company_id' => $outra->id, 'name' => 'Outro', 'ativo' => true]);
        Profissional::create(['company_id' => $this->company->id, 'name' => 'Meu', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.sem-cargo'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['items'][0]['name'])->toBe('Meu');
    });

    it('analista pode listar profissionais sem cargo', function () {
        $this->actingAs($this->analista)
            ->getJson(route('profissionais.sem-cargo'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('profissionais.sem-cargo'))
            ->assertUnauthorized();
    });
});
