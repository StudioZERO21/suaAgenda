<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\RegraService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyRegra extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'company_regras';

    protected $fillable = [
        'company_id',
        'regra_catalogo_id',
        'ativo',
        'params',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'params' => 'array',
        ];
    }

    protected static function booted(): void
    {
        $invalidar = function (CompanyRegra $regra): void {
            app(RegraService::class)->invalidar($regra->company_id);
        };

        static::saved($invalidar);
        static::deleted($invalidar);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function regraCatalogo(): BelongsTo
    {
        return $this->belongsTo(RegraCatalogo::class);
    }
}
