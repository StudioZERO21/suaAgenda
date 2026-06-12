<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * Activity log com company_id para filtragem multi-tenant no portal
 * de auditoria do super_admin.
 */
class Activity extends SpatieActivity
{
    protected static function booted(): void
    {
        static::creating(function (Activity $activity): void {
            if ($activity->company_id !== null) {
                return;
            }

            $activity->company_id = auth()->user()?->empresa_id
                ?? $activity->subject?->company_id
                ?? null;
        });
    }

    public function scopeDaEmpresa(Builder $query, string $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
