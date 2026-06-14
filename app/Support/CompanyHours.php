<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\Carbon;

/**
 * Normaliza horários da empresa (settings.hours) entre formatos legado e atual.
 */
final class CompanyHours
{
    /** @var array<string, string> */
    public const DAY_LABELS = [
        'seg' => 'Segunda',
        'ter' => 'Terça',
        'qua' => 'Quarta',
        'qui' => 'Quinta',
        'sex' => 'Sexta',
        'sab' => 'Sábado',
        'dom' => 'Domingo',
    ];

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        'aberto' => 'Aberto',
        'fechado' => 'Fechado',
        'ferias' => 'Férias',
        'feriado' => 'Feriado',
        'reforma' => 'Reforma',
        'outro' => 'Outro',
    ];

    /** @var list<string> */
    public const TEMPORARY_STATUSES = ['ferias', 'feriado', 'reforma', 'outro'];

    /**
     * @return list<string>
     */
    public static function dayKeys(): array
    {
        return array_keys(self::DAY_LABELS);
    }

    /**
     * Normaliza um dia (formato legado ou associativo).
     *
     * @return array{open: string, close: string, status: string, return_date: string|null}
     */
    public static function normalizeDay(mixed $raw): array
    {
        if (is_array($raw) && isset($raw['status'])) {
            $status = (string) $raw['status'];
            if (! array_key_exists($status, self::STATUS_LABELS)) {
                $status = 'fechado';
            }

            return [
                'open' => (string) ($raw['open'] ?? '08:00'),
                'close' => (string) ($raw['close'] ?? '20:00'),
                'status' => $status,
                'return_date' => self::nullableDate($raw['return_date'] ?? null),
            ];
        }

        if (is_array($raw)) {
            $open = (string) ($raw[0] ?? '08:00');
            $close = (string) ($raw[1] ?? '20:00');
            $active = filter_var($raw[2] ?? false, FILTER_VALIDATE_BOOLEAN);

            return [
                'open' => $open,
                'close' => $close,
                'status' => $active ? 'aberto' : 'fechado',
                'return_date' => null,
            ];
        }

        return [
            'open' => '08:00',
            'close' => '20:00',
            'status' => 'fechado',
            'return_date' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $hours
     * @return array<string, array{open: string, close: string, status: string, return_date: string|null}>
     */
    public static function normalizeAll(array $hours): array
    {
        $normalized = [];

        foreach (self::dayKeys() as $key) {
            $normalized[$key] = self::normalizeDay($hours[$key] ?? null);
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>|null  $input
     * @return array<string, array{open: string, close: string, status: string, return_date: string|null}>
     */
    public static function sanitizeFromRequest(?array $input): array
    {
        $input ??= [];
        $sanitized = [];

        foreach (self::dayKeys() as $key) {
            $day = is_array($input[$key] ?? null) ? $input[$key] : [];
            $status = (string) ($day['status'] ?? 'fechado');

            if (! array_key_exists($status, self::STATUS_LABELS)) {
                $status = 'fechado';
            }

            $returnDate = self::nullableDate($day['return_date'] ?? null);
            if (! in_array($status, self::TEMPORARY_STATUSES, true)) {
                $returnDate = null;
            }

            $sanitized[$key] = [
                'open' => self::normalizeTime((string) ($day['open'] ?? '08:00')),
                'close' => self::normalizeTime((string) ($day['close'] ?? '20:00')),
                'status' => $status,
                'return_date' => $returnDate,
            ];
        }

        return $sanitized;
    }

    /**
     * @param  array<string, mixed>|null  $input
     * @return array{active: bool, status: string, return_date: string|null, note: string}
     */
    public static function sanitizeClosure(?array $input): array
    {
        $input ??= [];
        $status = (string) ($input['status'] ?? 'ferias');

        if (! in_array($status, self::TEMPORARY_STATUSES, true)) {
            $status = 'ferias';
        }

        $active = filter_var($input['active'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $returnDate = self::nullableDate($input['return_date'] ?? null);

        if (! $active) {
            $returnDate = null;
        }

        return [
            'active' => $active,
            'status' => $status,
            'return_date' => $returnDate,
            'note' => trim((string) ($input['note'] ?? '')),
        ];
    }

    /**
     * @return array{active: bool, status: string, return_date: string|null, note: string}
     */
    public static function normalizeClosure(mixed $raw): array
    {
        if (! is_array($raw)) {
            return self::sanitizeClosure(null);
        }

        return self::sanitizeClosure($raw);
    }

    public static function requiresReturnDate(string $status): bool
    {
        return in_array($status, self::TEMPORARY_STATUSES, true);
    }

    /**
     * Mapeia o weekday do PHP (0=dom … 6=sáb) para a chave em settings.hours.
     */
    public static function weekdayKey(int $weekday): string
    {
        return ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sab'][$weekday] ?? 'seg';
    }

    /**
     * Retorna horário de funcionamento da empresa na data, ou null se fechado.
     *
     * @param  array<string, mixed>  $settings
     * @return array{inicio: string, fim: string}|null
     */
    public static function expedienteNaData(array $settings, \DateTimeInterface $date): ?array
    {
        $carbon = $date instanceof Carbon
            ? $date->copy()->startOfDay()
            : Carbon::parse($date)->startOfDay();

        $closure = self::normalizeClosure($settings['closure'] ?? null);

        if ($closure['active']) {
            $retorno = $closure['return_date'];
            if ($retorno === null || $carbon->format('Y-m-d') < $retorno) {
                return null;
            }
        }

        $hours = self::normalizeAll($settings['hours'] ?? []);
        $day = $hours[self::weekdayKey((int) $carbon->format('w'))];

        if ($day['status'] !== 'aberto') {
            return null;
        }

        return [
            'inicio' => $day['open'],
            'fim' => $day['close'],
        ];
    }

    private static function nullableDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private static function normalizeTime(string $time): string
    {
        if (preg_match('/^\d{2}:\d{2}$/', $time) === 1) {
            return $time;
        }

        return '08:00';
    }
}
