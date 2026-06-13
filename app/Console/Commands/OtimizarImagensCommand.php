<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ClienteFoto;
use App\Models\Company;
use App\Models\PortfolioItem;
use App\Models\ProdutoImagem;
use App\Models\Profissional;
use App\Services\ImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Reprocessa o acervo de imagens já enviado, aplicando a mesma otimização
 * dos uploads (maior lado ≤ 1024px + recompressão). Regrava no mesmo
 * caminho, então não altera o banco. Idempotente: só regrava se reduzir.
 */
class OtimizarImagensCommand extends Command
{
    protected $signature = 'images:otimizar {--dry-run : Apenas relata, sem regravar} {--disk=public : Disco a processar}';

    protected $description = 'Otimiza as imagens já existentes (maior lado ≤ 1024px + compressão)';

    public function handle(ImageService $service): int
    {
        $disk = (string) $this->option('disk');
        $dryRun = (bool) $this->option('dry-run');

        $paths = $this->coletarCaminhos();
        $this->info(($dryRun ? '[dry-run] ' : '').'Caminhos de imagem encontrados: '.$paths->count());

        if ($paths->isEmpty()) {
            return self::SUCCESS;
        }

        $store = Storage::disk($disk);
        $bytesAntes = 0;
        $bytesDepois = 0;
        $otimizadas = 0;
        $jaOk = 0;
        $ausentes = 0;
        $ignoradas = 0;

        $bar = $this->output->createProgressBar($paths->count());
        $bar->start();

        foreach ($paths as $path) {
            if (! $store->exists($path)) {
                $ausentes++;
                $bar->advance();

                continue;
            }

            if ($dryRun) {
                $original = (string) $store->get($path);
                $antes = strlen($original);
                $novo = $service->optimizeBinary($original);
                $bytesAntes += $antes;

                if ($novo === null) {
                    $ignoradas++;
                    $bytesDepois += $antes;
                } elseif (strlen($novo) < $antes) {
                    $otimizadas++;
                    $bytesDepois += strlen($novo);
                } else {
                    $jaOk++;
                    $bytesDepois += $antes;
                }
            } else {
                $r = $service->reprocessar($path, $disk);
                $bytesAntes += $r['antes'];
                $bytesDepois += $r['depois'];
                match ($r['status']) {
                    'otimizado' => $otimizadas++,
                    'ja-otimizado' => $jaOk++,
                    'ignorado' => $ignoradas++,
                    default => $ausentes++,
                };
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $economia = $bytesAntes - $bytesDepois;
        $pct = $bytesAntes > 0 ? round($economia / $bytesAntes * 100, 1) : 0.0;

        $this->table(['Métrica', 'Valor'], [
            [$dryRun ? 'Seriam otimizadas' : 'Otimizadas', $otimizadas],
            ['Já otimizadas', $jaOk],
            ['Não-imagem / ignoradas', $ignoradas],
            ['Arquivo ausente', $ausentes],
            ['Antes', $this->mb($bytesAntes)],
            ['Depois', $this->mb($bytesDepois)],
            ['Economia', $this->mb($economia)." ({$pct}%)"],
        ]);

        if ($dryRun) {
            $this->comment('Nada foi alterado (dry-run). Rode sem --dry-run para aplicar.');
        } else {
            $this->info('Concluído.');
        }

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, string>
     */
    private function coletarCaminhos(): Collection
    {
        $paths = collect();

        $paths = $paths
            ->merge(Profissional::whereNotNull('foto_path')->pluck('foto_path'))
            ->merge(ClienteFoto::whereNotNull('imagem_path')->pluck('imagem_path'))
            ->merge(ProdutoImagem::whereNotNull('imagem_path')->pluck('imagem_path'))
            ->merge(PortfolioItem::withoutGlobalScopes()->whereNotNull('imagem_path')->pluck('imagem_path'))
            ->merge(Company::whereNotNull('logo_path')->pluck('logo_path'));

        // Banner e og:image vivem no JSON de settings de cada empresa.
        foreach (Company::query()->get(['id', 'settings']) as $company) {
            $site = $company->settings['site'] ?? [];
            $paths->push($site['banner_path'] ?? null);
            $paths->push($site['og_image'] ?? null);
        }

        return $paths->filter()->unique()->values();
    }

    private function mb(int $bytes): string
    {
        return number_format($bytes / 1048576, 2, ',', '.').' MB';
    }
}
