<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditavel;
use App\Support\SaPalettes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use Auditavel, HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'plan_slug',
        'plano',
        'whatsapp',
        'segment',
        'email',
        'phone',
        'address',
        'description',
        'instagram',
        'facebook',
        'tiktok',
        'youtube',
        'settings',
        'lgpd_consent',
        'trial_ends_at',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'lgpd_consent' => 'boolean',
            'ativo' => 'boolean',
            'trial_ends_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    /**
     * Retorna as configurações mescladas com os padrões do sistema.
     *
     * @return array<string, mixed>
     */
    public function resolvedSettings(): array
    {
        return array_replace_recursive(
            SaPalettes::defaultCompanySettings(),
            $this->settings ?? [],
        );
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_slug', 'slug');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'empresa_id');
    }

    public function profissionais(): HasMany
    {
        return $this->hasMany(Profissional::class);
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class);
    }

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function emTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }
}
