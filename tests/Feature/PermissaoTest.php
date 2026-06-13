<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Perm',
        'slug' => 'empresa-perm',
        'plano' => 'trial',
        'whatsapp' => '(11) 99999-0003',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->company2 = Company::create([
        'name' => 'Outra Empresa',
        'slug' => 'outra-empresa-p',
        'plano' => 'trial',
        'whatsapp' => '(11) 99999-0004',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->userOutro = User::factory()->create(['empresa_id' => $this->company2->id]);
    $this->userOutro->assignRole('gestor');
});

describe('permissoes index', function () {
    it('admin pode acessar permissões', function () {
        $this->actingAs($this->admin)
            ->get(route('permissoes.index'))
            ->assertOk();
    });

    it('usa o overlay de modal centralizado por classe (não display inline)', function () {
        // Garante que os modais não voltem ao padrão quebrado: x-show + display:flex
        // inline (o x-show do Alpine remove o display inline e descentraliza).
        $html = $this->actingAs($this->admin)
            ->get(route('permissoes.index'))
            ->assertOk()
            ->getContent();

        // Os dois modais (grupo e atribuição) usam a classe.
        expect(substr_count($html, 'sa-modal-overlay'))->toBeGreaterThanOrEqual(2);
    });

    it('gestor não pode acessar permissões (sem cfg_perms)', function () {
        $this->actingAs($this->gestor)
            ->get(route('permissoes.index'))
            ->assertForbidden();
    });

    it('analista não pode acessar permissões (sem cfg_perms)', function () {
        $this->actingAs($this->analista)
            ->get(route('permissoes.index'))
            ->assertForbidden();
    });
});

describe('atribuição de função a usuário', function () {
    it('admin pode alterar função de outro usuário da empresa', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('permissoes.users.role', $this->gestor), ['role' => 'analista'])
            ->assertOk()
            ->assertJson(['success' => true, 'role' => 'analista']);

        expect($this->gestor->fresh()->hasRole('analista'))->toBeTrue();
        expect($this->gestor->fresh()->hasRole('gestor'))->toBeFalse();
    });

    it('admin não pode alterar própria função', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('permissoes.users.role', $this->admin), ['role' => 'analista'])
            ->assertForbidden();
    });

    it('não pode alterar função de usuário de outra empresa', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('permissoes.users.role', $this->userOutro), ['role' => 'analista'])
            ->assertForbidden();
    });

    it('rejeita função inválida', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('permissoes.users.role', $this->gestor), ['role' => 'super_admin'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    });

    it('gestor não pode alterar funções', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('permissoes.users.role', $this->analista), ['role' => 'gestor'])
            ->assertForbidden();
    });
});
