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
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Toggle', 'slug' => 'barbearia-toggle',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);
});

describe('servico_toggle', function () {
    it('admin desativa serviço ativo', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('servicos.toggle', $this->servico))
            ->assertOk()
            ->assertJson(['ativo' => false]);

        expect($this->servico->fresh()->ativo)->toBeFalse();
    });

    it('admin reativa serviço inativo', function () {
        $this->servico->update(['ativo' => false]);

        $this->actingAs($this->admin)
            ->patchJson(route('servicos.toggle', $this->servico))
            ->assertOk()
            ->assertJson(['ativo' => true]);

        expect($this->servico->fresh()->ativo)->toBeTrue();
    });

    it('analista não pode fazer toggle', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('servicos.toggle', $this->servico))
            ->assertForbidden();

        expect($this->servico->fresh()->ativo)->toBeTrue();
    });

    it('não pode fazer toggle de serviço de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-toggle', 'plano' => 'trial', 'ativo' => true]);
        $svcOutra = Servico::create([
            'company_id' => $outra->id, 'nome' => 'Svc Outra',
            'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#000', 'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('servicos.toggle', $svcOutra))
            ->assertForbidden();

        expect($svcOutra->fresh()->ativo)->toBeTrue();
    });

    it('unauthenticated é redirecionado', function () {
        $this->patchJson(route('servicos.toggle', $this->servico))
            ->assertUnauthorized();
    });
});
