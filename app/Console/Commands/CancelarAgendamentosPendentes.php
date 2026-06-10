<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Agendamento;
use App\Models\Notificacao;
use Illuminate\Console\Command;

class CancelarAgendamentosPendentes extends Command
{
    protected $signature = 'agendamentos:cancelar-pendentes {--grace=2 : Horas após a data/hora do agendamento antes de cancelar}';

    protected $description = 'Cancela agendamentos pendentes que já passaram do horário + período de tolerância';

    public function handle(): int
    {
        $grace = (int) $this->option('grace');
        $limite = now()->subHours($grace);

        $pendentes = Agendamento::where('status', Agendamento::STATUS_PENDENTE)
            ->where('data_hora', '<', $limite)
            ->get();

        if ($pendentes->isEmpty()) {
            $this->info('Nenhum agendamento pendente vencido encontrado.');

            return self::SUCCESS;
        }

        $por_empresa = $pendentes->groupBy('company_id');

        foreach ($por_empresa as $companyId => $agendamentos) {
            $count = $agendamentos->count();

            Agendamento::whereIn('id', $agendamentos->pluck('id'))
                ->update(['status' => Agendamento::STATUS_CANCELADO]);

            Notificacao::create([
                'company_id' => $companyId,
                'tipo' => 'cancelamento_automatico',
                'titulo' => "{$count} agendamento".($count > 1 ? 's cancelados' : ' cancelado').' automaticamente',
                'mensagem' => "{$count} agendamento".($count > 1 ? 's pendentes foram cancelados' : ' pendente foi cancelado')." por falta de confirmação (tolerância: {$grace}h).",
            ]);
        }

        $total = $pendentes->count();
        $this->info("Cancelados {$total} agendamento".($total !== 1 ? 's' : '').' pendente'.($total !== 1 ? 's' : '').' vencido'.($total !== 1 ? 's' : '').'.');

        return self::SUCCESS;
    }
}
