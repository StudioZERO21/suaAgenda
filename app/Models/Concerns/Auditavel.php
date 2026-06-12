<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Auditoria padrão (LGPD): registra criação/alteração/exclusão com
 * somente os atributos alterados, sem dados sensíveis de autenticação.
 */
trait Auditavel
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('modelo')
            ->logFillable()
            ->logOnlyDirty()
            ->logExcept(['password', 'remember_token'])
            ->dontLogEmptyChanges();
    }
}
