<?php

declare(strict_types=1);

use App\Models\BloqueioAgenda;
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
        'name' => 'Barbearia BUpd', 'slug' => 'barbearia-bupd',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);

    $this->bloqueio = BloqueioAgenda::create([
        'company_id' => $this->company->id,
        'profissional_id' => $this->prof->id,
        'data_inicio' => now()->addDay()->toDateString(),
        'data_fim' => now()->addDays(3)->toDateString(),
        'motivo' => 'Férias',
    ]);
});

describe('bloqueio_update', function () {
    it('admin atualiza motivo do bloqueio', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('bloqueios.update', $this->bloqueio), ['motivo' => 'Licença médica'])
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['id', 'data_inicio', 'data_fim', 'motivo']);
        expect($data['motivo'])->toBe('Licença médica');
        expect($this->bloqueio->fresh()->motivo)->toBe('Licença médica');
    });

    it('admin atualiza datas do bloqueio', function () {
        $nova_inicio = now()->addDays(5)->toDateString();
        $nova_fim = now()->addDays(7)->toDateString();

        $data = $this->actingAs($this->admin)
            ->patchJson(route('bloqueios.update', $this->bloqueio), ['data_inicio' => $nova_inicio, 'data_fim' => $nova_fim])
            ->assertOk()
            ->json();

        expect($data['data_inicio'])->toBe($nova_inicio);
        expect($data['data_fim'])->toBe($nova_fim);
    });

    it('data_fim antes de data_inicio retorna 422', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('bloqueios.update', $this->bloqueio), [
                'data_inicio' => now()->addDays(5)->toDateString(),
                'data_fim' => now()->addDays(2)->toDateString(),
            ])
            ->assertUnprocessable();
    });

    it('gestor não pode atualizar bloqueio', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('bloqueios.update', $this->bloqueio), ['motivo' => 'Tentativa'])
            ->assertForbidden();
    });

    it('analista não pode atualizar bloqueio', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('bloqueios.update', $this->bloqueio), ['motivo' => 'Tentativa'])
            ->assertForbidden();
    });

    it('não pode atualizar bloqueio de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-bupd', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $bloqueioOutra = BloqueioAgenda::create([
            'company_id' => $outra->id, 'profissional_id' => $profOutra->id,
            'data_inicio' => now()->addDay()->toDateString(), 'data_fim' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('bloqueios.update', $bloqueioOutra), ['motivo' => 'Hack'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('bloqueios.update', $this->bloqueio), ['motivo' => 'X'])
            ->assertUnauthorized();
    });
});
