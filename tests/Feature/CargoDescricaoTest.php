<?php

declare(strict_types=1);

use App\Models\Cargo;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia CDesc', 'slug' => 'barbearia-cdesc',
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

describe('cargo_descricao', function () {
    it('admin atualiza descrição do cargo', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('cargos.descricao', $this->cargo), ['descricao' => 'Profissional de cortes e barba'])
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['descricao', 'updated_at']);
        expect($data['descricao'])->toBe('Profissional de cortes e barba');
        expect($this->cargo->fresh()->descricao)->toBe('Profissional de cortes e barba');
    });

    it('gestor atualiza descrição do cargo', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('cargos.descricao', $this->cargo), ['descricao' => 'Nova descrição'])
            ->assertOk();
    });

    it('descrição nula limpa o campo', function () {
        $this->cargo->update(['descricao' => 'Texto anterior']);

        $data = $this->actingAs($this->admin)
            ->patchJson(route('cargos.descricao', $this->cargo), ['descricao' => null])
            ->assertOk()
            ->json();

        expect($data['descricao'])->toBe('');
        expect($this->cargo->fresh()->descricao)->toBeNull();
    });

    it('descrição acima de 500 caracteres retorna 422', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('cargos.descricao', $this->cargo), ['descricao' => str_repeat('A', 501)])
            ->assertUnprocessable();
    });

    it('não pode atualizar cargo de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-cdesc', 'plano' => 'trial', 'ativo' => true]);
        $cargoOutra = Cargo::create(['company_id' => $outra->id, 'nome' => 'Outro', 'nivel' => 'analista', 'cor' => '#000000']);

        $this->actingAs($this->admin)
            ->patchJson(route('cargos.descricao', $cargoOutra), ['descricao' => 'Hack'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('cargos.descricao', $this->cargo), ['descricao' => 'X'])
            ->assertUnauthorized();
    });
});
