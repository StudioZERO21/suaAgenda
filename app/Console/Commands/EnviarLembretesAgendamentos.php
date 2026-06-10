<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\AgendamentoLembrete;
use App\Models\Agendamento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EnviarLembretesAgendamentos extends Command
{
    protected $signature = 'agendamentos:lembretes';

    protected $description = 'Envia e-mail de lembrete antes dos agendamentos confirmados/pendentes';

    public function handle(): int
    {
        $agendamentos = Agendamento::with(['cliente', 'profissional', 'servico', 'company'])
            ->whereIn('status', [Agendamento::STATUS_PENDENTE, Agendamento::STATUS_CONFIRMADO])
            ->whereHas('company', fn ($q) => $q->where('ativo', true))
            ->get()
            ->filter(fn (Agendamento $ag) => $this->deveEnviar($ag));

        $enviados = 0;

        foreach ($agendamentos as $agendamento) {
            $email = $agendamento->cliente?->email;

            if (! $email) {
                continue;
            }

            Mail::to($email)->queue(new AgendamentoLembrete($agendamento));
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
