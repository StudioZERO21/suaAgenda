<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class AgendamentoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'barbearia-teste')->firstOrFail();

        $profissionais = Profissional::where('company_id', $company->id)->get()->keyBy('name');
        $carlos = $profissionais['Carlos Silva'];
        $joao = $profissionais['João Barbeiro'];

        $servicos = Servico::where('company_id', $company->id)->get()->keyBy('nome');

        $clienteIds = Cliente::where('company_id', $company->id)->pluck('id')->toArray();

        if (empty($clienteIds)) {
            $this->command->warn('Nenhum cliente encontrado — execute ClienteSeeder primeiro.');

            return;
        }

        // ── Passado: últimos 45 dias (finalizado / cancelado) ───────
        $this->command->info('Criando agendamentos passados...');
        $passados = $this->agendamentosPassados($company, $carlos, $joao, $servicos, $clienteIds);

        // ── Semana atual: confirmados e pendentes ────────────────────
        $this->command->info('Criando agendamentos da semana atual...');
        $atuais = $this->agendamentosAtuais($company, $carlos, $joao, $servicos, $clienteIds);

        // ── Próximas 2 semanas: pendentes ────────────────────────────
        $this->command->info('Criando agendamentos futuros...');
        $futuros = $this->agendamentosFuturos($company, $carlos, $joao, $servicos, $clienteIds);

        $total = count($passados) + count($atuais) + count($futuros);
        $this->command->info("✓ {$total} agendamentos criados para Barbearia Teste.");
    }

    /** @return array<int, Agendamento> */
    private function agendamentosPassados(
        Company $company,
        Profissional $carlos,
        Profissional $joao,
        Collection $servicos,
        array $clienteIds
    ): array {
        $criados = [];

        // Define blocos de agenda passados: dia, hora, profissional, serviço, status
        $blocos = [
            // Hoje - 1
            ['dias' => -1, 'hora' => '09:00', 'prof' => $carlos, 'servico' => 'Corte',         'status' => 'finalizado', 'obs' => null],
            ['dias' => -1, 'hora' => '09:30', 'prof' => $joao,   'servico' => 'Barba',          'status' => 'finalizado', 'obs' => null],
            ['dias' => -1, 'hora' => '10:30', 'prof' => $carlos, 'servico' => 'Corte + Barba',  'status' => 'finalizado', 'obs' => 'Cliente VIP'],
            ['dias' => -1, 'hora' => '14:00', 'prof' => $joao,   'servico' => 'Hidratação',     'status' => 'finalizado', 'obs' => null],
            ['dias' => -1, 'hora' => '15:30', 'prof' => $carlos, 'servico' => 'Barba',          'status' => 'cancelado',  'obs' => 'Cancelado pelo cliente'],
            // Hoje - 2
            ['dias' => -2, 'hora' => '09:00', 'prof' => $joao,   'servico' => 'Coloração',      'status' => 'finalizado', 'obs' => null],
            ['dias' => -2, 'hora' => '11:00', 'prof' => $carlos, 'servico' => 'Corte',          'status' => 'finalizado', 'obs' => null],
            ['dias' => -2, 'hora' => '14:30', 'prof' => $joao,   'servico' => 'Corte + Barba',  'status' => 'finalizado', 'obs' => null],
            ['dias' => -2, 'hora' => '16:00', 'prof' => $carlos, 'servico' => 'Barba + Bigode', 'status' => 'finalizado', 'obs' => null],
            // Hoje - 4
            ['dias' => -4, 'hora' => '09:30', 'prof' => $carlos, 'servico' => 'Corte',          'status' => 'finalizado', 'obs' => null],
            ['dias' => -4, 'hora' => '10:00', 'prof' => $joao,   'servico' => 'Barba',          'status' => 'finalizado', 'obs' => null],
            ['dias' => -4, 'hora' => '11:30', 'prof' => $carlos, 'servico' => 'Corte + Barba',  'status' => 'finalizado', 'obs' => null],
            ['dias' => -4, 'hora' => '14:00', 'prof' => $joao,   'servico' => 'Hidratação',     'status' => 'cancelado',  'obs' => 'Não compareceu'],
            ['dias' => -4, 'hora' => '15:00', 'prof' => $carlos, 'servico' => 'Corte',          'status' => 'finalizado', 'obs' => null],
            // Hoje - 7
            ['dias' => -7, 'hora' => '09:00', 'prof' => $joao,   'servico' => 'Coloração',      'status' => 'finalizado', 'obs' => null],
            ['dias' => -7, 'hora' => '11:00', 'prof' => $carlos, 'servico' => 'Barba',          'status' => 'finalizado', 'obs' => null],
            ['dias' => -7, 'hora' => '13:30', 'prof' => $joao,   'servico' => 'Corte',          'status' => 'finalizado', 'obs' => null],
            ['dias' => -7, 'hora' => '15:00', 'prof' => $carlos, 'servico' => 'Corte + Barba',  'status' => 'finalizado', 'obs' => null],
            // Hoje - 9
            ['dias' => -9, 'hora' => '09:30', 'prof' => $carlos, 'servico' => 'Corte',          'status' => 'finalizado', 'obs' => null],
            ['dias' => -9, 'hora' => '10:30', 'prof' => $joao,   'servico' => 'Barba + Bigode', 'status' => 'finalizado', 'obs' => null],
            ['dias' => -9, 'hora' => '14:00', 'prof' => $carlos, 'servico' => 'Hidratação',     'status' => 'finalizado', 'obs' => null],
            ['dias' => -9, 'hora' => '16:00', 'prof' => $joao,   'servico' => 'Barba',          'status' => 'cancelado',  'obs' => null],
            // Hoje - 14
            ['dias' => -14, 'hora' => '09:00', 'prof' => $joao,  'servico' => 'Coloração',      'status' => 'finalizado', 'obs' => null],
            ['dias' => -14, 'hora' => '10:30', 'prof' => $carlos, 'servico' => 'Corte',         'status' => 'finalizado', 'obs' => null],
            ['dias' => -14, 'hora' => '11:30', 'prof' => $carlos, 'servico' => 'Barba',         'status' => 'finalizado', 'obs' => null],
            ['dias' => -14, 'hora' => '14:00', 'prof' => $joao,   'servico' => 'Corte + Barba', 'status' => 'finalizado', 'obs' => null],
            ['dias' => -14, 'hora' => '15:30', 'prof' => $carlos, 'servico' => 'Barba + Bigode', 'status' => 'finalizado', 'obs' => null],
            // Hoje - 18
            ['dias' => -18, 'hora' => '09:30', 'prof' => $carlos, 'servico' => 'Corte',         'status' => 'finalizado', 'obs' => null],
            ['dias' => -18, 'hora' => '10:00', 'prof' => $joao,   'servico' => 'Hidratação',    'status' => 'finalizado', 'obs' => null],
            ['dias' => -18, 'hora' => '14:00', 'prof' => $carlos, 'servico' => 'Corte + Barba', 'status' => 'cancelado',  'obs' => 'Reagendado'],
            // Hoje - 22
            ['dias' => -22, 'hora' => '09:00', 'prof' => $joao,   'servico' => 'Coloração',     'status' => 'finalizado', 'obs' => null],
            ['dias' => -22, 'hora' => '11:30', 'prof' => $carlos, 'servico' => 'Corte',         'status' => 'finalizado', 'obs' => null],
            ['dias' => -22, 'hora' => '13:00', 'prof' => $joao,   'servico' => 'Barba',         'status' => 'finalizado', 'obs' => null],
            ['dias' => -22, 'hora' => '15:00', 'prof' => $carlos, 'servico' => 'Barba + Bigode', 'status' => 'finalizado', 'obs' => null],
            // Hoje - 30
            ['dias' => -30, 'hora' => '09:00', 'prof' => $carlos, 'servico' => 'Corte',         'status' => 'finalizado', 'obs' => null],
            ['dias' => -30, 'hora' => '10:00', 'prof' => $joao,   'servico' => 'Corte + Barba', 'status' => 'finalizado', 'obs' => null],
            ['dias' => -30, 'hora' => '14:00', 'prof' => $carlos, 'servico' => 'Hidratação',    'status' => 'finalizado', 'obs' => null],
            ['dias' => -30, 'hora' => '15:30', 'prof' => $joao,   'servico' => 'Barba',         'status' => 'cancelado',  'obs' => null],
            // Hoje - 38
            ['dias' => -38, 'hora' => '10:00', 'prof' => $carlos, 'servico' => 'Corte',         'status' => 'finalizado', 'obs' => null],
            ['dias' => -38, 'hora' => '11:00', 'prof' => $joao,   'servico' => 'Coloração',     'status' => 'finalizado', 'obs' => null],
            ['dias' => -38, 'hora' => '14:30', 'prof' => $carlos, 'servico' => 'Barba',         'status' => 'finalizado', 'obs' => null],
            ['dias' => -38, 'hora' => '16:00', 'prof' => $joao,   'servico' => 'Barba + Bigode', 'status' => 'finalizado', 'obs' => null],
            // Hoje - 45
            ['dias' => -45, 'hora' => '09:30', 'prof' => $joao,   'servico' => 'Hidratação',    'status' => 'finalizado', 'obs' => null],
            ['dias' => -45, 'hora' => '11:00', 'prof' => $carlos, 'servico' => 'Corte + Barba', 'status' => 'finalizado', 'obs' => null],
            ['dias' => -45, 'hora' => '14:00', 'prof' => $joao,   'servico' => 'Corte',         'status' => 'finalizado', 'obs' => null],
        ];

        $clienteIndex = 0;

        foreach ($blocos as $bloco) {
            $servico = $servicos[$bloco['servico']];
            $dataHora = Carbon::now()->addDays($bloco['dias'])->setTimeFromTimeString($bloco['hora']);

            $clienteId = $clienteIds[$clienteIndex % count($clienteIds)];
            $clienteIndex++;

            $criados[] = Agendamento::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'profissional_id' => $bloco['prof']->id,
                    'cliente_id' => $clienteId,
                    'data_hora' => $dataHora,
                ],
                [
                    'servico_id' => $servico->id,
                    'duracao' => $servico->duracao_minutos,
                    'valor' => $servico->preco,
                    'status' => $bloco['status'],
                    'observacao' => $bloco['obs'],
                ]
            );
        }

        return $criados;
    }

    /** @return array<int, Agendamento> */
    private function agendamentosAtuais(
        Company $company,
        Profissional $carlos,
        Profissional $joao,
        Collection $servicos,
        array $clienteIds
    ): array {
        $criados = [];
        $clienteIndex = 8;

        $blocos = [
            ['dias' => 0,  'hora' => '09:00', 'prof' => $carlos, 'servico' => 'Corte',         'status' => 'confirmado', 'obs' => null],
            ['dias' => 0,  'hora' => '10:00', 'prof' => $joao,   'servico' => 'Barba',         'status' => 'confirmado', 'obs' => null],
            ['dias' => 0,  'hora' => '11:00', 'prof' => $carlos, 'servico' => 'Corte + Barba', 'status' => 'confirmado', 'obs' => null],
            ['dias' => 1,  'hora' => '09:30', 'prof' => $joao,   'servico' => 'Coloração',     'status' => 'confirmado', 'obs' => null],
            ['dias' => 1,  'hora' => '11:00', 'prof' => $carlos, 'servico' => 'Barba',         'status' => 'pendente',   'obs' => null],
            ['dias' => 1,  'hora' => '14:00', 'prof' => $joao,   'servico' => 'Hidratação',    'status' => 'pendente',   'obs' => null],
            ['dias' => 2,  'hora' => '10:00', 'prof' => $carlos, 'servico' => 'Corte',         'status' => 'pendente',   'obs' => null],
            ['dias' => 2,  'hora' => '14:30', 'prof' => $joao,   'servico' => 'Corte + Barba', 'status' => 'pendente',   'obs' => 'Primeiro atendimento'],
            ['dias' => 3,  'hora' => '09:00', 'prof' => $carlos, 'servico' => 'Barba + Bigode', 'status' => 'confirmado', 'obs' => null],
            ['dias' => 3,  'hora' => '10:30', 'prof' => $joao,   'servico' => 'Corte',         'status' => 'pendente',   'obs' => null],
            ['dias' => 4,  'hora' => '09:00', 'prof' => $carlos, 'servico' => 'Hidratação',    'status' => 'pendente',   'obs' => null],
            ['dias' => 4,  'hora' => '13:30', 'prof' => $joao,   'servico' => 'Barba',         'status' => 'pendente',   'obs' => null],
        ];

        foreach ($blocos as $bloco) {
            $servico = $servicos[$bloco['servico']];
            $dataHora = Carbon::now()->addDays($bloco['dias'])->setTimeFromTimeString($bloco['hora']);

            $clienteId = $clienteIds[$clienteIndex % count($clienteIds)];
            $clienteIndex++;

            $criados[] = Agendamento::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'profissional_id' => $bloco['prof']->id,
                    'cliente_id' => $clienteId,
                    'data_hora' => $dataHora,
                ],
                [
                    'servico_id' => $servico->id,
                    'duracao' => $servico->duracao_minutos,
                    'valor' => $servico->preco,
                    'status' => $bloco['status'],
                    'observacao' => $bloco['obs'],
                ]
            );
        }

        return $criados;
    }

    /** @return array<int, Agendamento> */
    private function agendamentosFuturos(
        Company $company,
        Profissional $carlos,
        Profissional $joao,
        Collection $servicos,
        array $clienteIds
    ): array {
        $criados = [];
        $clienteIndex = 3;

        $blocos = [
            ['dias' => 5,  'hora' => '09:00', 'prof' => $carlos, 'servico' => 'Corte',         'status' => 'pendente', 'obs' => null],
            ['dias' => 5,  'hora' => '10:30', 'prof' => $joao,   'servico' => 'Coloração',     'status' => 'pendente', 'obs' => null],
            ['dias' => 6,  'hora' => '09:30', 'prof' => $carlos, 'servico' => 'Corte + Barba', 'status' => 'pendente', 'obs' => null],
            ['dias' => 6,  'hora' => '14:00', 'prof' => $joao,   'servico' => 'Hidratação',    'status' => 'pendente', 'obs' => null],
            ['dias' => 7,  'hora' => '09:00', 'prof' => $carlos, 'servico' => 'Barba',         'status' => 'pendente', 'obs' => null],
            ['dias' => 7,  'hora' => '11:00', 'prof' => $joao,   'servico' => 'Corte',         'status' => 'pendente', 'obs' => null],
            ['dias' => 8,  'hora' => '10:00', 'prof' => $carlos, 'servico' => 'Barba + Bigode', 'status' => 'pendente', 'obs' => null],
            ['dias' => 8,  'hora' => '14:00', 'prof' => $joao,   'servico' => 'Barba',         'status' => 'pendente', 'obs' => null],
            ['dias' => 10, 'hora' => '09:30', 'prof' => $carlos, 'servico' => 'Corte',         'status' => 'pendente', 'obs' => null],
            ['dias' => 10, 'hora' => '11:00', 'prof' => $joao,   'servico' => 'Coloração',     'status' => 'pendente', 'obs' => null],
            ['dias' => 11, 'hora' => '09:00', 'prof' => $carlos, 'servico' => 'Corte + Barba', 'status' => 'pendente', 'obs' => 'Aniversário do cliente'],
            ['dias' => 12, 'hora' => '14:00', 'prof' => $joao,   'servico' => 'Hidratação',    'status' => 'pendente', 'obs' => null],
            ['dias' => 14, 'hora' => '09:30', 'prof' => $carlos, 'servico' => 'Corte',         'status' => 'pendente', 'obs' => null],
            ['dias' => 14, 'hora' => '11:00', 'prof' => $joao,   'servico' => 'Barba',         'status' => 'pendente', 'obs' => null],
        ];

        foreach ($blocos as $bloco) {
            $servico = $servicos[$bloco['servico']];
            $dataHora = Carbon::now()->addDays($bloco['dias'])->setTimeFromTimeString($bloco['hora']);

            $clienteId = $clienteIds[$clienteIndex % count($clienteIds)];
            $clienteIndex++;

            $criados[] = Agendamento::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'profissional_id' => $bloco['prof']->id,
                    'cliente_id' => $clienteId,
                    'data_hora' => $dataHora,
                ],
                [
                    'servico_id' => $servico->id,
                    'duracao' => $servico->duracao_minutos,
                    'valor' => $servico->preco,
                    'status' => $bloco['status'],
                    'observacao' => $bloco['obs'],
                ]
            );
        }

        return $criados;
    }
}
