<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BloqueioAgenda extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'bloqueios_agenda';

    protected $fillable = [
        'company_id',
        'profissional_id',
        'data_inicio',
        'data_fim',
        'motivo',
    ];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim' => 'date',
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

    public static function blockedOn(string $profissionalId, string $data): bool
    {
        return self::where('profissional_id', $profissionalId)
            ->whereDate('data_inicio', '<=', $data)
            ->whereDate('data_fim', '>=', $data)
            ->exists();
    }
}
