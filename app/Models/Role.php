<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Role do spatie com suporte a teams (company_id) e metadados de UI.
 *
 * - Roles globais (company_id null): papéis do sistema — super_admin,
 *   admin_empresa, gestor, analista.
 * - Roles com company_id: "grupos de acesso" configuráveis pela empresa.
 */
class Role extends SpatieRole
{
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
