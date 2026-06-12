<?php

declare(strict_types=1);

use App\Models\Cliente;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Busca', 'slug' => 'barbearia-busca',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana Silva', 'phone' => '11999990001', 'email' => 'ana@test.com']);
    Cliente::create(['company_id' => $this->company->id, 'name' => 'Bruno Souza', 'phone' => '11999990002', 'email' => 'bruno@test.com']);
    Cliente::create(['company_id' => $this->company->id, 'name' => 'Carlos Nunes', 'phone' => '11999990003']);
});

describe('cliente_buscar', function () {
    it('retorna clientes que batem pelo nome', function () {
        $data = $this->actingAs($this->user)
            ->getJson(route('clientes.buscar', ['q' => 'Ana']))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(1);
        expect($data[0]['name'])->toBe('Ana Silva');
    });

    it('retorna clientes que batem pelo telefone', function () {
        $data = $this->actingAs($this->user)
            ->getJson(route('clientes.buscar', ['q' => '11999990002']))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(1);
        expect($data[0]['name'])->toBe('Bruno Souza');
    });

    it('retorna clientes que batem pelo email', function () {
        $data = $this->actingAs($this->user)
            ->getJson(route('clientes.buscar', ['q' => 'bruno@test']))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(1);
        expect($data[0]['name'])->toBe('Bruno Souza');
    });

    it('retorna lista vazia quando q está vazio', function () {
        $data = $this->actingAs($this->user)
            ->getJson(route('clientes.buscar'))
            ->assertOk()
            ->json();

        expect($data)->toBeEmpty();
    });

    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->user)
            ->getJson(route('clientes.buscar', ['q' => 'Ana']))
            ->assertOk()
            ->json();

        expect($data[0])->toHaveKeys(['id', 'name', 'phone', 'email']);
    });

    it('não expõe clientes de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-busca', 'plano' => 'trial', 'ativo' => true]);
        Cliente::create(['company_id' => $outra->id, 'name' => 'Ana Intrusa', 'phone' => '99999999999']);

        $data = $this->actingAs($this->user)
            ->getJson(route('clientes.buscar', ['q' => 'Ana']))
            ->json();

        expect(collect($data)->pluck('name')->all())->not->toContain('Ana Intrusa');
    });

    it('limita a 15 resultados', function () {
        for ($i = 0; $i < 20; $i++) {
            Cliente::create([
                'company_id' => $this->company->id,
                'name' => "Teste Cliente {$i}",
                'phone' => "119999{$i}".str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            ]);
        }

        $data = $this->actingAs($this->user)
            ->getJson(route('clientes.buscar', ['q' => 'Teste']))
            ->json();

        expect(count($data))->toBeLessThanOrEqual(15);
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('clientes.buscar', ['q' => 'Ana']))
            ->assertUnauthorized();
    });
});
