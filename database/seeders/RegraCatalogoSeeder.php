<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\RegraCatalogo;
use Illuminate\Database\Seeder;

class RegraCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $regras = [
            [
                'codigo' => 'cancelamento_antecedencia',
                'nome' => 'Antecedência mínima de cancelamento',
                'descricao' => 'O cliente só pode cancelar o agendamento com a antecedência mínima configurada. Depois do prazo, o cancelamento é bloqueado.',
                'categoria' => 'Agendamentos',
                'params_schema' => [
                    ['key' => 'horas_min', 'label' => 'Horas mínimas de antecedência', 'type' => 'number', 'min' => 1, 'max' => 168],
                ],
                'params_default' => ['horas_min' => 24],
            ],
            [
                'codigo' => 'reagendamento',
                'nome' => 'Política de reagendamento',
                'descricao' => 'Prazo mínimo para reagendar e limite de reagendamentos por agendamento.',
                'categoria' => 'Agendamentos',
                'params_schema' => [
                    ['key' => 'horas_min', 'label' => 'Horas mínimas de antecedência', 'type' => 'number', 'min' => 1, 'max' => 168],
                    ['key' => 'max_por_agendamento', 'label' => 'Máximo de reagendamentos', 'type' => 'number', 'min' => 1, 'max' => 10],
                ],
                'params_default' => ['horas_min' => 12, 'max_por_agendamento' => 2],
            ],
            [
                'codigo' => 'sinal',
                'nome' => 'Sinal (depósito antecipado)',
                'descricao' => 'Percentual do valor cobrado como sinal na reserva e se ele é reembolsável em cancelamentos dentro do prazo.',
                'categoria' => 'Financeiro',
                'params_schema' => [
                    ['key' => 'percentual', 'label' => 'Percentual do sinal (%)', 'type' => 'number', 'min' => 0, 'max' => 100],
                    ['key' => 'reembolsavel', 'label' => 'Sinal reembolsável dentro do prazo', 'type' => 'boolean'],
                ],
                'params_default' => ['percentual' => 30, 'reembolsavel' => true],
            ],
            [
                'codigo' => 'no_show',
                'nome' => 'Bloqueio por não comparecimento',
                'descricao' => 'Bloqueia novos agendamentos online de clientes que faltaram (no-show) o número de vezes configurado.',
                'categoria' => 'Agendamentos',
                'params_schema' => [
                    ['key' => 'bloquear_apos', 'label' => 'Bloquear após N faltas', 'type' => 'number', 'min' => 1, 'max' => 10],
                ],
                'params_default' => ['bloquear_apos' => 3],
            ],
            [
                'codigo' => 'lgpd_retencao',
                'nome' => 'Retenção de dados (LGPD)',
                'descricao' => 'Anonimiza automaticamente clientes sem nenhuma atividade após o prazo configurado.',
                'categoria' => 'LGPD',
                'params_schema' => [
                    ['key' => 'meses', 'label' => 'Prazo de retenção (meses)', 'type' => 'number', 'min' => 6, 'max' => 120],
                ],
                'params_default' => ['meses' => 24],
            ],
        ];

        foreach ($regras as $regra) {
            RegraCatalogo::updateOrCreate(
                ['codigo' => $regra['codigo']],
                $regra,
            );
        }

        $this->command?->info('✓ Catálogo de regras de negócio semeado.');
    }
}
