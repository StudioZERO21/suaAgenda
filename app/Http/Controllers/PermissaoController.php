<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Cargo;
use App\Models\Profissional;
use App\Models\User;
use App\Support\SaDemoData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

        // Map cargo nivel to a default ACL group
        $nivelToGroup = [
            'admin' => 'g-admin',
            'manager' => 'g-mgr',
            'professional' => 'g-prof',
            'receptionist' => 'g-recep',
            'intern' => 'g-intern',
            'operacional' => 'g-prof',
            'gerencial' => 'g-mgr',
            'diretivo' => 'g-admin',
        ];

        $roleGroups = $cargos->mapWithKeys(fn (array $c): array => [
            $c['id'] => $nivelToGroup[strtolower($c['nivel'])] ?? 'g-prof',
        ])->all();

        $users = User::where('empresa_id', $companyId)
            ->with('roles')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u): array => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'ativo' => (bool) $u->ativo,
                'role' => $u->roles->first()?->name ?? '',
                'profissional_id' => $u->profissional_id ?? '',
            ]);

        $profissionais = Profissional::where('company_id', $companyId)
            ->ativo()
            ->orderBy('name')
            ->get(['id', 'name', 'especialidade']);

        return view('permissoes.index', [
            'catalogo' => SaDemoData::aclCatalogo(),
            'gruposJson' => SaDemoData::gruposAcesso(),
            'cargosJson' => $cargos,
            'roleGroupsJson' => $roleGroups,
            'usersJson' => $users,
            'profissionaisJson' => $profissionais,
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

        $user->syncRoles([$request->role]);

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

        return response()->json(['success' => true, 'profissional_id' => $profissionalId]);
    }
}
