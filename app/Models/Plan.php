<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $primaryKey = 'slug';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'slug', 'nome', 'preco', 'max_profissionais', 'whatsapp_mensal',
        'sms_mensal', 'max_whatsapp_overage', 'features', 'color', 'popular', 'ordem',
    ];

    protected $casts = [
        'preco' => 'decimal:2',
        'features' => 'array',
        'popular' => 'boolean',
        'max_profissionais' => 'integer',
        'whatsapp_mensal' => 'integer',
        'sms_mensal' => 'integer',
        'max_whatsapp_overage' => 'integer',
    ];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'plan_slug', 'slug');
    }

    public function precoFormatado(): string
    {
        return 'R$ '.number_format((float) $this->preco, 2, ',', '.');
    }

    public function ilimitado(string $campo): bool
    {
        return $this->{$campo} === -1;
    }

    public static function ordered(): Collection
    {
        return static::orderBy('ordem')->get();
    }
}
