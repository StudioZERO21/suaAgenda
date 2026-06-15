<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Company;

/**
 * Encapsula os limites do plano de uma empresa e facilita verificações de gating.
 * Lê max_profissionais da tabela plans (já populada pelo seeder).
 * -1 significa ilimitado.
 */
final class PlanLimits
{
    private readonly int $maxProfissionais;

    private readonly int $usedProfissionais;

    /** @var list<string> */
    private readonly array $features;

    private readonly string $nomePlano;

    public function __construct(private readonly Company $company)
    {
        $plan = $company->plan;

        // Sem plano vinculado → sem restrição (trial ou provisionamento em andamento)
        $this->maxProfissionais = $plan?->max_profissionais ?? -1;
        $this->features = (array) ($plan?->features ?? []);
        $this->nomePlano = $plan?->nome ?? ucfirst($company->plan_slug ?? 'starter');
        $this->usedProfissionais = $company->profissionais()->count();
    }

    public static function forCompany(Company $company): self
    {
        return new self($company);
    }

    // ── Profissionais ────────────────────────────────────────────────────

    public function canAddProfissional(): bool
    {
        return $this->maxProfissionais === -1
            || $this->usedProfissionais < $this->maxProfissionais;
    }

    public function profissionaisUsados(): int
    {
        return $this->usedProfissionais;
    }

    public function profissionaisLimite(): int
    {
        return $this->maxProfissionais;
    }

    /** Retorna PHP_INT_MAX quando ilimitado. */
    public function profissionaisRestantes(): int
    {
        if ($this->maxProfissionais === -1) {
            return PHP_INT_MAX;
        }

        return max(0, $this->maxProfissionais - $this->usedProfissionais);
    }

    public function ilimitadoProfissionais(): bool
    {
        return $this->maxProfissionais === -1;
    }

    // ── Features ────────────────────────────────────────────────────────

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features, true);
    }

    // ── Info geral ───────────────────────────────────────────────────────

    public function nomePlano(): string
    {
        return $this->nomePlano;
    }

    /**
     * Mensagem de upgrade amigável para a tela de limite atingido.
     */
    public function mensagemLimiteProfissionais(): string
    {
        if ($this->ilimitadoProfissionais()) {
            return '';
        }

        return "Seu plano {$this->nomePlano} permite até {$this->maxProfissionais} "
            .($this->maxProfissionais === 1 ? 'profissional' : 'profissionais')
            .'. Faça upgrade para adicionar mais.';
    }
}
