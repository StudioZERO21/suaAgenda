<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Agendamento;
use App\Models\BloqueioAgenda;
use App\Models\HorarioTrabalho;
use App\Models\Servico;
use Carbon\Carbon;

/**
 * Validação de disponibilidade compartilhada entre o agendamento público
 * e os endpoints de slots. Garante que o horário submetido realmente cabe
 * na agenda do profissional (expediente, bloqueio, sobreposição e vínculo
 * serviço↔profissional) — o front só sugere, aqui é a fonte da verdade.
 */
class AgendamentoDisponibilidadeService
{
    /**
     * Avalia se um horário pode ser agendado.
     *
     * @return array{ok: bool, motivo: string|null}
     */
    public function validar(string $profissionalId, Servico $servico, Carbon $inicio): array
    {
        if (! $this->profissionalFazServico($profissionalId, $servico)) {
            return ['ok' => false, 'motivo' => 'Este profissional não realiza o serviço selecionado.'];
        }

        if ($this->diaBloqueado($profissionalId, $inicio)) {
            return ['ok' => false, 'motivo' => 'O profissional não está disponível nesta data.'];
        }

        if (! $this->dentroDoExpediente($profissionalId, $inicio, $servico->duracao_minutos)) {
            return ['ok' => false, 'motivo' => 'O horário escolhido está fora do expediente do profissional.'];
        }

        if ($this->temConflito($profissionalId, $inicio, $servico->duracao_minutos)) {
            return ['ok' => false, 'motivo' => 'Este horário acabou de ser reservado. Escolha outro, por favor.'];
        }

        return ['ok' => true, 'motivo' => null];
    }

    public function profissionalFazServico(string $profissionalId, Servico $servico): bool
    {
        return $servico->profissionais()
            ->where('profissionais.id', $profissionalId)
            ->exists();
    }

    public function diaBloqueado(string $profissionalId, Carbon $inicio): bool
    {
        return BloqueioAgenda::blockedOn($profissionalId, $inicio->format('Y-m-d'));
    }

    public function dentroDoExpediente(string $profissionalId, Carbon $inicio, int $duracao): bool
    {
        $horario = HorarioTrabalho::where('profissional_id', $profissionalId)
            ->where('dia_semana', (int) $inicio->format('w'))
            ->where('ativo', true)
            ->first();

        if (! $horario) {
            return false;
        }

        $dia = $inicio->format('Y-m-d');
        $abertura = Carbon::parse($dia.' '.$horario->hora_inicio);
        $fechamento = Carbon::parse($dia.' '.$horario->hora_fim);
        $fim = $inicio->copy()->addMinutes($duracao);

        return $inicio->gte($abertura) && $fim->lte($fechamento);
    }

    public function temConflito(string $profissionalId, Carbon $inicio, int $duracao, ?string $excluirId = null): bool
    {
        $fim = $inicio->copy()->addMinutes($duracao);

        $query = Agendamento::ativo()->where('profissional_id', $profissionalId);

        if ($excluirId !== null) {
            $query->where('id', '!=', $excluirId);
        }

        return $query->get()->contains(function (Agendamento $outro) use ($inicio, $fim): bool {
            $outroFim = $outro->data_hora->copy()->addMinutes($outro->duracao);

            return $inicio->lt($outroFim) && $fim->gt($outro->data_hora);
        });
    }
}
