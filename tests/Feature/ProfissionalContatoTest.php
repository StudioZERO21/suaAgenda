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
        'name' => 'Barbearia Contato', 'slug' => 'barbearia-contato',
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
    ]);
});

describe('profissional_contato', function () {
    it('admin pode atualizar dados de contato', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('profissionais.contato', $this->prof), [
                'phone' => '11999998888',
                'instagram' => '@carlos_barber',
                'tiktok' => '@carlos_tiktok',
                'facebook' => 'carlos.barber',
            ])
            ->assertOk()
            ->assertJsonStructure(['phone', 'instagram', 'tiktok', 'facebook', 'updated_at'])
            ->json();

        expect($data['phone'])->toBe('11999998888');
        expect($data['instagram'])->toBe('@carlos_barber');
        expect($this->prof->fresh()->instagram)->toBe('@carlos_barber');
    });

    it('gestor pode atualizar contato', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('profissionais.contato', $this->prof), ['phone' => '11988887777'])
            ->assertOk();
    });

    it('analista não pode atualizar contato', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('profissionais.contato', $this->prof), ['phone' => '11988887777'])
            ->assertForbidden();
    });

    it('campos são opcionais — envia apenas phone', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('profissionais.contato', $this->prof), ['phone' => '11911112222'])
            ->assertOk()
            ->json();

        expect($data['phone'])->toBe('11911112222');
    });

    it('rejeita phone com mais de 20 caracteres', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.contato', $this->prof), [
                'phone' => str_repeat('1', 21),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone']);
    });

    it('não pode atualizar profissional de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-ct', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->patchJson(route('profissionais.contato', $profOutra), ['phone' => '99999'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('profissionais.contato', $this->prof), ['phone' => '99999'])
            ->assertUnauthorized();
    });
});
