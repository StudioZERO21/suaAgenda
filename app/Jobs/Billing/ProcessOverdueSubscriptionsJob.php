<?php

declare(strict_types=1);

namespace App\Jobs\Billing;

use App\Models\BillingConfig;
use App\Services\Billing\BillingGatewayService;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\SubscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOverdueSubscriptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 2;

    public function handle(): void
    {
        $config = BillingConfig::current();
        $service = new SubscriptionService(
            new InvoiceService(new BillingGatewayService($config)),
            $config,
        );

        // First mark pending invoices as overdue
        $invoiceService = new InvoiceService(new BillingGatewayService($config));
        $markedOverdue = $invoiceService->markOverdueInvoices();

        // Expire trials
        $trialExpired = $service->expireTrials();

        // Process grace periods
        $result = $service->processOverdue();

        Log::info('ProcessOverdueSubscriptionsJob', [
            'marked_overdue' => $markedOverdue,
            'trial_expired' => $trialExpired,
            'warned' => $result['warned'],
            'suspended' => $result['suspended'],
            'cancelled' => $result['cancelled'],
        ]);
    }
}
