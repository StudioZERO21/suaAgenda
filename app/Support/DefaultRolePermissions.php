<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Conjuntos default de permissions dos papéis globais do sistema.
 * Fonte única usada pelo PermissionSeeder e pelo evento created do Role.
 */
final class DefaultRolePermissions
{
    /**
     * @return list<string>|null null quando o papel não tem conjunto default
     */
    public static function for(string $roleName): ?array
    {
        $grupos = collect(SaDemoData::gruposAcesso())->keyBy('id');

        return match ($roleName) {
            // super_admin não precisa de permissions (Gate::before)
            'admin_empresa' => array_keys(SaDemoData::permissionsFlat()),
            // Gestão operacional completa + PDV e agenda própria
            'gestor' => array_values(array_unique(array_merge(
                $grupos['g-mgr']['perms'],
                ['fin_pdv', 'fin_own', 'cal_own'],
            ))),
            // Analista = visualização ampla + operação de agendamentos
            'analista' => [
                'cal_view', 'cal_own', 'cal_create', 'cal_edit', 'cal_move',
                'cli_view', 'cli_history',
                'fin_view', 'fin_own', 'fin_pdv',
                'srv_view', 'prd_view', 'ptf_view', 'stf_view',
            ],
            default => null,
        };
    }
}
