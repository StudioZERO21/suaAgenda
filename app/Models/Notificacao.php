<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacao extends Model
{
    use HasUuids;

    protected $table = 'notificacoes';

    protected $fillable = [
        'company_id',
        'tipo',
        'titulo',
        'mensagem',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public static function colorFor(string $tipo): string
    {
        return match ($tipo) {
            'novo_agendamento' => '#10b981',
            'cancelamento' => '#ef4444',
            'confirmado' => '#059669',
            'em_atendimento' => '#6366f1',
            'aniversario' => '#f59e0b',
            default => '#6b7280',
        };
    }
}
