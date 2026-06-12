<?php

declare(strict_types=1);

use App\Models\Cargo;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['super_admin', 'admin_empresa', 'gestor', 'analista'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web', 'company_id' => null]);
    }

    $this->empresaA = Company::create(['name' => 'Empresa A', 'slug' => 'empresa-a', 'plano' => 'trial', 'ativo' => true]);
    $this->empresaB = Company::create(['name' => 'Empresa B', 'slug' => 'empresa-b', 'plano' => 'trial', 'ativo' => true]);

    $this->seed(PermissionSeeder::class);

    $this->adminA = User::create([
        'name' => 'Admin A', 'email' => 'admin-a@teste.com',
        'password' => bcrypt('secret123'), 'empresa_id' => $this->empresaA->id, 'ativo' => true,
    ]);
    $this->adminA->assignRole('admin_empresa');
});

afterEach(function () {
    setPermissionsTeamId(null);
});

describe('permission_teams', function () {
    it('cria os grupos de acesso padrão para cada empresa', function () {
        $nomesA = Role::where('company_id', $this->empresaA->id)->pluck('name');
        $nomesB = Role::where('company_id', $this->empresaB->id)->pluck('name');

        foreach (['Acesso Total', 'Gestão Operacional', 'Profissional', 'Recepção', 'Estagiário'] as $nome) {
            expect($nomesA)->toContain($nome)
                ->and($nomesB)->toContain($nome);
        }
    });

    it('permite grupos homônimos em empresas diferentes', function () {
        expect(Role::where('name', 'Recepção')->count())->toBe(2);
    });

    it('admin_empresa tem todas as permissions via papel global', function () {
        setPermissionsTeamId($this->empresaA->id);

        expect($this->adminA->can('cfg_perms'))->toBeTrue()
            ->and($this->adminA->can('fin_view'))->toBeTrue()
            ->and($this->adminA->can('cli_delete'))->toBeTrue();
    });

    it('analista recebe somente o conjunto do grupo Profissional', function () {
        $analista = User::create([
            'name' => 'Analista', 'email' => 'analista@teste.com',
            'password' => bcrypt('secret123'), 'empresa_id' => $this->empresaA->id, 'ativo' => true,
        ]);
        $analista->assignRole('analista');

        setPermissionsTeamId($this->empresaA->id);

        expect($analista->can('cal_own'))->toBeTrue()
            ->and($analista->can('fin_own'))->toBeTrue()
            ->and($analista->can('fin_view'))->toBeFalse()
            ->and($analista->can('cfg_perms'))->toBeFalse();
    });

    it('usuário não herda grupo de outra empresa', function () {
        $grupoB = Role::where('company_id', $this->empresaB->id)->where('name', 'Recepção')->first();

        setPermissionsTeamId($this->empresaB->id);
        $userA = User::create([
            'name' => 'Func A', 'email' => 'func-a@teste.com',
            'password' => bcrypt('secret123'), 'empresa_id' => $this->empresaA->id, 'ativo' => true,
        ]);
        $userA->assignRole($grupoB);

        // No contexto da empresa A, o grupo da empresa B não vale
        setPermissionsTeamId($this->empresaA->id);
        $userA->unsetRelation('roles');

        expect($userA->can('fin_pdv'))->toBeFalse();
    });

    it('super_admin passa por qualquer checagem via Gate::before', function () {
        $super = User::create([
            'name' => 'Super', 'email' => 'super@teste.com',
            'password' => bcrypt('secret123'), 'ativo' => true,
        ]);
        $super->assignRole('super_admin');

        expect($super->can('cfg_perms'))->toBeTrue()
            ->and($super->can('qualquer_coisa_inexistente'))->toBeTrue();
    });

    it('cria, atualiza e exclui grupo via API', function () {
        $resposta = $this->actingAs($this->adminA)
            ->postJson(route('permissoes.grupos.store'), [
                'nome' => 'Sênior',
                'cor' => '#0ea5e9',
                'descricao' => 'Profissionais sêniores',
                'perms' => ['cal_own', 'fin_own'],
            ])
            ->assertCreated()
            ->json();

        expect($resposta['perms'])->toContain('cal_own');

        $this->actingAs($this->adminA)
            ->putJson(route('permissoes.grupos.update', $resposta['id']), [
                'nome' => 'Sênior Plus',
                'cor' => '#0ea5e9',
                'descricao' => 'Atualizado',
                'perms' => ['cal_own'],
            ])
            ->assertOk()
            ->assertJsonPath('nome', 'Sênior Plus');

        $this->actingAs($this->adminA)
            ->deleteJson(route('permissoes.grupos.destroy', $resposta['id']))
            ->assertOk();

        expect(Role::find($resposta['id']))->toBeNull();
    });

    it('não permite excluir grupo padrão (is_system)', function () {
        $grupo = Role::where('company_id', $this->empresaA->id)->where('name', 'Recepção')->first();

        $this->actingAs($this->adminA)
            ->deleteJson(route('permissoes.grupos.destroy', $grupo->id))
            ->assertStatus(422);
    });

    it('não acessa grupo de outra empresa', function () {
        $grupoB = Role::where('company_id', $this->empresaB->id)->where('name', 'Recepção')->first();

        $this->actingAs($this->adminA)
            ->putJson(route('permissoes.grupos.update', $grupoB->id), [
                'nome' => 'Hack',
                'perms' => [],
            ])
            ->assertNotFound();
    });

    it('atribuir grupo ao cargo concede permissões ao usuário vinculado', function () {
        $cargo = Cargo::create(['company_id' => $this->empresaA->id, 'nome' => 'Recepcionista', 'nivel' => 'receptionist']);
        $prof = Profissional::create(['company_id' => $this->empresaA->id, 'name' => 'Rê', 'ativo' => true, 'cargo_id' => $cargo->id]);

        $func = User::create([
            'name' => 'Func', 'email' => 'func@teste.com',
            'password' => bcrypt('secret123'), 'empresa_id' => $this->empresaA->id,
            'profissional_id' => $prof->id, 'ativo' => true,
        ]);

        $grupoRecepcao = Role::where('company_id', $this->empresaA->id)->where('name', 'Recepção')->first();

        $this->actingAs($this->adminA)
            ->patchJson(route('permissoes.cargos.grupo', $cargo), ['grupo_id' => $grupoRecepcao->id])
            ->assertOk();

        setPermissionsTeamId($this->empresaA->id);
        $func->unsetRelation('roles');

        expect($func->can('fin_pdv'))->toBeTrue()
            ->and($func->can('cfg_perms'))->toBeFalse();
    });
});
