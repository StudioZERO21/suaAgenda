<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Company;
use App\Models\Plan;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cria Stripe Checkout Sessions para assinatura de planos SaaS.
 * Usa a REST API do Stripe diretamente (sem SDK) via Http facade.
 *
 * Chaves configuradas em config('services.stripe_platform.*').
 * ATENÇÃO: estas são as credenciais da PLATAFORMA, não das empresas clientes.
 */
final class StripeCheckoutService
{
    private const STRIPE_API = 'https://api.stripe.com/v1';

    /**
     * Cria ou retorna o Stripe Customer ID da empresa.
     *
     * @throws RuntimeException
     */
    public function ensureCustomer(Company $company): string
    {
        if ($company->stripe_customer_id) {
            return $company->stripe_customer_id;
        }

        $resp = $this->stripe('POST', '/customers', [
            'email' => $company->email ?? '',
            'name' => $company->name,
            'metadata[company_id]' => $company->id,
            'metadata[slug]' => $company->slug,
        ]);

        $customerId = $resp['id'] ?? null;

        if (! $customerId) {
            throw new RuntimeException('Stripe: falha ao criar customer — '.(string) json_encode($resp));
        }

        $company->update(['stripe_customer_id' => $customerId]);

        return $customerId;
    }

    /**
     * Cria uma Checkout Session para assinar o plano informado.
     *
     * @return array{url: string, session_id: string}
     *
     * @throws RuntimeException
     */
    public function criarSessao(Company $company, Plan $plan): array
    {
        if (! $plan->stripe_price_id) {
            throw new RuntimeException("Plano '{$plan->slug}' não tem stripe_price_id configurado.");
        }

        $customerId = $this->ensureCustomer($company);

        $successUrl = url(config('services.stripe_platform.success_url', '/planos?checkout=sucesso'));
        $cancelUrl = url(config('services.stripe_platform.cancel_url', '/planos?checkout=cancelado'));

        $resp = $this->stripe('POST', '/checkout/sessions', [
            'customer' => $customerId,
            'mode' => 'subscription',
            'line_items[0][price]' => $plan->stripe_price_id,
            'line_items[0][quantity]' => '1',
            'success_url' => $successUrl.'&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'metadata[company_id]' => $company->id,
            'metadata[plan_slug]' => $plan->slug,
            'subscription_data[metadata][company_id]' => $company->id,
            'subscription_data[metadata][plan_slug]' => $plan->slug,
            'allow_promotion_codes' => 'true',
            'billing_address_collection' => 'auto',
            'locale' => 'pt-BR',
        ]);

        if (empty($resp['url'])) {
            throw new RuntimeException('Stripe: falha ao criar checkout session — '.(string) json_encode($resp));
        }

        return [
            'url' => $resp['url'],
            'session_id' => $resp['id'],
        ];
    }

    /**
     * Cancela a assinatura Stripe da empresa ao final do período vigente.
     */
    public function cancelarAssinatura(Company $company): bool
    {
        if (! $company->stripe_subscription_id) {
            return false;
        }

        $resp = $this->stripe('POST', "/subscriptions/{$company->stripe_subscription_id}", [
            'cancel_at_period_end' => 'true',
        ]);

        return ! empty($resp['id']);
    }

    /**
     * Verifica e decodifica o payload do webhook Stripe.
     *
     * @throws RuntimeException se a assinatura for inválida
     */
    public static function verifyWebhook(string $payload, string $sigHeader): array
    {
        $secret = (string) config('services.stripe_platform.webhook_secret', '');

        if ($secret === '') {
            return (array) json_decode($payload, true);
        }

        // Stripe usa "t=<timestamp>,v1=<hmac>"
        $parts = [];
        foreach (explode(',', $sigHeader) as $part) {
            [$k, $v] = array_pad(explode('=', $part, 2), 2, '');
            $parts[trim($k)] = trim($v);
        }

        $ts = $parts['t'] ?? '';
        $v1 = $parts['v1'] ?? '';

        if ($ts === '' || $v1 === '') {
            throw new RuntimeException('Stripe webhook: header Stripe-Signature inválido');
        }

        // Janela de 5 minutos para evitar replay attacks
        if (abs(time() - (int) $ts) > 300) {
            throw new RuntimeException('Stripe webhook: timestamp fora da janela de 5 min');
        }

        $expected = hash_hmac('sha256', "{$ts}.{$payload}", $secret);

        if (! hash_equals($expected, $v1)) {
            throw new RuntimeException('Stripe webhook: assinatura inválida');
        }

        return (array) json_decode($payload, true);
    }

    /**
     * @param  array<string, string>  $data
     * @return array<string, mixed>
     */
    private function stripe(string $method, string $path, array $data = []): array
    {
        $secret = (string) config('services.stripe_platform.secret', '');

        if ($secret === '') {
            throw new RuntimeException('STRIPE_PLATFORM_SECRET não configurado.');
        }

        $request = Http::withBasicAuth($secret, '')->timeout(15);

        $resp = match ($method) {
            'POST' => $request->asForm()->post(self::STRIPE_API.$path, $data),
            'DELETE' => $request->delete(self::STRIPE_API.$path),
            default => $request->get(self::STRIPE_API.$path, $data),
        };

        return $resp->json() ?? [];
    }
}
