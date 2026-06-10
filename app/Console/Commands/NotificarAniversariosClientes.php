<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\Notificacao;
use Illuminate\Console\Command;

class NotificarAniversariosClientes extends Command
{
    protected $signature = 'clientes:aniversarios';

    protected $description = 'Cria notificações para aniversariantes do dia por empresa';

    public function handle(): int
    {
        $hoje = now();
        $mes = $hoje->month;
        $dia = $hoje->day;

        $aniversariantes = Cliente::whereNotNull('data_nasc')
            ->where('ativo', true)
            ->whereMonth('data_nasc', $mes)
            ->whereDay('data_nasc', $dia)
            ->get();

        $porEmpresa = $aniversariantes->groupBy('company_id');

        $total = 0;

        foreach ($porEmpresa as $companyId => $clientes) {
            $nomes = $clientes->pluck('name')->join(', ');
            $count = $clientes->count();

            Notificacao::create([
                'company_id' => $companyId,
                'tipo' => 'aniversario',
                'titulo' => "🎂 {$count} aniversariante".($count > 1 ? 's' : '').' hoje',
                'mensagem' => $nomes,
            ]);

            $total += $count;
        }

        $this->info("Notificações criadas para {$total} aniversariante".($total !== 1 ? 's' : '').'.');

        return self::SUCCESS;
    }
}
