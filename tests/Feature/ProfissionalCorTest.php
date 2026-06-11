<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Profissional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Cor', 'slug' => 'barbearia-cor',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id, 'name' => 'Carlos',
        'cor' => '#1a1a1a', 'ativo' => true,
    ]);
});

describe('profissional_cor', function () {
    it('admin pode atualizar cor do profissional', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('profissionais.cor', $this->profissional), ['cor' => '#d4a574'])
            ->assertOk()
            ->assertJsonStructure(['cor', 'updated_at'])
            ->json();

        expect($data['cor'])->toBe('#d4a574');
        expect($this->profissional->fresh()->cor)->toBe('#d4a574');
    });

    it('gestor pode atualizar cor', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('profissionais.cor', $this->profissional), ['cor' => '#6366f1'])
            ->assertOk();
    });

    it('analista não pode atualizar cor', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('profissionais.cor', $this->profissional), ['cor' => '#6366f1'])
            ->assertForbidden();
    });

    it('rejeita cor com formato inválido', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.cor', $this->profissional), ['cor' => 'red'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cor']);
    });

    it('rejeita cor sem hash', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.cor', $this->profissional), ['cor' => '1a1a1a'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cor']);
    });

    it('não pode atualizar cor de profissional de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-cor', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.cor', $profOutra), ['cor' => '#ff0000'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('profissionais.cor', $this->profissional), ['cor' => '#1a1a1a'])
            ->assertUnauthorized();
    });
});
