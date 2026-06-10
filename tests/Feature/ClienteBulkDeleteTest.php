<?php

declare(strict_types=1);

use App\Models\Cliente;
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
        'name' => 'Empresa Bulk',
        'slug' => 'empresa-bulk',
        'plano' => 'trial',
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->c1 = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente A', 'ativo' => true]);
    $this->c2 = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente B', 'ativo' => true]);
    $this->c3 = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente C', 'ativo' => true]);
});

describe('cliente_bulk_delete', function () {
    it('admin pode excluir múltiplos clientes', function () {
        $this->actingAs($this->admin)
            ->deleteJson(route('clientes.bulk-destroy'), ['ids' => [$this->c1->id, $this->c2->id]])
            ->assertOk()
            ->assertJson(['deleted' => 2]);

        expect(Cliente::find($this->c1->id))->toBeNull();
        expect(Cliente::find($this->c2->id))->toBeNull();
        expect(Cliente::find($this->c3->id))->not->toBeNull();
    });

    it('gestor não pode fazer bulk delete', function () {
        $this->actingAs($this->gestor)
            ->deleteJson(route('clientes.bulk-destroy'), ['ids' => [$this->c1->id]])
            ->assertForbidden();
    });

    it('analista não pode fazer bulk delete', function () {
        $this->actingAs($this->analista)
            ->deleteJson(route('clientes.bulk-destroy'), ['ids' => [$this->c1->id]])
            ->assertForbidden();
    });

    it('ids de outra empresa são ignorados (isolamento)', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-bulk', 'plano' => 'trial', 'ativo' => true]);
        $clienteOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Intruso', 'ativo' => true]);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('clientes.bulk-destroy'), ['ids' => [$clienteOutra->id]]);

        $response->assertOk()->assertJson(['deleted' => 0]);
        expect(Cliente::find($clienteOutra->id))->not->toBeNull();
    });

    it('validação rejeita array vazio', function () {
        $this->actingAs($this->admin)
            ->deleteJson(route('clientes.bulk-destroy'), ['ids' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ids']);
    });

    it('unauthenticated é redirecionado', function () {
        $this->deleteJson(route('clientes.bulk-destroy'), ['ids' => [$this->c1->id]])
            ->assertUnauthorized();
    });
});
