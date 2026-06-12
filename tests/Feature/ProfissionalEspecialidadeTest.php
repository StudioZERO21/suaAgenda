<?php

declare(strict_types=1);

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
        'name' => 'Barbearia Espec', 'slug' => 'barbearia-espec',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create([
        'company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true,
        'especialidade' => 'Barbeiro',
    ]);
});

describe('profissional_especialidade', function () {
    it('admin pode atualizar especialidade', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('profissionais.especialidade', $this->prof), ['especialidade' => 'Cabeleireiro'])
            ->assertOk()
            ->assertJsonStructure(['especialidade', 'updated_at'])
            ->json();

        expect($data['especialidade'])->toBe('Cabeleireiro');
        expect($this->prof->fresh()->especialidade)->toBe('Cabeleireiro');
    });

    it('gestor pode atualizar especialidade', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('profissionais.especialidade', $this->prof), ['especialidade' => 'Manicure'])
            ->assertOk();
    });

    it('analista não pode atualizar especialidade', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('profissionais.especialidade', $this->prof), ['especialidade' => 'X'])
            ->assertForbidden();
    });

    it('aceita especialidade nula para limpar campo', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('profissionais.especialidade', $this->prof), ['especialidade' => null])
            ->assertOk()
            ->json();

        expect($data['especialidade'])->toBe('');
    });

    it('rejeita especialidade maior que 100 caracteres', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.especialidade', $this->prof), [
                'especialidade' => str_repeat('x', 101),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['especialidade']);
    });

    it('não pode atualizar profissional de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-esp', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.especialidade', $profOutra), ['especialidade' => 'hack'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('profissionais.especialidade', $this->prof), ['especialidade' => 'x'])
            ->assertUnauthorized();
    });
});
