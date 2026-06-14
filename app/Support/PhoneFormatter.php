<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Formatação de telefones brasileiros para exibição e persistência.
 */
final class PhoneFormatter
{
    /**
     * Retorna apenas os dígitos do telefone.
     */
    public static function digits(?string $phone): string
    {
        if ($phone === null || $phone === '') {
            return '';
        }

        return preg_replace('/\D/', '', $phone) ?? '';
    }

    /**
     * Formata telefone para exibição: (11) 99999-0000 ou (11) 9999-0000.
     */
    public static function format(?string $phone): string
    {
        if ($phone === null || trim($phone) === '') {
            return '';
        }

        $digits = self::digits($phone);

        if (str_starts_with($digits, '55') && strlen($digits) > 11) {
            $digits = substr($digits, 2);
        }

        return match (strlen($digits)) {
            11 => sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7)),
            10 => sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6)),
            default => trim($phone),
        };
    }

    /**
     * Normaliza telefone antes de salvar no banco (formato mascarado ou null).
     */
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $formatted = self::format($phone);

        return $formatted !== '' ? $formatted : null;
    }
}
