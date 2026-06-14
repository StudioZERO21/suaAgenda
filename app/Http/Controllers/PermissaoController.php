<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Permissao\AssignUserGruposRequest;
use App\Http\Requests\Permissao\StoreGrupoAcessoRequest;
use App\Http\Requests\Permissao\UpdateGrupoAcessoRequest;
use App\Models\Cargo;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\User;
use App\Support\SaDemoData;
use App\Support\UserPermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;

class PermissaoController extends Controller
{
    /** Papéis globais que identificam funcionários com acesso ao painel. */
    private const STAFF_ROLES = ['admin_empresa', 'gestor', 'analista'];

    /** Rótulos amigáveis das funções do painel. */
    private const FUNCAO_LABELS = [
        'admin_empresa' => 'Administrador',
        'gestor' => 'Gestor',
        'analista' => 'Analista',
    ];

    public function index(): View
    {
        $companyId = auth()->user()->empresa_id;

        $cargos = Cargo::where('company_id', $companyId)
            ->withCount('profissionais as membros')
            ->orderBy('nome')
            ->get()
            ->map(fn (Cargo $c): array => [
                'id' => $c->id,
                'nome' => $c->nome,
                'nivel' => $c->nivel,
                'cor' => $c->cor,
                'descricao' => $c->descricao,
                'comissao' => (float) ($c->comissao_pct ?? 0),
                'membros' => (int) ($c->membros ?? 0),
            ]);

        $roleGroups = Cargo::where('company_id', $companyId)
            ->whereNotNull('grupo_acesso_id')
            ->pluck('grupo_acesso_id', 'id')
            ->all();

        $users = $this->funcionariosPayload($companyId);

        return view('permissoes.index', [
            'catalogo' => SaDemoData::aclCatalogo(),
            'gruposJson' => $this->gruposPayload($companyId),
            'cargosJson' => $cargos,
            'roleGroupsJson' => (object) $roleGroups,
            'funcionariosJson' => $users,
        ]);
    }

    public function gruposJson(): JsonResponse
    {
        abort_if(! auth()->user()->hasRole(['admin_empresa', 'super_admin']) && ! auth()->user()->can('cfg_perms'), 403);

        return response()->json($this->gruposPayload(auth()->user()->empresa_id));
    }

    public function storeGrupo(StoreGrupoAcessoRequest $request): JsonResponse
    {
        $grupo = Role::create([
            'company_id' => auth()->user()->empresa_id,
            'name' => $request->nome,
            'guard_name' => 'web',
            'cor' => $request->cor ?? '#6366f1',
            'descricao' => $request->descricao,
            'is_system' => false,
        ]);

        $grupo->syncPermissions($request->validated('perms'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json($this->grupoToJson($grupo->fresh('permissions')), 201);
    }

    public function updateGrupo(UpdateGrupoAcessoRequest $request, string $grupo): JsonResponse
    {
        $role = $this->findGrupo($grupo);

        $role->update([
            'name' => $request->nome,
            'cor' => $request->cor ?? $role->cor,
            'descricao' => $request->descricao,
        ]);

        $role->syncPermissions($request->validated('perms'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json($this->grupoToJson($role->fresh('permissions')));
    }

    public function destroyGrupo(string $grupo): JsonResponse
    {
        abort_if(! auth()->user()->hasRole(['admin_empresa', 'super_admin']) && ! auth()->user()->can('cfg_perms'), 403);

        $role = $this->findGrupo($grupo);

        abort_if($role->is_system, 422, 'Grupos padrão não podem ser excluídos.');

        Cargo::where('grupo_acesso_id', $role->id)->update(['grupo_acesso_id' => null]);
        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json(['success' => true]);
    }

    public function assignCargoGrupo(Request $request, Cargo $cargo): JsonResponse
    {
        abort_if(! auth()->user()->hasRole(['admin_empresa', 'super_admin']) && ! auth()->user()->can('cfg_perms'), 403);

        $request->validate(['grupo_id' => ['nullable', 'integer']]);

        $grupoId = $request->input('grupo_id');
        $role = $grupoId ? $this->findGrupo((string) $grupoId) : null;

        $cargo->update(['grupo_acesso_id' => $role?->id]);

        // Resincroniza o grupo dos usuários vinculados a profissionais deste cargo
        User::where('empresa_id', auth()->user()->empresa_id)
            ->whereIn('profissional_id', $cargo->profissionais()->pluck('id'))
            ->get()
            ->each(fn (User $user) => $this->resyncGrupoDoUsuario($user));

        return response()->json(['success' => true, 'grupo_id' => $role?->id]);
    }

    public function usuariosJson(): JsonResponse
    {
        abort_if(! auth()->user()->hasRole(['admin_empresa', 'super_admin', 'gestor']), 403);

        $companyId = auth()->user()->empresa_id;

        $users = User::where('empresa_id', $companyId)
            ->withTrashed(false)
            ->with(['roles', 'profissional:id,name,especialidade,cor'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $u): array => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'ativo' => (bool) $u->ativo,
                'role' => $u->roles->whereNull('company_id')->first()?->name ?? '',
                'grupo' => $u->roles->whereNotNull('company_id')->first()?->name ?? '',
                'profissional_id' => $u->profissional_id ?? '',
                'profissional_nome' => $u->profissional?->name ?? '',
                'criado_em' => $u->created_at->toDateString(),
            ]);

        return response()->json([
            'total' => $users->count(),
            'items' => $users->values(),
        ]);
    }

    public function assignUserRole(Request $request, User $user): JsonResponse
    {
        abort_if(! auth()->user()->hasRole('admin_empresa'), 403);
        abort_if($user->empresa_id !== auth()->user()->empresa_id, 403);
        abort_if($user->id === auth()->id(), 403);

        $request->validate([
            'role' => ['required', 'in:admin_empresa,gestor,analista'],
        ]);

        // Remove apenas papéis globais, preservando grupos de acesso da empresa
        $globais = Role::whereNull('company_id')->pluck('id');
        $user->roles()->detach($globais);
        $user->unsetRelation('roles');
        $user->assignRole($request->role);

        return response()->json(['success' => true, 'role' => $request->role]);
    }

    public function assignUserProfissional(Request $request, User $user): JsonResponse
    {
        abort_if(! auth()->user()->hasRole('admin_empresa'), 403);
        abort_if($user->empresa_id !== auth()->user()->empresa_id, 403);

        $companyId = auth()->user()->empresa_id;

        $request->validate([
            'profissional_id' => ['nullable', 'string'],
        ]);

        $profissionalId = $request->profissional_id ?: null;

        if ($profissionalId !== null) {
            $exists = Profissional::where('id', $profissionalId)
                ->where('company_id', $companyId)
                ->exists();
            abort_if(! $exists, 422);
        }

        $user->update(['profissional_id' => $profissionalId]);

        if (! $user->fresh()->acl_manual) {
            $this->resyncGrupoDoUsuario($user->fresh(['profissional.cargo.grupoAcesso']));
        }

        return response()->json(['success' => true, 'profissional_id' => $profissionalId]);
    }

    /**
     * Atribui um ou mais grupos ACL diretamente ao funcionário.
     */
    public function assignUserGrupos(AssignUserGruposRequest $request, User $user): JsonResponse
    {
        abort_if($user->empresa_id !== auth()->user()->empresa_id, 403);

        if ($user->hasRole('admin_empresa')) {
            return response()->json([
                'message' => 'Administrador da empresa possui acesso total e não usa grupos ACL.',
            ], 422);
        }

        /** @var list<int> $grupoIds */
        $grupoIds = array_values(array_unique(array_map('intval', $request->input('grupo_ids', []))));

        $grupos = Role::where('company_id', auth()->user()->empresa_id)
            ->whereIn('id', $grupoIds)
            ->get();

        if ($grupos->count() !== count($grupoIds)) {
            abort(422, 'Grupo de acesso inválido.');
        }

        $this->syncUserGrupos($user, $grupos->all(), manual: true);

        return response()->json([
            'success' => true,
            'funcionario' => $this->funcionarioToJson($user->fresh(['roles.permissions', 'profissional.cargo.grupoAcesso'])),
        ]);
    }

    /**
     * Restaura grupos ACL conforme o cargo do profissional vinculado.
     */
    public function syncUserGrupoFromCargo(User $user): JsonResponse
    {
        abort_if(! auth()->user()->hasRole(['admin_empresa', 'super_admin']) && ! auth()->user()->can('cfg_perms'), 403);
        abort_if($user->empresa_id !== auth()->user()->empresa_id, 403);

        if ($user->hasRole('admin_empresa')) {
            return response()->json(['message' => 'Administrador não usa grupos ACL.'], 422);
        }

        $user->update(['acl_manual' => false]);
        $this->resyncGrupoDoUsuario($user->fresh(['profissional.cargo.grupoAcesso']));

        return response()->json([
            'success' => true,
            'funcionario' => $this->funcionarioToJson($user->fresh(['roles.permissions', 'profissional.cargo.grupoAcesso'])),
        ]);
    }

    /**
     * Garante grupos ACL pelo cargo do profissional, salvo atribuição manual.
     */
    private function resyncGrupoDoUsuario(User $user): void
    {
        if ($user->acl_manual) {
            return;
        }

        $grupo = $user->profissional?->cargo?->grupoAcesso;

        $this->syncUserGrupos($user, $grupo !== null ? [$grupo] : [], manual: false);
    }

    /**
     * @param  list<Role>  $grupos
     */
    private function syncUserGrupos(User $user, array $grupos, bool $manual): void
    {
        $gruposDaEmpresa = Role::where('company_id', $user->empresa_id)->pluck('id');
        $user->roles()->detach($gruposDaEmpresa);
        $user->unsetRelation('roles');

        foreach ($grupos as $grupo) {
            $user->assignRole($grupo);
        }

        $user->updateQuietly(['acl_manual' => $manual]);
        UserPermissions::forgetUser($user->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function findGrupo(string $id): Role
    {
        return Role::where('company_id', auth()->user()->empresa_id)
            ->where('guard_name', 'web')
            ->findOrFail($id);
    }

    private function gruposPayload(?string $companyId): array
    {
        return Role::where('company_id', $companyId)
            ->with('permissions:id,name')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => $this->grupoToJson($role))
            ->all();
    }

    private function grupoToJson(Role $role): array
    {
        return [
            'id' => $role->id,
            'nome' => $role->name,
            'cor' => $role->cor ?? '#6366f1',
            'descricao' => $role->descricao ?? '',
            'is_system' => (bool) $role->is_system,
            'perms' => $role->permissions->pluck('name')->values()->all(),
        ];
    }

    /**
     * Lista funcionários da empresa (exclui clientes sem função de painel).
     *
     * @return list<array<string, mixed>>
     */
    private function funcionariosPayload(?string $companyId): array
    {
        return User::where('empresa_id', $companyId)
            ->with([
                'roles.permissions',
                'profissional.cargo.grupoAcesso',
            ])
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user): bool => $this->isFuncionario($user))
            ->map(fn (User $user): array => $this->funcionarioToJson($user))
            ->values()
            ->all();
    }

    /**
     * Funcionário = função de painel, vínculo profissional ou grupo ACL da empresa.
     */
    private function isFuncionario(User $user): bool
    {
        if ($user->profissional_id !== null) {
            return true;
        }

        $globalRole = $user->roles->whereNull('company_id')->first()?->name;

        if ($globalRole !== null && in_array($globalRole, self::STAFF_ROLES, true)) {
            return true;
        }

        return $user->roles->whereNotNull('company_id')->isNotEmpty();
    }

    /**
     * Payload read-only para a aba Usuários & Funções.
     *
     * @return array<string, mixed>
     */
    private function funcionarioToJson(User $user): array
    {
        $globalRole = $user->roles->whereNull('company_id')->first();
        $gruposAcl = $user->roles->whereNotNull('company_id')->values();
        $funcaoSlug = $globalRole?->name ?? '';
        $funcaoLabel = self::FUNCAO_LABELS[$funcaoSlug] ?? '';

        $perms = UserPermissions::namesForDisplay($user);

        $grupos = $gruposAcl->map(fn (Role $role): array => [
            'id' => $role->id,
            'nome' => $role->name,
            'cor' => $role->cor ?? '#6366f1',
            'descricao' => $role->descricao ?? '',
        ])->all();

        if ($grupos !== []) {
            $grupo = [
                'nome' => count($grupos) === 1 ? $grupos[0]['nome'] : count($grupos).' grupos',
                'cor' => $grupos[0]['cor'],
                'descricao' => implode(', ', array_column($grupos, 'nome')),
            ];
        } elseif ($funcaoSlug === 'admin_empresa') {
            $grupo = ['nome' => 'Acesso Total', 'cor' => '#ef4444', 'descricao' => 'Função Administrador'];
        } elseif ($funcaoSlug === 'gestor') {
            $grupo = ['nome' => 'Gestão Operacional', 'cor' => '#f59e0b', 'descricao' => 'Função Gestor'];
        } elseif ($funcaoSlug === 'analista') {
            $grupo = ['nome' => 'Analista', 'cor' => '#64748b', 'descricao' => 'Função Analista'];
        } else {
            $grupo = ['nome' => 'Sem grupo ACL', 'cor' => '#64748b', 'descricao' => ''];
        }

        $cargo = $user->profissional?->cargo;
        $cargoGrupo = $cargo?->grupoAcesso;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'ativo' => (bool) $user->ativo,
            'funcao' => $funcaoLabel,
            'funcao_slug' => $funcaoSlug,
            'grupo' => array_merge($grupo, ['perms' => $perms]),
            'grupos' => $grupos,
            'grupo_ids' => array_column($grupos, 'id'),
            'acl_manual' => (bool) $user->acl_manual,
            'cargo_grupo' => $cargoGrupo ? [
                'id' => $cargoGrupo->id,
                'nome' => $cargoGrupo->name,
                'cor' => $cargoGrupo->cor ?? '#6366f1',
            ] : null,
            'cargo' => $cargo ? ['nome' => $cargo->nome, 'cor' => $cargo->cor] : null,
            'profissional' => $user->profissional ? ['nome' => $user->profissional->name] : null,
        ];
    }
}
