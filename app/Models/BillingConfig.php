<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BillingConfig extends Model
{
    use HasUuids;

    protected $fillable = [
        'gateway',
        'credentials',
        'grace_warning_days',
        'grace_suspend_days',
        'grace_cancel_days',
        'notification_channel_billing',
        'notification_channel_cancel',
        'active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'grace_warning_days' => 'integer',
            'grace_suspend_days' => 'integer',
            'grace_cancel_days' => 'integer',
            'active' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'gateway' => 'asaas',
            'grace_warning_days' => 3,
            'grace_suspend_days' => 7,
            'grace_cancel_days' => 30,
            'notification_channel_billing' => 'email',
            'notification_channel_cancel' => 'whatsapp',
            'active' => true,
        ]);
    }
}
