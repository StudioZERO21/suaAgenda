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

    protected $description = 'Envia e-mail de lembrete 24h antes dos agendamentos confirmados/pendentes';

    public function handle(): int
    {
        $amanha = now()->addDay();

        $agendamentos = Agendamento::with(['cliente', 'profissional', 'servico', 'company'])
            ->whereDate('data_hora', $amanha->toDateString())
            ->whereIn('status', [Agendamento::STATUS_PENDENTE, Agendamento::STATUS_CONFIRMADO])
            ->get();

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
}
