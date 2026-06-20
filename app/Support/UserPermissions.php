<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

/**
 * Resolve permissões efetivas do usuário no painel da empresa.
 *
 * Quando o funcionário possui grupo ACL da empresa (via cargo/profissional),
 * apenas as permissions desse grupo contam — o papel global (gestor/analista)
 * define a função administrativa, mas não expande o menu nem o acesso.
 */
final class UserPermissions
{
    /** @var array<string, true>|null */
    private static ?array $catalog = null;

    /** @var array<string, list<string>> */
    private static array $effectiveCache = [];

    public static function forgetUser(string $userId): void
    {
        unset(self::$effectiveCache[$userId]);
    }

    /**
     * @return list<string>
     */
    public static function catalog(): array
    {
        self::$catalog ??= array_fill_keys(array_keys(SaDemoData::permissionsFlat()), true);

        return array_keys(self::$catalog);
    }

    public static function isModulePermission(string $ability): bool
    {
        self::$catalog ??= array_fill_keys(array_keys(SaDemoData::permissionsFlat()), true);

        return isset(self::$catalog[$ability]);
    }

    public static function hasCompanyGrupo(User $user): bool
    {
        $user->loadMissing('roles');

        return $user->roles->contains(fn ($role) => $role->company_id !== null);
    }

    /**
     * @return list<string>
     */
    public static function effectiveNames(User $user): array
    {
        if (isset(self::$effectiveCache[$user->id])) {
            return self::$effectiveCache[$user->id];
        }

        $user->loadMissing('roles.permissions');

        $names = $user->roles
            ->filter(fn ($role) => $role->company_id !== null)
            ->flatMap(fn ($role) => $role->permissions->pluck('name'))
            ->unique()
            ->sort()
            ->values()
            ->all();

        self::$effectiveCache[$user->id] = $names;

        return $names;
    }

    public static function can(User $user, string $permission): bool
    {
        if ($user->hasRole('super_admin') || $user->hasRole('admin_empresa')) {
            return true;
        }

        if (self::hasCompanyGrupo($user)) {
            return in_array($permission, self::effectiveNames($user), true);
        }

        try {
            return $user->hasPermissionTo($permission);
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
            return false;
        }
    }

    /**
     * @param  list<string>|null  $permissions
     */
    public static function canAny(User $user, ?array $permissions): bool
    {
        if ($permissions === null) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (self::can($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lista para exibição na tela de permissões (read-only).
     *
     * @return list<string>
     */
    public static function namesForDisplay(User $user): array
    {
        if (self::hasCompanyGrupo($user)) {
            return self::effectiveNames($user);
        }

        $globalRole = $user->roles->whereNull('company_id')->first()?->name ?? '';

        if ($globalRole === 'admin_empresa') {
            return self::catalog();
        }

        return DefaultRolePermissions::for($globalRole) ?? [];
    }
}
