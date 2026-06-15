<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\WhatsappLog;

/**
 * Controla a quota mensal de mensagens WhatsApp por plano.
 * Limites definidos no PRD seção 3.3 (Starter=50, Crescimento=200, Profissional=500, Enterprise=∞).
 */
class WhatsAppLimitService
{
    /** Limite de envios por plano/mês */
    private const LIMITS = [
        'starter' => 50,
        'crescimento' => 200,
        'profissional' => 500,
        'enterprise' => PHP_INT_MAX,
    ];

    /** Teto máximo de overage (além do limite bloqueia definitivamente) */
    private const OVERAGE_CAP = [
        'starter' => 300,
        'crescimento' => 800,
        'profissional' => 2000,
        'enterprise' => PHP_INT_MAX,
    ];

    public function quota(string $companyId, ?string $mes = null): array
    {
        $mes ??= now()->format('Y-m');
        $company = Company::with('plan')->findOrFail($companyId);
        $slug = $company->plan?->slug ?? 'starter';

        $limit = self::LIMITS[$slug] ?? self::LIMITS['starter'];
        $cap = self::OVERAGE_CAP[$slug] ?? self::OVERAGE_CAP['starter'];

        $used = WhatsappLog::where('company_id', $companyId)
            ->where('mes_referencia', $mes)
            ->where('status', '!=', 'failed')
            ->count();

        $remaining = max(0, $limit - $used);
        $pct = $limit > 0 ? min(100, (int) round($used / $limit * 100)) : 0;

        return [
            'plano' => $slug,
            'limite' => $limit,
            'teto' => $cap,
            'usado' => $used,
            'restante' => $remaining,
            'pct' => $pct,
            'bloqueado' => $used >= $cap,
            'alerta' => $used >= (int) ($limit * 0.8) && $used < $cap,
            'mes' => $mes,
        ];
    }

    public function podeEnviar(string $companyId, ?string $mes = null): bool
    {
        $q = $this->quota($companyId, $mes);

        return ! $q['bloqueado'];
    }

    public function registrar(
        string $companyId,
        string $toPhone,
        string $message,
        string $status = 'sent',
        ?string $eventType = null,
        ?string $sid = null,
    ): void {
        WhatsappLog::create([
            'company_id' => $companyId,
            'to_phone' => $toPhone,
            'message' => mb_substr($message, 0, 1000),
            'status' => $status,
            'event_type' => $eventType,
            'sid' => $sid,
            'mes_referencia' => now()->format('Y-m'),
        ]);
    }
}
