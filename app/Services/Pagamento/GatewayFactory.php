<?php

declare(strict_types=1);

namespace App\Services\Pagamento;

use InvalidArgumentException;

class GatewayFactory
{
    /**
     * Testa as credenciais do gateway ativo da empresa.
     *
     * @param  array<string,mixed>  $integrations  settings['integrations']
     * @return array{ok: bool, nome?: string, erro?: string}
     */
    public static function testar(array $integrations): array
    {
        $gateway = $integrations['gateway'] ?? 'nenhum';

        return match ($gateway) {
            'mercadopago' => MercadoPagoService::testarCredenciais(
                $integrations['mercadopago']['access_token'] ?? ''
            ),
            'asaas' => AsaasService::testarCredenciais(
                $integrations['asaas']['api_key'] ?? '',
                $integrations['asaas']['ambiente'] ?? 'sandbox'
            ),
            'stripe' => StripeService::testarCredenciais(
                $integrations['stripe']['secret_key'] ?? ''
            ),
            'nenhum' => ['ok' => false, 'erro' => 'Nenhum gateway configurado.'],
            default => throw new InvalidArgumentException("Gateway desconhecido: {$gateway}"),
        };
    }
}
