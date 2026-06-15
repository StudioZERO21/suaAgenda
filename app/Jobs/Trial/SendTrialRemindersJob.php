<?php

declare(strict_types=1);

namespace App\Jobs\Trial;

use App\Mail\Trial\TrialReminderMail;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Enviado diariamente: dispara lembretes de trial no dia 4, 6 e 7
 * (contados a partir do início — expira no dia 8, ou seja, faltam 4, 2 e 1 dias).
 */
class SendTrialRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Dias em que trial_ends_at cai: hoje+3, hoje+1, hoje (último dia)
        $diasAlerta = [
            4 => now()->addDays(3)->toDateString(), // faltam 3 dias
            6 => now()->addDays(1)->toDateString(), // falta 1 dia
            7 => now()->toDateString(),              // hoje = último dia
        ];

        foreach ($diasAlerta as $dia => $dataExpiracao) {
            Subscription::with('company')
                ->where('status', Subscription::STATUS_TRIAL)
                ->whereDate('trial_ends_at', $dataExpiracao)
                ->get()
                ->each(function (Subscription $sub) use ($dia): void {
                    $email = $sub->company?->email;

                    if ($email === null || $email === '') {
                        return;
                    }

                    Mail::to($email)->queue(new TrialReminderMail($sub->company, $dia));
                });
        }
    }
}
