<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Mail\Billing\FaturaPageMail;
use App\Models\Invoice;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Handles invoice lifecycle: creation, payment recognition, notification, cancellation.
 */
final class InvoiceService
{
    public function __construct(private readonly BillingGatewayService $gateway) {}

    /**
     * Generate a new invoice for a subscription and push it to the gateway.
     */
    public function generate(Subscription $subscription): Invoice
    {
        $company = $subscription->company;
        $dueDate = Carbon::now()->addDays(3); // always 3 days from generation

        $invoice = Invoice::create([
            'subscription_id' => $subscription->id,
            'company_id' => $company->id,
            'number' => Invoice::nextNumber(),
            'status' => Invoice::STATUS_PENDING,
            'amount' => $subscription->monthly_amount,
            'due_date' => $dueDate,
            'gateway' => $subscription->gateway,
        ]);

        // Push to gateway if configured
        $customerId = $subscription->gateway_customer_id
            ?? $this->gateway->ensureCustomer($company);

        if ($customerId) {
            if (! $subscription->gateway_customer_id) {
                $subscription->update(['gateway_customer_id' => $customerId]);
            }

            $charge = $this->gateway->createCharge(
                customerId: $customerId,
                amount: (float) $invoice->amount,
                dueDate: $dueDate,
                description: "Assinatura suaAgenda.pro — {$company->name}",
                externalRef: $invoice->id,
            );

            if ($charge) {
                $invoice->update([
                    'gateway_invoice_id' => $charge['id'],
                    'gateway_payment_url' => $charge['url'],
                ]);
            }
        }

        return $invoice;
    }

    /**
     * Mark invoice as paid and update subscription status.
     */
    public function markPaid(Invoice $invoice, ?string $paymentMethod = null, ?\DateTimeInterface $paidAt = null): void
    {
        $invoice->update([
            'status' => Invoice::STATUS_PAID,
            'paid_at' => $paidAt ?? now(),
            'payment_method' => $paymentMethod,
        ]);

        $subscription = $invoice->subscription;

        if ($subscription && ! $subscription->isActive()) {
            $subscription->update([
                'status' => Subscription::STATUS_ACTIVE,
                'suspended_at' => null,
            ]);

            Log::info('InvoiceService: subscription reactivated after payment', [
                'company_id' => $subscription->company_id,
                'invoice_id' => $invoice->id,
            ]);
        }

        if ($subscription) {
            $this->advancePeriod($subscription);
        }

        $this->notifyPaymentConfirmed($invoice);
    }

    /**
     * Mark overdue invoices (past due_date and still pending).
     */
    public function markOverdueInvoices(): int
    {
        return Invoice::where('status', Invoice::STATUS_PENDING)
            ->where('due_date', '<', now()->startOfDay())
            ->update(['status' => Invoice::STATUS_OVERDUE]);
    }

    /**
     * Cancel an invoice (e.g., when subscription is cancelled).
     */
    public function cancel(Invoice $invoice): void
    {
        $invoice->update(['status' => Invoice::STATUS_CANCELLED]);
    }

    private function advancePeriod(Subscription $subscription): void
    {
        $start = $subscription->current_period_end ?? now();
        $anniversaryDay = (int) ($subscription->anniversary_day?->day ?? now()->day);

        $nextStart = Carbon::instance($start)->addMonth()->startOfDay();
        $nextEnd = Carbon::create($nextStart->year, $nextStart->month, $anniversaryDay)
            ->addMonth()
            ->subDay()
            ->endOfDay();

        $subscription->update([
            'current_period_start' => $nextStart,
            'current_period_end' => $nextEnd,
        ]);
    }

    private function notifyPaymentConfirmed(Invoice $invoice): void
    {
        $company = $invoice->company;

        if (! $company?->email) {
            return;
        }

        try {
            Mail::to($company->email)->send(new FaturaPageMail($invoice));
        } catch (\Throwable $e) {
            Log::error('InvoiceService: payment notification failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
