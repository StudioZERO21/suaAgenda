<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Agendamento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireUnpaidBookingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $expirados = Agendamento::where('status', Agendamento::STATUS_AGUARDANDO_SINAL)
            ->where('created_at', '<', now()->subMinutes(10))
            ->get();

        foreach ($expirados as $ag) {
            $ag->update([
                'status' => Agendamento::STATUS_CANCELADO,
                'sinal_status' => Agendamento::SINAL_EXPIRADO,
            ]);

            Log::info('ExpireUnpaidBookingsJob: slot expirado e liberado', ['agendamento_id' => $ag->id]);
        }
    }
}
