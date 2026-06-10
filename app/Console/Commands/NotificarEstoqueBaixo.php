<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Notificacao;
use App\Models\Produto;
use Illuminate\Console\Command;

class NotificarEstoqueBaixo extends Command
{
    protected $signature = 'produtos:estoque-baixo';

    protected $description = 'Cria notificações para produtos com estoque abaixo do mínimo';

    public function handle(): int
    {
        $produtos = Produto::where('ativo', true)
            ->whereColumn('estoque', '<=', 'estoque_min')
            ->get();

        $porEmpresa = $produtos->groupBy('company_id');

        $total = 0;

        foreach ($porEmpresa as $companyId => $itens) {
            $count = $itens->count();
            $nomes = $itens->map(fn ($p) => "{$p->nome} ({$p->estoque} un.)")->join(', ');

            Notificacao::create([
                'company_id' => $companyId,
                'tipo' => 'estoque_baixo',
                'titulo' => "⚠️ {$count} produto".($count > 1 ? 's' : '').' com estoque baixo',
                'mensagem' => $nomes,
            ]);

            $total += $count;
        }

        $this->info("Notificações criadas para {$total} produto".($total !== 1 ? 's' : '').' com estoque baixo.');

        return self::SUCCESS;
    }
}
