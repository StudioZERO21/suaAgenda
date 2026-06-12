<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditavel;
use App\Support\DefaultRolePermissions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role do spatie com suporte a teams (company_id) e metadados de UI.
 *
 * - Roles globais (company_id null): papéis do sistema — super_admin,
 *   admin_empresa, gestor, analista.
 * - Roles com company_id: "grupos de acesso" configuráveis pela empresa.
 */
class Role extends SpatieRole
{
    use Auditavel;

    protected static function booted(): void
    {
        // Papéis globais do sistema nascem com seu conjunto default de
        // permissions (garante consistência em seeders, registro e testes).
        static::created(function (Role $role): void {
            if ($role->company_id !== null || $role->guard_name !== 'web') {
                return;
            }

            $set = DefaultRolePermissions::for($role->name);

            if ($set === null) {
                return;
            }

            $existentes = Permission::where('guard_name', 'web')->pluck('name')->all();

            foreach (array_diff($set, $existentes) as $name) {
                Permission::create(['name' => $name, 'guard_name' => 'web']);
            }

            $role->syncPermissions($set);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }
}
