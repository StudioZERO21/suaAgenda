<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\EvolutionService;
use Illuminate\Console\Command;

/**
 * Sincroniza status evolution_connected de todas as empresas com a API.
 */
final class SincronizarEvolutionStatus extends Command
{
    protected $signature = 'evolution:sincronizar-status';

    protected $description = 'Atualiza status de conexão WhatsApp (Evolution) de todas as empresas';

    public function handle(EvolutionService $evolution): int
    {
        if (! $evolution->configurado()) {
            $this->warn('Evolution API não configurada.');

            return self::FAILURE;
        }

        $empresas = Company::whereNotNull('evolution_instance')->get();
        $atualizadas = 0;

        foreach ($empresas as $company) {
            $status = $evolution->status((string) $company->evolution_instance);
            $connected = $status === 'open';

            if ($connected !== $company->evolution_connected) {
                $company->update([
                    'evolution_connected' => $connected,
                    'evolution_connected_at' => $connected ? now() : $company->evolution_connected_at,
                ]);
                $atualizadas++;
            }
        }

        $plataforma = $evolution->instanciaPlataforma();
        $statusPlataforma = $evolution->status($plataforma);

        $this->info("Empresas verificadas: {$empresas->count()} | Atualizadas: {$atualizadas}");
        $this->info("Plataforma [{$plataforma}]: {$statusPlataforma}");

        return self::SUCCESS;
    }
}
