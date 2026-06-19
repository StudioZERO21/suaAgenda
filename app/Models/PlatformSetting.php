<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

final class PlatformSetting extends Model
{
    use HasUuids;

    protected $table = 'platform_settings';

    protected $fillable = ['group', 'key', 'value'];

    protected $casts = [
        'value' => 'encrypted',
    ];

    public static function get(string $group, string $key, mixed $default = null): mixed
    {
        return self::where('group', $group)->where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $group, string $key, ?string $value): void
    {
        self::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => ($value !== '' && $value !== null) ? $value : null]
        );
    }

    /** Retorna todos os pares key=>value de um grupo (já descriptografados). */
    public static function forGroup(string $group): array
    {
        return self::where('group', $group)->pluck('value', 'key')->toArray();
    }

    public static function clearCache(): void
    {
        Cache::forget('platform_settings_all');
    }

    /** Carrega todos os grupos em cache (usado no AppServiceProvider). */
    public static function allCached(): array
    {
        return Cache::remember('platform_settings_all', 3600, function () {
            $result = [];
            foreach (static::all() as $s) {
                $result[$s->group][$s->key] = $s->value;
            }

            return $result;
        });
    }
}
