<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Isolamento multi-tenant: escopo global por company_id do usuário autenticado.
 *
 * Aplicar SOMENTE em models de back-office (nunca consultados em rotas
 * públicas), pois um usuário logado navegando na vitrine de outra empresa
 * teria as queries públicas filtradas pela empresa errada.
 *
 * - Usuários sem empresa (super_admin) não recebem o escopo.
 * - Rotas públicas/jobs/commands (sem auth) não recebem o escopo.
 * - No creating, company_id é preenchido automaticamente se ausente.
 */
trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder): void {
            $empresaId = auth()->user()?->empresa_id;

            if ($empresaId === null) {
                return;
            }

            $builder->where($builder->getModel()->getTable().'.company_id', $empresaId);
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('company_id') === null) {
                $model->setAttribute('company_id', auth()->user()?->empresa_id);
            }
        });
    }
}
