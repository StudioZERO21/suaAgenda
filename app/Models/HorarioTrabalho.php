<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HorarioTrabalho extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'horarios_trabalho';

    protected $fillable = [
        'empresa_id',
        'profissional_id',
        'dia_semana',
        'hora_inicio',
        'hora_fim',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'dia_semana' => 'integer',
            'ativo' => 'boolean',
        ];
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function nomeDia(int $dia): string
    {
        return ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'][$dia] ?? '?';
    }
}
