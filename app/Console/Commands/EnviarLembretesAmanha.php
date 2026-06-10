<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\LembreteAgendamento;
use App\Models\Agendamento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EnviarLembretesAmanha extends Command
{
    protected $signature = 'agendamentos:lembretes-amanha';

    protected $description = 'Envia email de lembrete para clientes com agendamentos amanhã';

    public function handle(): int
    {
        $amanha = now()->addDay();
        $inicio = $amanha->copy()->startOfDay();
        $fim = $amanha->copy()->endOfDay();

        $agendamentos = Agendamento::whereBetween('data_hora', [$inicio, $fim])
            ->whereIn('status', [Agendamento::STATUS_CONFIRMADO, Agendamento::STATUS_PENDENTE])
            ->with(['cliente', 'servico', 'profissional'])
            ->get();

        $enviados = 0;

        foreach ($agendamentos as $agendamento) {
            $email = $agendamento->cliente?->email;

            if (! $email) {
                continue;
            }

            Mail::to($email)->queue(new LembreteAgendamento($agendamento));
            $enviados++;
        }

        $this->info("Lembretes enviados: {$enviados}.");

        return self::SUCCESS;
    }
}
