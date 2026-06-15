<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkVisit extends Model
{
    use HasUuids;

    public const TYPE_VIEW = 'view';

    public const TYPE_BOOKING = 'booking';

    protected $fillable = ['company_id', 'type'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Registra uma visita ou conversão de forma não-bloqueante.
     * Falhas silenciosas para não impactar o fluxo do usuário.
     */
    public static function track(string $companyId, string $type = self::TYPE_VIEW): void
    {
        try {
            static::create(['company_id' => $companyId, 'type' => $type]);
        } catch (\Throwable) {
            // tracking nunca deve quebrar o fluxo principal
        }
    }
}
