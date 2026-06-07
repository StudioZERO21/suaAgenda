<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agendamento extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    const STATUS_PENDENTE = 'pendente';

    const STATUS_CONFIRMADO = 'confirmado';

    const STATUS_FINALIZADO = 'finalizado';

    const STATUS_CANCELADO = 'cancelado';

    protected $fillable = [
        'company_id',
        'profissional_id',
        'cliente_id',
        'data_hora',
        'duracao',
        'status',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'data_hora' => 'datetime',
            'duracao' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->whereNotIn('status', [self::STATUS_CANCELADO]);
    }

    public function scopePorData(Builder $query, string $data): Builder
    {
        return $query->whereDate('data_hora', $data);
    }

    public function scopePorProfissional(Builder $query, string $profissionalId): Builder
    {
        return $query->where('profissional_id', $profissionalId);
    }
}
