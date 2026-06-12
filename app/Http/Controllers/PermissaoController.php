<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Permissao\StoreGrupoAcessoRequest;
use App\Http\Requests\Permissao\UpdateGrupoAcessoRequest;
use App\Models\Cargo;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\User;
use App\Support\SaDemoData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;

class PermissaoController extends Controller
{
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

        $users = User::where('empresa_id', $companyId)
            ->with('roles')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u): array => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'ativo' => (bool) $u->ativo,
                'role' => $u->roles->whereNull('company_id')->first()?->name ?? '',
                'profissional_id' => $u->profissional_id ?? '',
            ]);

        $profissionais = Profissional::where('company_id', $companyId)
            ->ativo()
            ->orderBy('name')
            ->get(['id', 'name', 'especialidade']);

        return view('permissoes.index', [
            'catalogo' => SaDemoData::aclCatalogo(),
            'gruposJson' => $this->gruposPayload($companyId),
            'cargosJson' => $cargos,
            'roleGroupsJson' => (object) $roleGroups,
            'usersJson' => $users,
            'profissionaisJson' => $profissionais,
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
        $this->resyncGrupoDoUsuario($user->fresh());

        return response()->json(['success' => true, 'profissional_id' => $profissionalId]);
    }

    /**
     * Garante que o usuário tenha exatamente o grupo de acesso definido
     * pelo cargo do profissional vinculado (ou nenhum).
     */
    private function resyncGrupoDoUsuario(User $user): void
    {
        $gruposDaEmpresa = Role::where('company_id', $user->empresa_id)->pluck('id');
        $user->roles()->detach($gruposDaEmpresa);
        $user->unsetRelation('roles');

        $grupo = $user->profissional?->cargo?->grupoAcesso;

        if ($grupo !== null) {
            $user->assignRole($grupo);
        }

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
}
