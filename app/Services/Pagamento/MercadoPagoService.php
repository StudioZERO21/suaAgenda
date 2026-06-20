<?php

declare(strict_types=1);

namespace App\Services\Pagamento;

use App\Models\BillingConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MercadoPagoService
{
    private const BASE = 'https://api.mercadopago.com';

    /** Endpoint oficial OAuth (BR e demais países). */
    private const AUTH = 'https://auth.mercadopago.com';

    // ── Ambiente (sandbox / produção) ──────────────────────────────────────────

    /**
     * Ambiente atual da plataforma: 'sandbox' ou 'producao'.
     * Lê do BillingConfig (DB) com fallback para .env.
     */
    public static function getAmbiente(): string
    {
        return Cache::remember('mp_ambiente', 300, function () {
            try {
                $creds = BillingConfig::current()->credentials ?? [];

                return $creds['mp_ambiente'] ?? config('services.mercadopago.ambiente', 'sandbox');
            } catch (\Throwable) {
                return config('services.mercadopago.ambiente', 'sandbox');
            }
        });
    }

    public static function isSandbox(): bool
    {
        return self::getAmbiente() === 'sandbox';
    }

    /**
     * Public Key para uso no frontend do Checkout Bricks.
     */
    public static function getPublicKey(): string
    {
        return self::isSandbox()
            ? (config('services.mercadopago.public_key_test') ?? '')
            : (config('services.mercadopago.public_key') ?? '');
    }

    /**
     * Access Token da plataforma para chamadas de API (não por empresa).
     */
    public static function getPlatformAccessToken(): string
    {
        return self::isSandbox()
            ? (config('services.mercadopago.access_token_test') ?? '')
            : (config('services.mercadopago.access_token') ?? '');
    }

    // ── OAuth Connect ──────────────────────────────────────────────────────────

    /**
     * Redirect URI estático exigido pelo Mercado Pago (deve coincidir com o painel Developers).
     */
    public static function getRedirectUri(): string
    {
        $configured = config('services.mercadopago.redirect_uri');

        if (is_string($configured) && $configured !== '') {
            return rtrim($configured, '/');
        }

        return route('mp.oauth.callback', [], absolute: true);
    }

    /**
     * Verifica se credenciais OAuth da plataforma estão configuradas.
     */
    public static function isOAuthConfigured(): bool
    {
        return filled(config('services.mercadopago.client_id'))
            && filled(config('services.mercadopago.client_secret'))
            && filled(self::getRedirectUri());
    }

    /**
     * Gera par PKCE (RFC 7636) exigido quando habilitado no app Mercado Pago.
     *
     * @return array{verifier: string, challenge: string}
     */
    public static function generatePkce(): array
    {
        $verifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $challenge = rtrim(
            strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'),
            '='
        );

        return ['verifier' => $verifier, 'challenge' => $challenge];
    }

    /**
     * URL de autorização OAuth (redirecionar o usuário para cá).
     */
    public static function getAuthUrl(string $state, ?string $codeChallenge = null): string
    {
        $params = [
            'client_id' => config('services.mercadopago.client_id'),
            'response_type' => 'code',
            'redirect_uri' => self::getRedirectUri(),
            'state' => $state,
            'platform_id' => 'mp',
        ];

        if ($codeChallenge !== null && $codeChallenge !== '') {
            $params['code_challenge'] = $codeChallenge;
            $params['code_challenge_method'] = 'S256';
        }

        return self::AUTH.'/authorization?'.http_build_query($params);
    }

    /**
     * Troca o authorization code por access_token + refresh_token.
     *
     * @return array{access_token: string, refresh_token?: string, user_id?: int, ...}
     *
     * @throws RuntimeException
     */
    public static function exchangeCode(string $code, ?string $codeVerifier = null): array
    {
        $payload = [
            'client_id' => config('services.mercadopago.client_id'),
            'client_secret' => config('services.mercadopago.client_secret'),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => self::getRedirectUri(),
        ];

        if (self::isSandbox()) {
            $payload['test_token'] = 'true';
        }

        if ($codeVerifier !== null && $codeVerifier !== '') {
            $payload['code_verifier'] = $codeVerifier;
        }

        $resp = Http::timeout(15)->post(self::BASE.'/oauth/token', $payload);

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
     * Em sandbox retorna sandbox_init_point; em produção retorna init_point.
     */
    public static function criarLink(
        string $accessToken,
        float $valor,
        string $descricao,
        string $referencia = '',
        string $backUrl = ''
    ): string {
        $payload = [
            'items' => [[
                'title' => $descricao,
                'quantity' => 1,
                'unit_price' => round($valor, 2),
                'currency_id' => 'BRL',
            ]],
            'external_reference' => $referencia,
        ];

        if ($backUrl !== '') {
            $payload['back_urls'] = [
                'success' => $backUrl,
                'failure' => $backUrl,
                'pending' => $backUrl,
            ];
            $payload['auto_return'] = 'approved';
        }

        $resp = Http::timeout(15)
            ->withToken($accessToken)
            ->post(self::BASE.'/checkout/preferences', $payload);

        if (! $resp->successful()) {
            return '';
        }

        // Sandbox usa sandbox_init_point; produção usa init_point
        $key = self::isSandbox() ? 'sandbox_init_point' : 'init_point';

        return (string) ($resp->json($key) ?? $resp->json('init_point') ?? '');
    }

    /**
     * Busca pagamentos pelo external_reference (UUID do agendamento).
     * Retorna o primeiro pagamento aprovado, se existir.
     *
     * @return array{ok: bool, pago: bool, payment_id?: string, status?: string, erro?: string}
     */
    public static function buscarPagamentoPorReferencia(string $accessToken, string $externalReference): array
    {
        if ($accessToken === '' || $externalReference === '') {
            return ['ok' => false, 'pago' => false, 'erro' => 'Credenciais ou referência ausentes'];
        }

        try {
            $resp = Http::timeout(10)
                ->withToken($accessToken)
                ->get(self::BASE.'/v1/payments/search', [
                    'external_reference' => $externalReference,
                    'sort' => 'date_created',
                    'criteria' => 'desc',
                ]);

            if (! $resp->successful()) {
                return ['ok' => false, 'pago' => false, 'erro' => 'HTTP '.$resp->status()];
            }

            $results = $resp->json('results') ?? [];

            foreach ($results as $payment) {
                $status = (string) ($payment['status'] ?? '');
                if ($status === 'approved') {
                    return [
                        'ok' => true,
                        'pago' => true,
                        'payment_id' => (string) ($payment['id'] ?? ''),
                        'status' => $status,
                    ];
                }
            }

            return ['ok' => true, 'pago' => false, 'status' => 'não encontrado ou pendente'];
        } catch (\Exception $e) {
            return ['ok' => false, 'pago' => false, 'erro' => $e->getMessage()];
        }
    }
}
