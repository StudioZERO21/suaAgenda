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

    it('a matriz envolve cada categoria num tbody (x-for de raiz única)', function () {
        // Regressão: um <template x-for> do Alpine só pode ter UMA raiz. A matriz
        // tinha duas (o <tr> de categoria + o <template> das permissões), o que
        // fazia o Alpine descartar as linhas de permissão (página "incompleta").
        $html = $this->actingAs($this->admin)
            ->get(route('permissoes.index'))
            ->assertOk()
            ->getContent();

        // O template da matriz deve abrir um <tbody> logo em seguida.
        $pos = strpos($html, 'x-for="[cat, perms] in catalogoEntries" :key="cat"');
        expect($pos)->not->toBeFalse();
        $trecho = substr($html, $pos, 200);
        expect($trecho)->toContain('<tbody>');
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

    it('abas verticais usam classes CSS (não :style que apaga border:none)', function () {
        $html = $this->actingAs($this->admin)
            ->get(route('permissoes.index'))
            ->assertOk()
            ->getContent();

        expect($html)->toContain('class="sa-vtab"');
        expect($html)->toContain('sa-vtab--active');
        expect($html)->not->toContain(':style="tabStyle(');
    });

    it('matriz usa template x-for em th/td (Alpine não renderiza x-for direto em células)', function () {
        $html = $this->actingAs($this->admin)
            ->get(route('permissoes.index'))
            ->assertOk()
            ->getContent();

        expect($html)->toContain('<template x-for="role in cargos"');
        expect($html)->toContain('<template x-for="perm in perms"');
        expect($html)->not->toContain('<th x-for="role in cargos"');
        expect($html)->not->toContain('<td x-for="role in cargos"');
    });

    it('grupos ACL usam grid com template oculto (cards não empilham)', function () {
        $html = $this->actingAs($this->admin)
            ->get(route('permissoes.index'))
            ->assertOk()
            ->getContent();

        expect($html)->toContain('class="sa-acl-groups-grid"');
        expect($html)->toContain('class="sa-acl-group-card"');
        expect($html)->toContain('.sa-acl-groups-grid > template { display: none; }');
    });

    it('modal de grupo usa grid CSS com template oculto (perm cards visíveis)', function () {
        $html = $this->actingAs($this->admin)
            ->get(route('permissoes.index'))
            ->assertOk()
            ->getContent();

        expect($html)->toContain('class="sa-group-modal"');
        expect($html)->toContain('class="sa-group-modal__perms-grid"');
        expect($html)->toContain('.sa-group-modal__perms-grid > template { display: none; }');
        expect($html)->toContain('.sa-group-modal__cat-card > template { display: none; }');
    });

    it('cargos e grupos usam lista com template oculto e barra colorida', function () {
        $html = $this->actingAs($this->admin)
            ->get(route('permissoes.index'))
            ->assertOk()
            ->getContent();

        expect($html)->toContain('class="sa-role-assign-list"');
        expect($html)->toContain('class="sa-role-assign-row"');
        expect($html)->toContain('.sa-role-assign-list > template { display: none; }');
        expect($html)->toContain('roleBarStyle(role.id)');
        expect($html)->toContain('class="sa-assign-modal"');
    });

    it('badges de grupo ACL usam pill com borda colorida (template)', function () {
        $html = $this->actingAs($this->admin)
            ->get(route('permissoes.index'))
            ->assertOk()
            ->getContent();

        expect($html)->toContain('class="sa-grupo-badge"');
        expect($html)->toContain('grupoBadgeStyle(');
        expect($html)->toContain('border: 1px solid var(--grupo-badge-color');
    });

    it('aba usuarios permite editar grupos ACL dos funcionarios', function () {
        $cliente = User::factory()->create([
            'empresa_id' => $this->company->id,
            'name' => 'Maria Cliente',
            'email' => 'maria@cliente.test',
        ]);

        $html = $this->actingAs($this->admin)
            ->get(route('permissoes.index'))
            ->assertOk()
            ->getContent();

        expect($html)->toContain('class="sa-funcionario-list"');
        expect($html)->toContain('funcionarios:');
        expect($html)->toContain('openUserGrupos(f)');
        expect($html)->toContain('saveUserGrupos()');
        expect($html)->toContain('syncUserGruposFromCargo()');
        expect($html)->not->toContain('maria@cliente.test');
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
