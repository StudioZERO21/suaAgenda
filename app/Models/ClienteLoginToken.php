<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Token de acesso por link mágico do cliente (sem senha).
 * Armazena apenas o hash sha256; uso único e com expiração curta.
 */
class ClienteLoginToken extends Model
{
    use HasUuids;

    protected $fillable = [
        'cliente_id',
        'token_hash',
        'channel',
        'expires_at',
        'used_at',
        'created_ip',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /**
     * Gera um token, persiste o hash e devolve o valor em claro
     * (que vai no link e nunca é armazenado).
     *
     * @return array{token: string, model: self}
     */
    public static function gerar(Cliente $cliente, string $channel, ?string $ip, int $minutos = 15): array
    {
        $token = bin2hex(random_bytes(32));

        $model = self::create([
            'cliente_id' => $cliente->id,
            'token_hash' => hash('sha256', $token),
            'channel' => $channel,
            'expires_at' => now()->addMinutes($minutos),
            'created_ip' => $ip,
        ]);

        return ['token' => $token, 'model' => $model];
    }

    public static function consumir(string $token): ?self
    {
        $registro = self::where('token_hash', hash('sha256', $token))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        $registro?->update(['used_at' => now()]);

        return $registro;
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
