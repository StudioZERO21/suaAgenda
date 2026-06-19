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
     * Verifica se o gateway ativo está pronto para gerar cobranças.
     */
    public static function isReady(array $integrations): bool
    {
        $gateway = $integrations['gateway'] ?? 'nenhum';

        return match ($gateway) {
            'mercadopago' => self::getMpAccessToken($integrations) !== '',
            'asaas'       => trim($integrations['asaas']['api_key'] ?? '') !== '',
            'stripe'      => trim($integrations['stripe']['secret_key'] ?? '') !== '',
            default       => false,
        };
    }

    /**
     * Cria link de pagamento via gateway ativo da empresa.
     *
     * Para Asaas: $payer['customer_id'] é obrigatório.
     * $tipo distingue sinal (vencimento curto) de saldo (7 dias) — apenas Asaas.
     *
     * @param  array<string,mixed>  $integrations  settings['integrations']
     * @param  array{customer_id?: string}  $payer
     * @return array{ok: bool, payment_url?: string, payment_id?: string, erro?: string}
     */
    public static function criarLinkPagamento(
        array $integrations,
        float $valor,
        string $descricao,
        string $referencia,
        array $payer = [],
        string $backUrl = '',
        string $tipo = 'sinal'
    ): array {
        $gateway = $integrations['gateway'] ?? 'nenhum';

        return match ($gateway) {
            'mercadopago' => self::criarLinkMp($integrations, $valor, $descricao, $referencia, $backUrl),
            'asaas'       => self::criarLinkAsaas($integrations, $valor, $descricao, $referencia, $payer, $tipo),
            'stripe'      => self::criarLinkStripe($integrations, $valor, $descricao),
            default       => ['ok' => false, 'payment_url' => '', 'erro' => 'Gateway de pagamento não configurado.'],
        };
    }

    // ── Helpers privados ───────────────────────────────────────────────────────

    private static function criarLinkMp(
        array $integrations,
        float $valor,
        string $descricao,
        string $referencia,
        string $backUrl
    ): array {
        $token = self::getMpAccessToken($integrations);

        if ($token === '') {
            return ['ok' => false, 'payment_url' => '', 'erro' => 'Mercado Pago não conectado.'];
        }

        $url = MercadoPagoService::criarLink($token, $valor, $descricao, $referencia, $backUrl);

        if ($url === '') {
            return ['ok' => false, 'payment_url' => '', 'erro' => 'Falha ao criar preferência no Mercado Pago.'];
        }

        return ['ok' => true, 'payment_url' => $url, 'payment_id' => ''];
    }

    private static function criarLinkAsaas(
        array $integrations,
        float $valor,
        string $descricao,
        string $referencia,
        array $payer,
        string $tipo
    ): array {
        $apiKey = trim($integrations['asaas']['api_key'] ?? '');
        $ambiente = $integrations['asaas']['ambiente'] ?? 'sandbox';
        $customerId = $payer['customer_id'] ?? '';

        if ($apiKey === '') {
            return ['ok' => false, 'payment_url' => '', 'erro' => 'Asaas não configurado.'];
        }

        if ($customerId === '') {
            return ['ok' => false, 'payment_url' => '', 'erro' => 'Cliente não registrado no Asaas.'];
        }

        return $tipo === 'saldo'
            ? AsaasService::criarCobrancaSaldo($apiKey, $ambiente, $customerId, $valor, $descricao, $referencia)
            : AsaasService::criarCobrancaSinal($apiKey, $ambiente, $customerId, $valor, $descricao, $referencia);
    }

    private static function criarLinkStripe(
        array $integrations,
        float $valor,
        string $descricao
    ): array {
        $secretKey = trim($integrations['stripe']['secret_key'] ?? '');

        if ($secretKey === '') {
            return ['ok' => false, 'payment_url' => '', 'erro' => 'Stripe não configurado.'];
        }

        $url = StripeService::criarLink($secretKey, $valor, $descricao);

        if ($url === '') {
            return ['ok' => false, 'payment_url' => '', 'erro' => 'Falha ao criar link de pagamento no Stripe.'];
        }

        return ['ok' => true, 'payment_url' => $url, 'payment_id' => ''];
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

        return (string) ($mp['access_token'] ?? '');
    }
}
