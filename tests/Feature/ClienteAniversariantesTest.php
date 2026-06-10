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
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Aniv', 'slug' => 'barbearia-aniv',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('cliente_aniversariantes', function () {
    it('retorna aniversariantes de hoje', function () {
        Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana', 'phone' => '11000000001',
            'data_nasc' => now()->format('1990-m-d'), 'ativo' => true]);
        Cliente::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'phone' => '11000000002',
            'data_nasc' => now()->subMonth()->format('1990-m-d'), 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.aniversariantes'))
            ->assertOk()
            ->json();

        expect(count($data))->toBe(1);
        expect($data[0]['name'])->toBe('Ana');
    });

    it('retorna estrutura correta', function () {
        Cliente::create(['company_id' => $this->company->id, 'name' => 'Beto', 'phone' => '11000000003',
            'data_nasc' => now()->format('1985-m-d'), 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.aniversariantes'))
            ->json();

        expect($data[0])->toHaveKeys(['id', 'name', 'phone', 'email', 'data_nasc', 'idade']);
        expect($data[0]['idade'])->toBeInt();
    });

    it('retorna todos do mês quando periodo=mes', function () {
        Cliente::create(['company_id' => $this->company->id, 'name' => 'X', 'phone' => '11000000010',
            'data_nasc' => now()->format('1990-m-01'), 'ativo' => true]);
        Cliente::create(['company_id' => $this->company->id, 'name' => 'Y', 'phone' => '11000000011',
            'data_nasc' => now()->format('1990-m-15'), 'ativo' => true]);
        Cliente::create(['company_id' => $this->company->id, 'name' => 'Z', 'phone' => '11000000012',
            'data_nasc' => now()->subMonth()->format('1990-m-01'), 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.aniversariantes', ['periodo' => 'mes']))
            ->json();

        $nomes = collect($data)->pluck('name')->all();
        expect($nomes)->toContain('X');
        expect($nomes)->toContain('Y');
        expect($nomes)->not->toContain('Z');
    });

    it('não retorna clientes inativos', function () {
        Cliente::create(['company_id' => $this->company->id, 'name' => 'Inativo', 'phone' => '11000000020',
            'data_nasc' => now()->format('1990-m-d'), 'ativo' => false]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.aniversariantes'))
            ->json();

        expect($data)->toBeEmpty();
    });

    it('não retorna clientes de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-aniv', 'plano' => 'trial', 'ativo' => true]);
        Cliente::create(['company_id' => $outra->id, 'name' => 'Dono Outra', 'phone' => '11000000030',
            'data_nasc' => now()->format('1990-m-d'), 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.aniversariantes'))
            ->json();

        expect($data)->toBeEmpty();
    });

    it('analista pode ver aniversariantes', function () {
        $this->actingAs($this->analista)
            ->getJson(route('clientes.aniversariantes'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('clientes.aniversariantes'))
            ->assertUnauthorized();
    });
});
