<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Role;
use App\Support\SaDemoData;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cria os grupos de acesso padrão (roles team-scoped) de uma empresa
 * a partir do catálogo em SaDemoData::gruposAcesso(). Idempotente.
 */
class GrupoAcessoProvisioner
{
    public function provision(Company $company): void
    {
        $this->ensureCatalogoDePermissions();

        foreach (SaDemoData::gruposAcesso() as $grupo) {
            $role = Role::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => $grupo['nome'],
                    'guard_name' => 'web',
                ],
                [
                    'cor' => $grupo['cor'],
                    'descricao' => $grupo['descricao'],
                    'is_system' => true,
                ]
            );

            $role->syncPermissions($grupo['perms']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function ensureCatalogoDePermissions(): void
    {
        $existentes = Permission::where('guard_name', 'web')->pluck('name')->all();
        $faltantes = array_diff(array_keys(SaDemoData::permissionsFlat()), $existentes);

        foreach ($faltantes as $name) {
            Permission::create(['name' => $name, 'guard_name' => 'web']);
        }

        if ($faltantes !== []) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}
