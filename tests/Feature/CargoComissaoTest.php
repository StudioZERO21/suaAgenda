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
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia CargoC', 'slug' => 'barbearia-cc',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cargo = Cargo::create([
        'company_id' => $this->company->id,
        'nome' => 'Barbeiro', 'nivel' => 'junior',
        'cor' => '#6b7280', 'comissao_pct' => 10.0,
    ]);
});

describe('cargo_comissao', function () {
    it('admin pode atualizar comissão do cargo', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('cargos.comissao', $this->cargo), ['comissao' => 15.5])
            ->assertOk()
            ->assertJsonStructure(['comissao', 'updated_at'])
            ->json();

        expect((float) $data['comissao'])->toBe(15.5);
        expect((float) $this->cargo->fresh()->comissao_pct)->toBe(15.5);
    });

    it('gestor não pode atualizar comissão', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('cargos.comissao', $this->cargo), ['comissao' => 20.0])
            ->assertForbidden();
    });

    it('analista não pode atualizar comissão', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('cargos.comissao', $this->cargo), ['comissao' => 20.0])
            ->assertForbidden();
    });

    it('aceita comissão zero', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('cargos.comissao', $this->cargo), ['comissao' => 0])
            ->assertOk()
            ->json();

        expect((float) $data['comissao'])->toBe(0.0);
    });

    it('rejeita comissão maior que 100', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('cargos.comissao', $this->cargo), ['comissao' => 101])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['comissao']);
    });

    it('não pode atualizar cargo de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-cc', 'plano' => 'trial', 'ativo' => true]);
        $cargoOutra = Cargo::create([
            'company_id' => $outra->id, 'nome' => 'X', 'nivel' => 'junior', 'cor' => '#999',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('cargos.comissao', $cargoOutra), ['comissao' => 15.0])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('cargos.comissao', $this->cargo), ['comissao' => 15.0])
            ->assertUnauthorized();
    });
});
