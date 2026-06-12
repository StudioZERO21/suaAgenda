<?php

declare(strict_types=1);

use App\Models\Cargo;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia CDet', 'slug' => 'barbearia-cdet',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cargo = Cargo::create([
        'company_id' => $this->company->id,
        'nome' => 'Barbeiro',
        'nivel' => 'analista',
        'cor' => '#6b7280',
        'descricao' => 'Profissional de cortes',
        'comissao_pct' => 10.5,
    ]);
});

describe('cargo_detalhe', function () {
    it('retorna estrutura completa do cargo', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('cargos.detalhe', $this->cargo))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['id', 'nome', 'nivel', 'cor', 'descricao', 'comissao', 'membros', 'created_at', 'updated_at']);
        expect($data['id'])->toBe($this->cargo->id);
        expect($data['nome'])->toBe('Barbeiro');
        expect($data['cor'])->toBe('#6b7280');
        expect($data['descricao'])->toBe('Profissional de cortes');
        expect((float) $data['comissao'])->toBe(10.5);
        expect($data['membros'])->toBe(0);
    });

    it('conta profissionais no campo membros', function () {
        Profissional::create(['company_id' => $this->company->id, 'cargo_id' => $this->cargo->id, 'name' => 'Carlos', 'ativo' => true]);
        Profissional::create(['company_id' => $this->company->id, 'cargo_id' => $this->cargo->id, 'name' => 'Ana', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('cargos.detalhe', $this->cargo))
            ->assertOk()
            ->json();

        expect($data['membros'])->toBe(2);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('cargos.detalhe', $this->cargo))
            ->assertOk();
    });

    it('não pode acessar cargo de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-cdet', 'plano' => 'trial', 'ativo' => true]);
        $cargoOutra = Cargo::create(['company_id' => $outra->id, 'nome' => 'Hack', 'nivel' => 'analista', 'cor' => '#000']);

        $this->actingAs($this->admin)
            ->getJson(route('cargos.detalhe', $cargoOutra))
            ->assertNotFound();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('cargos.detalhe', $this->cargo))
            ->assertUnauthorized();
    });
});
