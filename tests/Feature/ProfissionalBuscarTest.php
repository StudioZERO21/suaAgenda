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

    $this->company = Company::create([
        'name' => 'Barbearia ProfBusca', 'slug' => 'barbearia-profbusca',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    Profissional::create(['company_id' => $this->company->id, 'name' => 'Ana Lima', 'especialidade' => 'Colorista', 'ativo' => true]);
    Profissional::create(['company_id' => $this->company->id, 'name' => 'Bruno Costa', 'phone' => '11999990001', 'ativo' => true]);
    Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos Mello', 'ativo' => false]);
});

describe('profissional_buscar', function () {
    it('retorna profissionais que batem pelo nome', function () {
        $data = $this->actingAs($this->user)
            ->getJson(route('profissionais.buscar', ['q' => 'Ana']))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(1);
        expect($data[0]['name'])->toBe('Ana Lima');
    });

    it('retorna profissionais que batem pela especialidade', function () {
        $data = $this->actingAs($this->user)
            ->getJson(route('profissionais.buscar', ['q' => 'Colorista']))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(1);
        expect($data[0]['name'])->toBe('Ana Lima');
    });

    it('retorna profissionais que batem pelo telefone', function () {
        $data = $this->actingAs($this->user)
            ->getJson(route('profissionais.buscar', ['q' => '11999990001']))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(1);
        expect($data[0]['name'])->toBe('Bruno Costa');
    });

    it('retorna lista vazia quando q está vazio', function () {
        $data = $this->actingAs($this->user)
            ->getJson(route('profissionais.buscar'))
            ->assertOk()
            ->json();

        expect($data)->toBeEmpty();
    });

    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->user)
            ->getJson(route('profissionais.buscar', ['q' => 'Ana']))
            ->assertOk()
            ->json();

        expect($data[0])->toHaveKeys(['id', 'name', 'especialidade', 'phone', 'ativo']);
    });

    it('não expõe profissionais de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-profbusca', 'plano' => 'trial', 'ativo' => true]);
        Profissional::create(['company_id' => $outra->id, 'name' => 'Ana Intrusa', 'ativo' => true]);

        $data = $this->actingAs($this->user)
            ->getJson(route('profissionais.buscar', ['q' => 'Ana']))
            ->json();

        expect(collect($data)->pluck('name')->all())->not->toContain('Ana Intrusa');
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('profissionais.buscar', ['q' => 'Ana']))
            ->assertUnauthorized();
    });
});
