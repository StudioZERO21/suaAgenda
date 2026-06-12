<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Agendamento;

/**
 * Aplica a política de cancelamento configurada pela empresa
 * (regra cancelamento_antecedencia) e a informação de sinal.
 */
class AgendamentoCancelamentoService
{
    public function __construct(private readonly RegraService $regras) {}

    /**
     * @return array{ok: bool, motivo: string|null}
     */
    public function podeCancelar(Agendamento $agendamento): array
    {
        $cancelavel = in_array($agendamento->status, [
            Agendamento::STATUS_PENDENTE,
            Agendamento::STATUS_CONFIRMADO,
        ], true) && $agendamento->data_hora->isFuture();

        if (! $cancelavel) {
            return ['ok' => false, 'motivo' => 'Este agendamento não pode mais ser cancelado.'];
        }

        $companyId = $agendamento->company_id;

        if (! $this->regras->enabled('cancelamento_antecedencia', $companyId)) {
            return ['ok' => true, 'motivo' => null];
        }

        $horasMin = (int) $this->regras->param('cancelamento_antecedencia', 'horas_min', 24, $companyId);

        if (now()->diffInHours($agendamento->data_hora, false) < $horasMin) {
            $motivo = "O cancelamento só é permitido com pelo menos {$horasMin}h de antecedência.";

            if ($this->sinalNaoReembolsavel($companyId)) {
                $motivo .= ' Após esse prazo, o sinal pago não é reembolsável.';
            }

            return ['ok' => false, 'motivo' => $motivo];
        }

        return ['ok' => true, 'motivo' => null];
    }

    /**
     * Mensagem da política de cancelamento/sinal exibida ao cliente.
     */
    public function descricaoPolitica(string $companyId): ?string
    {
        $partes = [];

        if ($this->regras->enabled('cancelamento_antecedencia', $companyId)) {
            $horas = (int) $this->regras->param('cancelamento_antecedencia', 'horas_min', 24, $companyId);
            $partes[] = "Cancelamento permitido até {$horas}h antes do horário marcado.";
        }

        if ($this->regras->enabled('sinal', $companyId)) {
            $pct = (float) $this->regras->param('sinal', 'percentual', 0, $companyId);

            if ($pct > 0) {
                $reembolso = $this->regras->param('sinal', 'reembolsavel', true, $companyId)
                    ? 'reembolsável em cancelamentos dentro do prazo'
                    : 'não reembolsável';
                $partes[] = sprintf('Reserva com sinal de %s%% do valor (%s).', rtrim(rtrim(number_format($pct, 1, ',', '.'), '0'), ','), $reembolso);
            }
        }

        return $partes === [] ? null : implode(' ', $partes);
    }

    private function sinalNaoReembolsavel(string $companyId): bool
    {
        return $this->regras->enabled('sinal', $companyId)
            && (float) $this->regras->param('sinal', 'percentual', 0, $companyId) > 0
            && $this->regras->param('sinal', 'reembolsavel', true, $companyId) === false;
    }
}
