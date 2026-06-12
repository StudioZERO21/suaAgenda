<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Role;
use App\Services\GrupoAcessoProvisioner;
use App\Support\SaDemoData;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Catálogo de permissions (global, fonte: SaDemoData)
        foreach (array_keys(SaDemoData::permissionsFlat()) as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // 2. Papéis globais recebem seus conjuntos
        $grupos = collect(SaDemoData::gruposAcesso())->keyBy('id');

        $sets = [
            'admin_empresa' => array_keys(SaDemoData::permissionsFlat()),
            'gestor' => $grupos['g-mgr']['perms'],
            'analista' => $grupos['g-prof']['perms'],
        ];

        foreach ($sets as $roleName => $perms) {
            $role = Role::whereNull('company_id')
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            $role?->syncPermissions($perms);
        }

        // super_admin não precisa de permissions (Gate::before)

        // 3. Grupos de acesso padrão por empresa existente
        $provisioner = app(GrupoAcessoProvisioner::class);

        Company::query()->each(fn (Company $company) => $provisioner->provision($company));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('✓ Permissions + grupos de acesso provisionados.');
    }
}
