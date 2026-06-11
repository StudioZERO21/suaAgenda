<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Preco', 'slug' => 'barbearia-preco',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'preco' => 50.0, 'duracao_minutos' => 30, 'ativo' => true,
    ]);
});

describe('servico_preco', function () {
    it('admin pode atualizar preço do serviço', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('servicos.preco', $this->servico), ['preco' => 65.0])
            ->assertOk()
            ->assertJsonStructure(['preco', 'updated_at'])
            ->json();

        expect((float) $data['preco'])->toBe(65.0);
        expect((float) $this->servico->fresh()->preco)->toBe(65.0);
    });

    it('gestor pode atualizar preço', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('servicos.preco', $this->servico), ['preco' => 45.0])
            ->assertOk();
    });

    it('analista não pode atualizar preço', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('servicos.preco', $this->servico), ['preco' => 45.0])
            ->assertForbidden();
    });

    it('aceita preço zero', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('servicos.preco', $this->servico), ['preco' => 0])
            ->assertOk()
            ->json();

        expect((float) $data['preco'])->toBe(0.0);
    });

    it('rejeita preço negativo', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('servicos.preco', $this->servico), ['preco' => -5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['preco']);
    });

    it('não pode atualizar serviço de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-preco', 'plano' => 'trial', 'ativo' => true]);
        $servOutra = Servico::create([
            'company_id' => $outra->id, 'nome' => 'X', 'preco' => 30.0, 'duracao_minutos' => 30, 'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('servicos.preco', $servOutra), ['preco' => 99.0])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('servicos.preco', $this->servico), ['preco' => 50.0])
            ->assertUnauthorized();
    });
});
