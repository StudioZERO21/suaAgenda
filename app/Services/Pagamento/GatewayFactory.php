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
                self::getMpAccessToken($integrations)
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

    /**
     * Extrai o access_token do MP a partir das configurações de integração.
     * Suporta o formato novo (OAuth criptografado) e o legado (plain text).
     */
    public static function getMpAccessToken(array $integrations): string
    {
        $mp = $integrations['mercadopago'] ?? [];

        if (! empty($mp['access_token_enc'])) {
            try {
                return (string) decrypt($mp['access_token_enc']);
            } catch (\Throwable) {
                // token corrompido — cai no fallback
            }
        }

        // Legado: token plain text gravado pela versão anterior
        return (string) ($mp['access_token'] ?? '');
    }
}
