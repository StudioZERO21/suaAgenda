<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BillingConfig;
use App\Services\Billing\PaymentWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookAsaasController extends Controller
{
    /**
     * Receive and process Asaas payment webhook events.
     * Route is public but validated against a shared secret token.
     */
    public function __invoke(Request $request): Response
    {
        // Validate shared secret (configured in billing_configs.credentials.webhook_token)
        $token = $request->header('asaas-access-token') ?? $request->query('token');
        $config = BillingConfig::current();
        $expectedToken = ($config->credentials ?? [])['webhook_token'] ?? null;

        if ($expectedToken && $token !== $expectedToken) {
            Log::warning('WebhookAsaasController: invalid token', [
                'ip' => $request->ip(),
            ]);

            return response('Unauthorized', 401);
        }

        $payload = $request->json()->all();

        if (empty($payload['event'])) {
            return response('', 422);
        }

        try {
            PaymentWebhookService::make()->handle($payload);
        } catch (\Throwable $e) {
            Log::error('WebhookAsaasController: processing failed', [
                'event' => $payload['event'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response('Internal error', 500);
        }

        return response('', 200);
    }
}
