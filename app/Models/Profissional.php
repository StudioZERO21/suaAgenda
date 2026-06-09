<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Profissional extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'profissionais';

    protected $fillable = ['company_id', 'cargo_id', 'name', 'especialidade', 'comissao_pct', 'ativo', 'cor', 'phone', 'admissao', 'instagram', 'tiktok', 'facebook'];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'comissao_pct' => 'decimal:2',
            'admissao' => 'date',
        ];
    }

    public function comissaoFormatada(): string
    {
        return $this->comissao_pct !== null
            ? number_format((float) $this->comissao_pct, 1, ',', '.').'%'
            : '—';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function servicos(): BelongsToMany
    {
        return $this->belongsToMany(Servico::class, 'profissional_servico');
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioTrabalho::class);
    }

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }
}
