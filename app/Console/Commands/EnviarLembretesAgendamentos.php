<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Agendamento;
use App\Services\NotificationDispatcher;
use Illuminate\Console\Command;

class EnviarLembretesAgendamentos extends Command
{
    protected $signature = 'agendamentos:lembretes';

    protected $description = 'Envia lembretes (email/WhatsApp) para agendamentos do próximo período configurado';

    public function handle(): int
    {
        $agendamentos = Agendamento::with(['cliente', 'profissional', 'servico', 'company'])
            ->whereIn('status', [Agendamento::STATUS_PENDENTE, Agendamento::STATUS_CONFIRMADO])
            ->whereHas('company', fn ($q) => $q->where('ativo', true))
            ->get()
            ->filter(fn (Agendamento $ag) => $this->deveEnviar($ag));

        $enviados = 0;

        foreach ($agendamentos as $agendamento) {
            if (! $agendamento->company) {
                continue;
            }

            NotificationDispatcher::dispatch('lembrete_24h', $agendamento->company, ['agendamento' => $agendamento]);
            $enviados++;
        }

        $this->info("Lembretes enfileirados: {$enviados}");

        return self::SUCCESS;
    }

    private function deveEnviar(Agendamento $agendamento): bool
    {
        $company = $agendamento->company;

        if (! $company) {
            return false;
        }

        $advanced = $company->resolvedSettings()['advanced'] ?? [];

        if (! ($advanced['auto_reminder'] ?? true)) {
            return false;
        }

        $hours = (int) ($advanced['reminder_hours'] ?? 24);
        $targetDate = now()->addHours($hours)->toDateString();

        return $agendamento->data_hora->toDateString() === $targetDate;
    }
}
