<?php

declare(strict_types=1);

use App\Models\Cargo;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia CNome', 'slug' => 'barbearia-cnome',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->cargo = Cargo::create([
        'company_id' => $this->company->id,
        'nome' => 'Barbeiro',
        'nivel' => 'analista',
        'cor' => '#6b7280',
    ]);
});

describe('cargo_nome', function () {
    it('admin atualiza nome do cargo', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('cargos.nome', $this->cargo), ['nome' => 'Barbeiro Sênior'])
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['nome', 'updated_at']);
        expect($data['nome'])->toBe('Barbeiro Sênior');
        expect($this->cargo->fresh()->nome)->toBe('Barbeiro Sênior');
    });

    it('gestor atualiza nome do cargo', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('cargos.nome', $this->cargo), ['nome' => 'Assistente'])
            ->assertOk();
    });

    it('nome vazio retorna 422', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('cargos.nome', $this->cargo), ['nome' => ''])
            ->assertUnprocessable();
    });

    it('nome acima de 100 caracteres retorna 422', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('cargos.nome', $this->cargo), ['nome' => str_repeat('A', 101)])
            ->assertUnprocessable();
    });

    it('não pode atualizar cargo de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-cnome', 'plano' => 'trial', 'ativo' => true]);
        $cargoOutra = Cargo::create(['company_id' => $outra->id, 'nome' => 'Outro', 'nivel' => 'analista', 'cor' => '#000000']);

        $this->actingAs($this->admin)
            ->patchJson(route('cargos.nome', $cargoOutra), ['nome' => 'Hack'])
            ->assertNotFound();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('cargos.nome', $this->cargo), ['nome' => 'X'])
            ->assertUnauthorized();
    });
});
