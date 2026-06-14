<?php

declare(strict_types=1);

namespace App\Jobs\Billing;

use App\Models\BillingConfig;
use App\Models\Invoice;
use App\Services\Billing\BillingGatewayService;
use App\Services\Billing\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Fallback sync: poll Asaas for payment status of pending invoices
 * that should have been updated by webhook but weren't.
 */
class SyncGatewayPaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 2;

    public function handle(): void
    {
        $config = BillingConfig::current();
        $gateway = new BillingGatewayService($config);
        $invoiceService = new InvoiceService($gateway);
        $synced = 0;

        Invoice::where('status', Invoice::STATUS_PENDING)
            ->whereNotNull('gateway_invoice_id')
            ->where('due_date', '>=', now()->subDays(60))
            ->chunk(20, function ($invoices) use ($gateway, $invoiceService, &$synced): void {
                foreach ($invoices as $invoice) {
                    try {
                        $status = $gateway->getPaymentStatus($invoice->gateway_invoice_id);

                        if (in_array($status, ['RECEIVED', 'CONFIRMED'])) {
                            $invoiceService->markPaid($invoice, 'pix');
                            $synced++;
                        } elseif ($status === 'OVERDUE') {
                            $invoice->update(['status' => Invoice::STATUS_OVERDUE]);
                        }
                    } catch (\Throwable $e) {
                        Log::error('SyncGatewayPaymentsJob: sync failed for invoice', [
                            'invoice_id' => $invoice->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info("SyncGatewayPaymentsJob: {$synced} payment(s) synced from gateway");
    }
}
