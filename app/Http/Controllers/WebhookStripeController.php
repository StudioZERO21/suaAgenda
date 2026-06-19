<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\Billing\StripeCheckoutService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WebhookStripeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = (string) $request->header('Stripe-Signature', '');

        try {
            $event = StripeCheckoutService::verifyWebhook($payload, $sigHeader);
        } catch (RuntimeException $e) {
            Log::warning('WebhookStripe: '.$e->getMessage());

            return response('Unauthorized', 401);
        }

        $type = $event['type'] ?? '';

        try {
            match ($type) {
                'checkout.session.completed' => $this->handleCheckoutCompleted($event),
                'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event),
                'customer.subscription.updated' => $this->handleSubscriptionUpdated($event),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error("WebhookStripe: erro ao processar {$type} — ".$e->getMessage(), [
                'event_id' => $event['id'] ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            return response('Internal error', 500);
        }

        return response('OK', 200);
    }

    private function handleCheckoutCompleted(array $event): void
    {
        $session = $event['data']['object'] ?? [];
        $companyId = $session['metadata']['company_id'] ?? null;
        $planSlug = $session['metadata']['plan_slug'] ?? null;
        $customerId = $session['customer'] ?? null;
        $subscriptionId = $session['subscription'] ?? null;

        if (! $companyId || ! $planSlug) {
            Log::warning('WebhookStripe checkout.session.completed: metadata incompleto', [
                'session_id' => $session['id'] ?? null,
            ]);

            return;
        }

        $company = Company::find($companyId);

        if (! $company) {
            Log::warning("WebhookStripe: Company {$companyId} não encontrada");

            return;
        }

        $company->update([
            'plan_slug' => $planSlug,
            'plano' => $planSlug,
            'stripe_customer_id' => $customerId ?? $company->stripe_customer_id,
            'stripe_subscription_id' => $subscriptionId ?? $company->stripe_subscription_id,
            'trial_ends_at' => null,
        ]);

        Log::info("WebhookStripe: Company {$company->name} assinou plano {$planSlug}", [
            'company_id' => $companyId,
            'session_id' => $session['id'] ?? null,
        ]);
    }

    private function handleSubscriptionDeleted(array $event): void
    {
        $subscription = $event['data']['object'] ?? [];
        $subscriptionId = $subscription['id'] ?? null;
        $customerId = $subscription['customer'] ?? null;

        if (! $subscriptionId) {
            return;
        }

        $company = Company::where('stripe_subscription_id', $subscriptionId)
            ->orWhere('stripe_customer_id', $customerId)
            ->first();

        if (! $company) {
            Log::warning("WebhookStripe: nenhuma empresa com subscription {$subscriptionId}");

            return;
        }

        $company->update([
            'plan_slug' => 'starter',
            'plano' => 'starter',
            'stripe_subscription_id' => null,
        ]);

        Log::info("WebhookStripe: assinatura cancelada — {$company->name} revertida para starter");
    }

    private function handleSubscriptionUpdated(array $event): void
    {
        $subscription = $event['data']['object'] ?? [];
        $subscriptionId = $subscription['id'] ?? null;
        $status = $subscription['status'] ?? '';

        // Só nos interessa quando reativada (active) ou cancelada (canceled)
        if (! in_array($status, ['active', 'canceled'], strict: true) || ! $subscriptionId) {
            return;
        }

        if ($status === 'canceled') {
            $this->handleSubscriptionDeleted($event);
        }
    }
}
