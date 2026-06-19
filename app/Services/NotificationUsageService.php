<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\NotificationUsage;

final class NotificationUsageService
{
    /**
     * Retorna o limite mensal do canal para a empresa.
     * Prioridade: override individual → plano → default.
     * -1 = ilimitado.
     */
    public static function limite(Company $company, string $canal): int
    {
        // Override individual configurado pelo super_admin
        $override = match ($canal) {
            'whatsapp' => $company->notif_limit_whatsapp,
            'sms' => $company->notif_limit_sms,
            'email' => $company->notif_limit_email,
            default => null,
        };

        if ($override !== null) {
            return $override;
        }

        $plano = $company->plano ?? 'default';
        $limites = config("notification_limits.planos.{$plano}", config('notification_limits.planos.default'));

        return (int) ($limites[$canal] ?? 0);
    }

    /**
     * Verifica se a empresa ainda pode enviar no canal neste mês.
     */
    public static function podeEnviar(Company $company, string $canal): bool
    {
        $limite = self::limite($company, $canal);
        if ($limite === -1) {
            return true;
        }

        return NotificationUsage::totalMes($company->id, $canal) < $limite;
    }

    /**
     * Registra um envio (deve ser chamado APÓS o envio bem-sucedido).
     */
    public static function registrar(string $companyId, string $canal): void
    {
        NotificationUsage::incrementar($companyId, $canal);
    }

    /**
     * Retorna status completo do mês atual para a empresa.
     *
     * @return array<string, array{usado: int, limite: int, percentual: float, alerta: bool}>
     */
    public static function statusMes(Company $company): array
    {
        $resumo = NotificationUsage::resumoMes($company->id);
        $alerta = (int) config('notification_limits.alerta_percentual', 80);
        $canais = ['whatsapp', 'sms', 'email'];
        $status = [];

        foreach ($canais as $canal) {
            $usado = (int) ($resumo[$canal] ?? 0);
            $limite = self::limite($company, $canal);
            $pct = $limite > 0 ? round(($usado / $limite) * 100, 1) : 0.0;

            $status[$canal] = [
                'usado' => $usado,
                'limite' => $limite,
                'percentual' => $pct,
                'alerta' => $limite !== -1 && $pct >= $alerta,
                'esgotado' => $limite !== -1 && $usado >= $limite,
            ];
        }

        return $status;
    }
}
