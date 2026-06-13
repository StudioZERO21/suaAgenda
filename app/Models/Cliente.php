<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditavel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Cliente extends Authenticatable
{
    use Auditavel, HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'phone',
        'email',
        'data_nasc',
        'lgpd_consent',
        'lgpd_consent_at',
        'lgpd_consent_ip',
        'anonymized_at',
        'observacao',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'data_nasc' => 'date',
            'lgpd_consent' => 'boolean',
            'lgpd_consent_at' => 'datetime',
            'anonymized_at' => 'datetime',
            'ativo' => 'boolean',
        ];
    }

    public function anonimizado(): bool
    {
        return $this->anonymized_at !== null;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(ClienteFoto::class);
    }

    public function loginTokens(): HasMany
    {
        return $this->hasMany(ClienteLoginToken::class);
    }
}
