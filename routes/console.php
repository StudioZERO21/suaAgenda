<?php

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
