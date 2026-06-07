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
    Role::firstOrCreate(['name' => 'gestor',        'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista',      'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Teste',
        'slug' => 'empresa-teste',
        'plano' => 'trial',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id,
        'name' => 'João da Silva',
        'lgpd_consent' => true,
    ]);
});

describe('clientes', function () {
    it('admin pode listar clientes', function () {
        $this->actingAs($this->admin)
            ->get(route('clientes.index'))
            ->assertOk();
    });

    it('gestor pode listar clientes', function () {
        $this->actingAs($this->gestor)
            ->get(route('clientes.index'))
            ->assertOk();
    });

    it('analista pode listar clientes', function () {
        $this->actingAs($this->analista)
            ->get(route('clientes.index'))
            ->assertOk();
    });

    it('admin pode criar cliente', function () {
        $this->actingAs($this->admin)
            ->post(route('clientes.store'), [
                'name' => 'Maria Teste',
                'phone' => '(11) 98765-4321',
                'lgpd_consent' => '1',
            ])
            ->assertRedirect();

        expect(Cliente::where('name', 'Maria Teste')->where('company_id', $this->company->id)->exists())->toBeTrue();
    });

    it('gestor pode criar cliente', function () {
        $this->actingAs($this->gestor)
            ->post(route('clientes.store'), [
                'name' => 'Ana Gestor',
                'lgpd_consent' => '1',
            ])
            ->assertRedirect();

        expect(Cliente::where('name', 'Ana Gestor')->exists())->toBeTrue();
    });

    it('analista não pode criar cliente', function () {
        $this->actingAs($this->analista)
            ->post(route('clientes.store'), ['name' => 'Proibido'])
            ->assertForbidden();
    });

    it('admin pode editar cliente', function () {
        $this->actingAs($this->admin)
            ->put(route('clientes.update', $this->cliente), [
                'name' => 'João Atualizado',
                'lgpd_consent' => '1',
            ])
            ->assertRedirect(route('clientes.show', $this->cliente));

        expect($this->cliente->fresh()->name)->toBe('João Atualizado');
    });

    it('gestor pode editar cliente da empresa', function () {
        $this->actingAs($this->gestor)
            ->put(route('clientes.update', $this->cliente), [
                'name' => 'João Gestor',
                'lgpd_consent' => '0',
            ])
            ->assertRedirect(route('clientes.show', $this->cliente));
    });

    it('analista não pode editar cliente', function () {
        $this->actingAs($this->analista)
            ->put(route('clientes.update', $this->cliente), ['name' => 'Hack'])
            ->assertForbidden();
    });

    it('apenas admin pode excluir cliente', function () {
        $this->actingAs($this->admin)
            ->delete(route('clientes.destroy', $this->cliente))
            ->assertRedirect(route('clientes.index'));

        expect(Cliente::find($this->cliente->id))->toBeNull();
        expect(Cliente::withTrashed()->find($this->cliente->id))->not->toBeNull();
    });

    it('gestor não pode excluir cliente', function () {
        $this->actingAs($this->gestor)
            ->delete(route('clientes.destroy', $this->cliente))
            ->assertForbidden();
    });

    it('isolamento: não acessa cliente de outra empresa', function () {
        $outraCompany = Company::create([
            'name' => 'Outra',
            'slug' => 'outra',
            'plano' => 'trial',
            'ativo' => true,
        ]);
        $clienteOutro = Cliente::create([
            'company_id' => $outraCompany->id,
            'name' => 'Cliente Alheio',
            'lgpd_consent' => false,
        ]);

        $this->actingAs($this->admin)
            ->get(route('clientes.show', $clienteOutro))
            ->assertForbidden();
    });

    it('busca filtra por nome', function () {
        Cliente::create(['company_id' => $this->company->id, 'name' => 'Zacarias Busca', 'lgpd_consent' => false]);

        $response = $this->actingAs($this->admin)
            ->get(route('clientes.index', ['search' => 'Zacarias']))
            ->assertOk();

        $response->assertSee('Zacarias Busca');
        $response->assertDontSee('João da Silva');
    });
});
