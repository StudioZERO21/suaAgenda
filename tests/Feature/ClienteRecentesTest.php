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
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Recentes', 'slug' => 'barbearia-recentes',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('cliente_recentes', function () {
    it('retorna clientes cadastrados nos últimos 30 dias', function () {
        Cliente::create(['company_id' => $this->company->id, 'name' => 'Recente', 'phone' => '11999990001']);
        $antigo = Cliente::create(['company_id' => $this->company->id, 'name' => 'Antigo', 'phone' => '11999990002']);
        $antigo->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.recentes'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['items'][0]['name'])->toBe('Recente');
    });

    it('retorna estrutura correta', function () {
        Cliente::create(['company_id' => $this->company->id, 'name' => 'A', 'phone' => '11999990003']);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.recentes'))
            ->json();

        expect($data)->toHaveKeys(['total', 'dias', 'items']);
        expect($data['items'][0])->toHaveKeys(['id', 'name', 'phone', 'email', 'ativo', 'cadastrado_em']);
    });

    it('respeita parâmetro limite', function () {
        for ($i = 1; $i <= 5; $i++) {
            Cliente::create(['company_id' => $this->company->id, 'name' => "C{$i}", 'phone' => "119999900{$i}0"]);
        }

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.recentes', ['limite' => 3]))
            ->json();

        expect(count($data['items']))->toBe(3);
    });

    it('respeita parâmetro dias', function () {
        Cliente::create(['company_id' => $this->company->id, 'name' => 'Novo', 'phone' => '11999990010']);
        $velho = Cliente::create(['company_id' => $this->company->id, 'name' => 'Velho', 'phone' => '11999990011']);
        $velho->forceFill(['created_at' => now()->subDays(15)])->saveQuietly();

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.recentes', ['dias' => 5]))
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['dias'])->toBe(5);
    });

    it('não retorna clientes de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-recentes', 'plano' => 'trial', 'ativo' => true]);
        Cliente::create(['company_id' => $outra->id, 'name' => 'X', 'phone' => '99999999999']);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.recentes'))
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode ver recentes', function () {
        $this->actingAs($this->analista)
            ->getJson(route('clientes.recentes'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('clientes.recentes'))
            ->assertUnauthorized();
    });
});
