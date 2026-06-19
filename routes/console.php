<?php

use App\Jobs\Billing\GenerateAnniversaryInvoicesJob;
use App\Jobs\Billing\ProcessOverdueSubscriptionsJob;
use App\Jobs\Billing\SyncGatewayPaymentsJob;
use App\Jobs\ExpireUnpaidBookingsJob;
use App\Jobs\Trial\SendTrialRemindersJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('agendamentos:lembretes')->dailyAt('08:00');
Schedule::command('agendamentos:lembretes-amanha')->dailyAt('18:00');
Schedule::command('clientes:aniversarios')->dailyAt('07:00');
Schedule::command('produtos:estoque-baixo')->dailyAt('06:00');
Schedule::command('agendamentos:cancelar-pendentes')->hourly();
Schedule::command('relatorio:semanal')->weeklyOn(1, '09:00');
Schedule::command('notificacoes:limpar')->monthlyOn(1, '03:00');
Schedule::command('activitylog:clean')->dailyAt('02:00');
Schedule::command('lgpd:retencao')->dailyAt('03:30');

// ── Trial reminders ──────────────────────────────────────────────────
Schedule::job(SendTrialRemindersJob::class)->dailyAt('08:00');

// ── Sinal: liberar slots não pagos a cada 2 min ───────────────────────
Schedule::job(ExpireUnpaidBookingsJob::class)->everyTwoMinutes();

// ── Billing automático ────────────────────────────────────────────────
Schedule::job(GenerateAnniversaryInvoicesJob::class)->dailyAt('06:00');
Schedule::job(ProcessOverdueSubscriptionsJob::class)->dailyAt('07:00');
Schedule::job(SyncGatewayPaymentsJob::class)->everySixHours();
