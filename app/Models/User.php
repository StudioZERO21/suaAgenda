<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, HasUuids, Notifiable, SoftDeletes;

    protected $fillable = ['name', 'email', 'password', 'empresa_id', 'profissional_id', 'ativo'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ativo' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'empresa_id');
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'profissional_id');
    }

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    /**
     * Override do spatie/teams: pivot com company_id NULL conta como
     * atribuição global (papéis do sistema atribuídos fora de contexto de
     * empresa — seeders, super_admin). Pivots com company_id casam apenas
     * com o contexto de team atual. Cada usuário pertence a uma única
     * empresa, então não há risco de vazamento entre empresas.
     */
    public function roles(): MorphToMany
    {
        $relation = $this->morphToMany(
            config('permission.models.role'),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            app(PermissionRegistrar::class)->pivotRole
        );

        if (! app(PermissionRegistrar::class)->teams) {
            return $relation;
        }

        $teamsKey = app(PermissionRegistrar::class)->teamsKey;
        $teamId = getPermissionsTeamId();
        $rolesTable = config('permission.table_names.roles');

        return $relation
            ->where(function ($q) use ($teamsKey, $teamId): void {
                $q->whereNull("model_has_roles.{$teamsKey}");
                if ($teamId !== null) {
                    $q->orWhere("model_has_roles.{$teamsKey}", $teamId);
                }
            })
            ->where(function ($q) use ($rolesTable, $teamsKey, $teamId): void {
                $q->whereNull("{$rolesTable}.{$teamsKey}");
                if ($teamId !== null) {
                    $q->orWhere("{$rolesTable}.{$teamsKey}", $teamId);
                }
            });
    }
}
