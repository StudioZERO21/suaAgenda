<?php

declare(strict_types=1);

namespace App\Jobs\Billing;

use App\Services\Billing\SubscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAnniversaryInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 2;

    public function handle(): void
    {
        $count = SubscriptionService::make()->generateAnniversaryInvoices();

        Log::info("GenerateAnniversaryInvoicesJob: {$count} invoice(s) gerada(s)");
    }
}
