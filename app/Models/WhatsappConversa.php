<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WhatsappConversa extends Model
{
    use HasUuids;

    protected $table = 'whatsapp_conversas';

    protected $fillable = [
        'direction',
        'from_number',
        'to_number',
        'body',
        'twilio_sid',
        'status',
        'company_id',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isInbound(): bool
    {
        return $this->direction === 'inbound';
    }

    /** Número do contato externo (quem falou conosco ou para quem enviamos) */
    public function contactNumber(): string
    {
        return $this->direction === 'inbound' ? $this->from_number : $this->to_number;
    }
}
