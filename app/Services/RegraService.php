<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CompanyRegra;
use Illuminate\Support\Facades\Cache;

/**
 * Leitura das regras de negócio efetivas de uma empresa
 * (catálogo + ativação/parâmetros da empresa), com cache.
 */
class RegraService
{
    public function enabled(string $codigo, ?string $companyId = null): bool
    {
        $companyId ??= auth()->user()?->empresa_id;

        if ($companyId === null) {
            return false;
        }

        return array_key_exists($codigo, $this->paraEmpresa($companyId));
    }

    public function param(string $codigo, string $key, mixed $default = null, ?string $companyId = null): mixed
    {
        $companyId ??= auth()->user()?->empresa_id;

        if ($companyId === null) {
            return $default;
        }

        return $this->paraEmpresa($companyId)[$codigo][$key] ?? $default;
    }

    /**
     * Regras ativas da empresa: codigo => params efetivos
     * (defaults do catálogo sobrescritos pela configuração da empresa).
     *
     * @return array<string, array<string, mixed>>
     */
    public function paraEmpresa(string $companyId): array
    {
        return Cache::remember(
            $this->cacheKey($companyId),
            now()->addHours(12),
            fn (): array => CompanyRegra::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('ativo', true)
                ->with('regraCatalogo')
                ->get()
                ->filter(fn (CompanyRegra $regra) => $regra->regraCatalogo?->ativo === true)
                ->mapWithKeys(fn (CompanyRegra $regra): array => [
                    $regra->regraCatalogo->codigo => array_merge(
                        $regra->regraCatalogo->params_default ?? [],
                        $regra->params ?? [],
                    ),
                ])
                ->all(),
        );
    }

    public function invalidar(string $companyId): void
    {
        Cache::forget($this->cacheKey($companyId));
    }

    private function cacheKey(string $companyId): string
    {
        return "regras:{$companyId}";
    }
}
