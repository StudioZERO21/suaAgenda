<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\EvolutionService;
use Illuminate\Console\Command;

/**
 * Cria a instância Evolution da plataforma para notificações fallback.
 */
final class ProvisionarEvolutionPlataforma extends Command
{
    protected $signature = 'evolution:provisionar-plataforma';

    protected $description = 'Cria instância WhatsApp da plataforma no Evolution (escaneie o QR após rodar)';

    public function handle(EvolutionService $evolution): int
    {
        $result = $evolution->provisionarInstanciaPlataforma();

        if (! ($result['ok'] ?? false)) {
            $this->error($result['erro'] ?? 'Erro desconhecido');

            return self::FAILURE;
        }

        $this->info("Instância criada: {$result['instance']}");
        $this->info("Status: {$result['status']}");

        if (! empty($result['qr'])) {
            $this->warn('Abra Admin → Configurações → Evolution e escaneie o QR da instância plataforma.');
            $this->line('Ou acesse o Manager: /manager → instância "'.$result['instance'].'"');
        } else {
            $this->info('Já conectada ou aguardando conexão.');
        }

        return self::SUCCESS;
    }
}
