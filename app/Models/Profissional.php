<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditavel;
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
    use Auditavel, HasFactory, HasUuids, SoftDeletes;

    protected $table = 'profissionais';

    protected $fillable = ['company_id', 'cargo_id', 'name', 'email', 'especialidade', 'especialidades', 'comissao_pct', 'ativo', 'status', 'cor', 'phone', 'admissao', 'instagram', 'tiktok', 'facebook', 'foto_path'];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'comissao_pct' => 'decimal:2',
            'admissao' => 'date',
            'especialidades' => 'array',
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

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
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
