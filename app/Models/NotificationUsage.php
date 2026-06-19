<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NotificationUsage extends Model
{
    use HasUuids;

    protected $table = 'notification_usage';

    protected $fillable = ['company_id', 'ano', 'mes', 'canal', 'total'];

    protected function casts(): array
    {
        return ['total' => 'integer', 'ano' => 'integer', 'mes' => 'integer'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function incrementar(string $companyId, string $canal): void
    {
        $ano = (int) now()->format('Y');
        $mes = (int) now()->format('n');

        self::firstOrCreate(
            ['company_id' => $companyId, 'ano' => $ano, 'mes' => $mes, 'canal' => $canal],
            ['total' => 0]
        );

        self::where('company_id', $companyId)
            ->where('ano', $ano)
            ->where('mes', $mes)
            ->where('canal', $canal)
            ->increment('total');
    }

    public static function totalMes(string $companyId, string $canal, ?int $ano = null, ?int $mes = null): int
    {
        return (int) self::where('company_id', $companyId)
            ->where('ano', $ano ?? (int) now()->format('Y'))
            ->where('mes', $mes ?? (int) now()->format('n'))
            ->where('canal', $canal)
            ->value('total');
    }

    /** @return array<string, int> canal => total */
    public static function resumoMes(string $companyId, ?int $ano = null, ?int $mes = null): array
    {
        return self::where('company_id', $companyId)
            ->where('ano', $ano ?? (int) now()->format('Y'))
            ->where('mes', $mes ?? (int) now()->format('n'))
            ->pluck('total', 'canal')
            ->toArray();
    }
}
