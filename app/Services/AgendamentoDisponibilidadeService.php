<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Agendamento;
use App\Models\BloqueioAgenda;
use App\Models\Company;
use App\Models\HorarioTrabalho;
use App\Models\Profissional;
use App\Models\Servico;
use App\Support\CompanyHours;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;

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
        $profissional = Profissional::find($profissionalId);

        if ($profissional === null) {
            return false;
        }

        $expediente = $this->expedienteDoProfissional(
            $profissional,
            $profissional->company,
            $inicio->copy()->startOfDay()
        );

        if ($expediente === null) {
            return false;
        }

        $dia = $inicio->format('Y-m-d');
        $abertura = Carbon::parse($dia.' '.$expediente['inicio']);
        $fechamento = Carbon::parse($dia.' '.$expediente['fim']);
        $fim = $inicio->copy()->addMinutes($duracao);

        return $inicio->gte($abertura) && $fim->lte($fechamento);
    }

    /**
     * Horário do profissional na data; se não configurado, usa horário da empresa.
     *
     * @return array{inicio: string, fim: string}|null
     */
    public function expedienteDoProfissional(Profissional $profissional, Company $company, Carbon $data): ?array
    {
        $diaSemana = (int) $data->format('w');

        $horario = HorarioTrabalho::where('profissional_id', $profissional->id)
            ->where('dia_semana', $diaSemana)
            ->where('ativo', true)
            ->first();

        if ($horario !== null) {
            return [
                'inicio' => $horario->hora_inicio,
                'fim' => $horario->hora_fim,
            ];
        }

        return CompanyHours::expedienteNaData($company->resolvedSettings(), $data);
    }

    /**
     * Gera slots de agendamento para profissional + serviço + data.
     *
     * @return list<array{hora: string, disponivel: bool}>
     */
    public function gerarSlots(Profissional $profissional, Company $company, Servico $servico, Carbon $data): array
    {
        if (BloqueioAgenda::blockedOn($profissional->id, $data->format('Y-m-d'))) {
            return [];
        }

        $expediente = $this->expedienteDoProfissional($profissional, $company, $data);

        if ($expediente === null) {
            return [];
        }

        $duracao = $servico->duracao_minutos;
        $inicio = Carbon::parse($data->format('Y-m-d').' '.$expediente['inicio']);
        $fim = Carbon::parse($data->format('Y-m-d').' '.$expediente['fim']);
        $agora = now();

        $slots = [];
        $current = $inicio->copy();

        while ($current->copy()->addMinutes($duracao)->lte($fim)) {
            $disponivel = $current->gt($agora)
                && ! $this->temConflito($profissional->id, $current, $duracao);

            $slots[] = ['hora' => $current->format('H:i'), 'disponivel' => $disponivel];
            $current->addMinutes($duracao);
        }

        return $slots;
    }

    /**
     * Retorna um lock atômico Redis para o slot profissional+horário.
     * TTL de 15 s cobre o tempo máximo de processamento do store().
     * Use dentro de try/finally: $lock->release().
     */
    public function acquireLock(string $profissionalId, Carbon $inicio): Lock
    {
        $key = 'slot:'.$profissionalId.':'.$inicio->format('YmdHi');

        return Cache::lock($key, 15);
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

    /**
     * Próximos dias em que o profissional tem expediente (empresa ou individual).
     *
     * @return list<string> Datas no formato Y-m-d
     */
    public function diasFuncionamento(
        Profissional $profissional,
        Company $company,
        int $quantidade = 14,
        int $janelaDias = 90
    ): array {
        $dias = [];
        $cursor = now()->startOfDay();
        $limite = now()->addDays($janelaDias)->startOfDay();

        while (count($dias) < $quantidade && $cursor->lte($limite)) {
            if (! $this->diaBloqueado($profissional->id, $cursor)
                && $this->expedienteDoProfissional($profissional, $company, $cursor) !== null) {
                $dias[] = $cursor->format('Y-m-d');
            }
            $cursor->addDay();
        }

        return $dias;
    }
}
