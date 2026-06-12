<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Catálogo global de regras de negócio mantido pelo super_admin.
 * Cada empresa ativa e configura os parâmetros via CompanyRegra.
 */
class RegraCatalogo extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'regra_catalogo';

    protected $fillable = [
        'codigo',
        'nome',
        'descricao',
        'categoria',
        'params_schema',
        'params_default',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'params_schema' => 'array',
            'params_default' => 'array',
            'ativo' => 'boolean',
        ];
    }

    public function companyRegras(): HasMany
    {
        return $this->hasMany(CompanyRegra::class);
    }
}
