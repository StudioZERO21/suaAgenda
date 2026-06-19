<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gatekeeping de assinatura — bloqueia acesso ao painel quando:
 *   - empresa está desativada pela plataforma (ativo = false)
 *   - trial expirou e não há stripe_subscription_id ativo
 *   - subscription manual está suspensa ou cancelada
 *
 * Rotas de billing e planos ficam sempre acessíveis para permitir upgrade.
 * Super admins ignoram todas as verificações.
 */
class CheckSubscription
{
    /** Prefixos de rota que passam mesmo sem assinatura ativa. */
    private const BYPASS = [
        'planos.',
        'billing.',
        'perfil',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->hasRole('super_admin')) {
            return $next($request);
        }

        // Rotas de upgrade/billing sempre passam
        $routeName = (string) ($request->route()?->getName() ?? '');
        foreach (self::BYPASS as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return $next($request);
            }
        }

        $company = $user->company;

        if (! $company) {
            return $next($request);
        }

        // Empresa desativada pela plataforma
        if (! $company->ativo) {
            return redirect()->route('billing.suspended');
        }

        // Assinatura Stripe ativa → liberado
        if ($company->stripe_subscription_id) {
            return $next($request);
        }

        // Trial sem data definida → empresa ainda em onboarding ou legacy sem expiração
        if ($company->trial_ends_at === null) {
            return $next($request);
        }

        // Trial ainda no prazo → liberado
        if ($company->trial_ends_at->isFuture()) {
            return $next($request);
        }

        // Trial expirado: checar subscription manual (sistema legado / admin billing)
        $subscription = Subscription::where('company_id', $company->id)
            ->latest()
            ->first();

        if ($subscription) {
            if ($subscription->status === Subscription::STATUS_SUSPENDED) {
                return redirect()->route('billing.suspended');
            }

            if ($subscription->status === Subscription::STATUS_CANCELLED) {
                return redirect()->route('billing.cancelled');
            }

            if ($subscription->isActive()) {
                return $next($request);
            }
        }

        // Trial expirado, sem Stripe, sem subscription manual ativa
        return redirect()->route('billing.trial-expirado');
    }
}
