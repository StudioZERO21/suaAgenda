<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block access when the company subscription is suspended or cancelled.
 * Super admins bypass this check.
 */
class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        $empresaId = $user->empresa_id;

        if (! $empresaId) {
            return $next($request);
        }

        $subscription = Subscription::where('company_id', $empresaId)
            ->latest()
            ->first();

        if (! $subscription) {
            return $next($request); // no subscription yet — let through (provisioning flow)
        }

        if ($subscription->status === Subscription::STATUS_SUSPENDED) {
            return redirect()->route('billing.suspended');
        }

        if ($subscription->status === Subscription::STATUS_CANCELLED) {
            return redirect()->route('billing.cancelled');
        }

        return $next($request);
    }
}
