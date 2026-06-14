<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Mail\Billing\AssinaturaCanceladaMail;
use App\Mail\Billing\AssinaturaSuspensaMail;
use App\Mail\Billing\FaturaGeradaMail;
use App\Mail\Billing\FaturaVencidaMail;
use App\Models\BillingConfig;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Handles the full subscription lifecycle: creation, billing, grace period, suspension, cancellation.
 */
final class SubscriptionService
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly BillingConfig $billingConfig,
    ) {}

    public static function make(): self
    {
        $config = BillingConfig::current();

        return new self(
            new InvoiceService(new BillingGatewayService($config)),
            $config,
        );
    }

    /**
     * Create or restore a subscription for a company.
     */
    public function provision(Company $company, string $planSlug, float $monthlyAmount): Subscription
    {
        $existing = Subscription::where('company_id', $company->id)->latest()->first();

        if ($existing && $existing->isActive()) {
            return $existing;
        }

        $trialDays = $company->trial_ends_at
            ? max(0, (int) now()->diffInDays($company->trial_ends_at, false))
            : 0;

        $anniversaryDay = now()->day;
        $periodStart = now()->startOfDay();
        $periodEnd = now()->addMonth()->subDay()->endOfDay();

        return Subscription::create([
            'company_id' => $company->id,
            'plan_slug' => $planSlug,
            'status' => $trialDays > 0 ? Subscription::STATUS_TRIAL : Subscription::STATUS_ACTIVE,
            'trial_ends_at' => $company->trial_ends_at,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'anniversary_day' => Carbon::now()->day($anniversaryDay),
            'gateway' => 'asaas',
            'monthly_amount' => $monthlyAmount,
        ]);
    }

    /**
     * Generate anniversary invoices for subscriptions due today.
     */
    public function generateAnniversaryInvoices(): int
    {
        $today = now()->day;
        $count = 0;

        Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->whereDay('anniversary_day', $today)
            ->with('company')
            ->chunk(50, function ($subscriptions) use (&$count): void {
                foreach ($subscriptions as $subscription) {
                    try {
                        $this->invoiceService->generate($subscription);
                        $count++;
                        $this->sendBillingNotification($subscription->company, 'invoice_generated');
                    } catch (\Throwable $e) {
                        Log::error('SubscriptionService: invoice generation failed', [
                            'subscription_id' => $subscription->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $count;
    }

    /**
     * Expire trial subscriptions — move to suspended if trial ended.
     */
    public function expireTrials(): int
    {
        return Subscription::where('status', Subscription::STATUS_TRIAL)
            ->where('trial_ends_at', '<', now())
            ->update([
                'status' => Subscription::STATUS_SUSPENDED,
                'suspended_at' => now(),
            ]);
    }

    /**
     * Process overdue subscriptions:
     * - D+warning: send warning notification
     * - D+suspend: suspend
     * - D+cancel: cancel
     */
    public function processOverdue(): array
    {
        $warningDays = $this->billingConfig->grace_warning_days;
        $suspendDays = $this->billingConfig->grace_suspend_days;
        $cancelDays = $this->billingConfig->grace_cancel_days;

        $warned = $this->warnOverdue($warningDays);
        $suspended = $this->suspendOverdue($suspendDays);
        $cancelled = $this->cancelOverdue($cancelDays);

        return compact('warned', 'suspended', 'cancelled');
    }

    private function warnOverdue(int $warningDays): int
    {
        $threshold = now()->subDays($warningDays);
        $count = 0;

        $overdueInvoices = Invoice::where('status', Invoice::STATUS_OVERDUE)
            ->where('due_date', '<=', $threshold)
            ->where('due_date', '>', now()->subDays($this->billingConfig->grace_suspend_days))
            ->with('subscription.company')
            ->get();

        foreach ($overdueInvoices as $invoice) {
            try {
                $company = $invoice->subscription?->company;
                if ($company) {
                    $this->sendBillingNotification($company, 'payment_overdue_warning', $invoice);
                    $count++;
                }
            } catch (\Throwable $e) {
                Log::error('SubscriptionService: warn notification failed', ['invoice_id' => $invoice->id]);
            }
        }

        return $count;
    }

    private function suspendOverdue(int $suspendDays): int
    {
        $threshold = now()->subDays($suspendDays);

        $subscriptions = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->whereHas('invoices', fn ($q) => $q
                ->where('status', Invoice::STATUS_OVERDUE)
                ->where('due_date', '<=', $threshold)
            )
            ->with('company')
            ->get();

        $count = 0;
        foreach ($subscriptions as $subscription) {
            $subscription->update([
                'status' => Subscription::STATUS_SUSPENDED,
                'suspended_at' => now(),
            ]);
            try {
                $this->sendBillingNotification($subscription->company, 'subscription_suspended');
            } catch (\Throwable $e) {
                Log::error('SubscriptionService: suspend notification failed', ['sub_id' => $subscription->id]);
            }
            $count++;
        }

        return $count;
    }

    private function cancelOverdue(int $cancelDays): int
    {
        $threshold = now()->subDays($cancelDays);

        $subscriptions = Subscription::where('status', Subscription::STATUS_SUSPENDED)
            ->where('suspended_at', '<=', $threshold)
            ->with('company')
            ->get();

        $count = 0;
        foreach ($subscriptions as $subscription) {
            $subscription->update([
                'status' => Subscription::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);
            Invoice::where('subscription_id', $subscription->id)
                ->where('status', Invoice::STATUS_PENDING)
                ->update(['status' => Invoice::STATUS_CANCELLED]);
            try {
                $this->sendCancellationNotification($subscription->company);
            } catch (\Throwable $e) {
                Log::error('SubscriptionService: cancel notification failed', ['sub_id' => $subscription->id]);
            }
            $count++;
        }

        return $count;
    }

    /**
     * Send billing notification by configured channel (email or WhatsApp).
     */
    private function sendBillingNotification(
        Company $company,
        string $type,
        ?Invoice $invoice = null,
    ): void {
        $channel = $this->billingConfig->notification_channel_billing;

        if ($channel === 'email' && $company->email) {
            $mailable = match ($type) {
                'invoice_generated' => $invoice
                    ? new FaturaGeradaMail($invoice)
                    : null,
                'payment_overdue_warning' => $invoice
                    ? new FaturaVencidaMail($invoice)
                    : null,
                'subscription_suspended' => new AssinaturaSuspensaMail($company),
                default => null,
            };

            if ($mailable) {
                Mail::to($company->email)->send($mailable);
            }
        }
    }

    private function sendCancellationNotification(Company $company): void
    {
        $channel = $this->billingConfig->notification_channel_cancel;

        if ($channel === 'email' && $company->email) {
            Mail::to($company->email)->send(new AssinaturaCanceladaMail($company));
        }
        // WhatsApp cancellation via NotificationDispatcher would go here
    }
}
