<?php

declare(strict_types=1);

namespace App\Services\Pagamento;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class MercadoPagoService
{
    private const BASE = 'https://api.mercadopago.com';

    private const AUTH = 'https://auth.mercadopago.com.br';

    // ── OAuth Connect ──────────────────────────────────────────────────────────

    /**
     * URL de autorização OAuth (redirecionar o usuário para cá).
     */
    public static function getAuthUrl(string $state): string
    {
        return self::AUTH.'/authorization?'.http_build_query([
            'client_id' => config('services.mercadopago.client_id'),
            'response_type' => 'code',
            'platform_id' => 'mp',
            'redirect_uri' => config('services.mercadopago.redirect_uri'),
            'state' => $state,
        ]);
    }

    /**
     * Troca o authorization code por access_token + refresh_token.
     *
     * @return array{access_token: string, refresh_token?: string, user_id?: int, ...}
     *
     * @throws RuntimeException
     */
    public static function exchangeCode(string $code): array
    {
        $resp = Http::timeout(15)->post(self::BASE.'/oauth/token', [
            'client_id' => config('services.mercadopago.client_id'),
            'client_secret' => config('services.mercadopago.client_secret'),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => config('services.mercadopago.redirect_uri'),
        ]);

        if (! $resp->successful()) {
            throw new RuntimeException('Falha no OAuth MP: '.$resp->body());
        }

        return $resp->json();
    }

    /**
     * Info da conta MP (nome, e-mail).
     *
     * @return array<string, mixed>
     */
    public static function getAccountInfo(string $accessToken): array
    {
        $resp = Http::timeout(8)->withToken($accessToken)->get(self::BASE.'/v1/users/me');

        return $resp->successful() ? ($resp->json() ?? []) : [];
    }

    /**
     * Saldo disponível na conta MP.
     */
    public static function getBalance(string $accessToken): ?float
    {
        $resp = Http::timeout(8)->withToken($accessToken)->get(self::BASE.'/v1/account/balance');

        if (! $resp->successful()) {
            return null;
        }

        return (float) ($resp->json('available_balance') ?? 0);
    }

    /**
     * Total aprovado no mês corrente (pagamentos com status = approved).
     */
    public static function getMonthRevenue(string $accessToken): float
    {
        $begin = now()->startOfMonth()->format('Y-m-d\TH:i:s.000-03:00');
        $end = now()->endOfMonth()->format('Y-m-d\TH:i:s.999-03:00');

        $resp = Http::timeout(10)->withToken($accessToken)->get(self::BASE.'/v1/payments/search', [
            'sort' => 'date_created',
            'criteria' => 'desc',
            'range' => 'date_created',
            'begin_date' => $begin,
            'end_date' => $end,
            'status' => 'approved',
        ]);

        if (! $resp->successful()) {
            return 0.0;
        }

        $results = $resp->json('results') ?? [];

        return (float) array_sum(array_column($results, 'transaction_amount'));
    }

    // ── Helpers de validação / geração de link ──────────────────────────────────

    /**
     * Testa se o access_token é válido.
     *
     * @return array{ok: bool, nome?: string, erro?: string}
     */
    public static function testarCredenciais(string $accessToken): array
    {
        if (trim($accessToken) === '') {
            return ['ok' => false, 'erro' => 'Conta Mercado Pago não conectada.'];
        }

        try {
            $resp = Http::timeout(8)->withToken($accessToken)->get(self::BASE.'/v1/payment_methods');

            if ($resp->successful()) {
                return ['ok' => true, 'nome' => 'Conta Mercado Pago ativa'];
            }

            return ['ok' => false, 'erro' => 'Token inválido (HTTP '.$resp->status().')'];
        } catch (\Exception $e) {
            return ['ok' => false, 'erro' => $e->getMessage()];
        }
    }

    /**
     * Cria preferência de pagamento e retorna o link de checkout.
     */
    public static function criarLink(
        string $accessToken,
        float $valor,
        string $descricao,
        string $referencia = ''
    ): string {
        $resp = Http::timeout(15)
            ->withToken($accessToken)
            ->post(self::BASE.'/checkout/preferences', [
                'items' => [[
                    'title' => $descricao,
                    'quantity' => 1,
                    'unit_price' => round($valor, 2),
                    'currency_id' => 'BRL',
                ]],
                'external_reference' => $referencia,
                'auto_return' => 'approved',
            ]);

        return $resp->json('init_point') ?? '';
    }
}
