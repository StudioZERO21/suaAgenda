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
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia UsrJson', 'slug' => 'barbearia-usrjson',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('permissao_usuarios_json', function () {
    it('retorna lista de usuários da empresa', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('permissoes.usuarios.json'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['total', 'items']);
        expect($data['total'])->toBe(3);
        expect($data['items'][0])->toHaveKeys(['id', 'name', 'email', 'ativo', 'role', 'profissional_id', 'profissional_nome', 'criado_em']);
    });

    it('inclui role do usuario', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('permissoes.usuarios.json'))
            ->assertOk()
            ->json();

        $adminItem = collect($data['items'])->firstWhere('id', $this->admin->id);
        expect($adminItem['role'])->toBe('admin_empresa');

        $gestorItem = collect($data['items'])->firstWhere('id', $this->gestor->id);
        expect($gestorItem['role'])->toBe('gestor');
    });

    it('inclui profissional vinculado quando existe', function () {
        $prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof Vinculado', 'ativo' => true]);
        $this->gestor->update(['profissional_id' => $prof->id]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('permissoes.usuarios.json'))
            ->assertOk()
            ->json();

        $gestorItem = collect($data['items'])->firstWhere('id', $this->gestor->id);
        expect($gestorItem['profissional_id'])->toBe($prof->id);
        expect($gestorItem['profissional_nome'])->toBe('Prof Vinculado');
    });

    it('ignora usuários de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra UJ', 'slug' => 'outra-uj', 'plano' => 'trial', 'ativo' => true]);
        User::factory()->create(['empresa_id' => $outra->id]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('permissoes.usuarios.json'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(3);
    });

    it('gestor pode acessar', function () {
        $this->actingAs($this->gestor)
            ->getJson(route('permissoes.usuarios.json'))
            ->assertOk();
    });

    it('analista é rejeitado (403)', function () {
        $this->actingAs($this->analista)
            ->getJson(route('permissoes.usuarios.json'))
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('permissoes.usuarios.json'))
            ->assertUnauthorized();
    });
});
