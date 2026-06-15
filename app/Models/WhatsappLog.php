<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'company_id',
        'to_phone',
        'message',
        'status',
        'event_type',
        'sid',
        'mes_referencia',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
