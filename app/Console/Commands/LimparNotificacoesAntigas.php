<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Notificacao;
use Illuminate\Console\Command;

class LimparNotificacoesAntigas extends Command
{
    protected $signature = 'notificacoes:limpar {--days=90 : Dias de retenção — notificações lidas mais antigas serão removidas}';

    protected $description = 'Remove notificações lidas com mais de N dias (padrão: 90)';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $limite = now()->subDays($days);

        $deletadas = Notificacao::whereNotNull('read_at')
            ->where('created_at', '<', $limite)
            ->delete();

        $this->info("Removidas {$deletadas} notificação".($deletadas !== 1 ? 'ões' : '')." com mais de {$days} dia".($days !== 1 ? 's' : '').'.');

        return self::SUCCESS;
    }
}
