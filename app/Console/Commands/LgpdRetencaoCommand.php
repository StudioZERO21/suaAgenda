<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\Company;
use App\Services\LgpdService;
use App\Services\RegraService;
use Illuminate\Console\Command;

/**
 * Retenção de dados (LGPD): anonimiza titulares sem atividade além do
 * prazo configurado. Sem prazo configurado, o comando não faz nada —
 * a configuração por empresa virá da regra de negócio lgpd_retencao.
 */
class LgpdRetencaoCommand extends Command
{
    protected $signature = 'lgpd:retencao {--meses= : Prazo de retenção em meses (sobrepõe a configuração)} {--dry-run : Apenas lista, sem anonimizar}';

    protected $description = 'Anonimiza clientes inativos além do prazo de retenção (LGPD)';

    public function handle(LgpdService $lgpd): int
    {
        $mesesOpcao = $this->option('meses') !== null ? (int) $this->option('meses') : null;
        $dryRun = (bool) $this->option('dry-run');
        $totalAnonimizados = 0;

        Company::query()->each(function (Company $company) use ($lgpd, $mesesOpcao, $dryRun, &$totalAnonimizados): void {
            $meses = $mesesOpcao ?? $this->mesesConfigurados($company);

            if ($meses === null || $meses <= 0) {
                return;
            }

            $limite = now()->subMonths($meses);

            $candidatos = Cliente::where('company_id', $company->id)
                ->whereNull('anonymized_at')
                ->where('created_at', '<', $limite)
                ->whereDoesntHave('agendamentos', fn ($q) => $q->where('data_hora', '>=', $limite))
                ->get();

            foreach ($candidatos as $cliente) {
                if ($dryRun) {
                    $this->line("[dry-run] {$company->name}: {$cliente->name} ({$cliente->id})");

                    continue;
                }

                $lgpd->anonimizar($cliente);
                $totalAnonimizados++;
            }
        });

        $this->info("Retenção LGPD concluída. Anonimizados: {$totalAnonimizados}.");

        return self::SUCCESS;
    }

    /**
     * Prazo de retenção da empresa, lido da regra lgpd_retencao.
     * Empresa sem a regra ativa = retenção desligada.
     */
    private function mesesConfigurados(Company $company): ?int
    {
        $regras = app(RegraService::class);

        if (! $regras->enabled('lgpd_retencao', $company->id)) {
            return null;
        }

        $meses = $regras->param('lgpd_retencao', 'meses', null, $company->id);

        return $meses !== null ? (int) $meses : null;
    }
}
