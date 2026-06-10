<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\RelatorioSemanal;
use App\Models\Agendamento;
use App\Models\Company;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EnviarRelatorioSemanal extends Command
{
    protected $signature = 'relatorio:semanal';

    protected $description = 'Envia relatório semanal por email para o admin_empresa de cada empresa ativa';

    public function handle(): int
    {
        $inicio = now()->subDays(6)->startOfDay();
        $fim = now()->endOfDay();

        $companies = Company::where('ativo', true)->get();
        $enviados = 0;

        foreach ($companies as $company) {
            $admin = User::where('empresa_id', $company->id)
                ->role('admin_empresa')
                ->whereNotNull('email')
                ->first();

            if (! $admin) {
                continue;
            }

            $base = Agendamento::where('company_id', $company->id)
                ->whereBetween('data_hora', [$inicio, $fim]);

            $total = (clone $base)->count();
            $finalizados = (clone $base)->where('status', Agendamento::STATUS_FINALIZADO)->get();
            $receita = (float) $finalizados->sum('valor');
            $ticketMedio = $finalizados->count() > 0 ? $receita / $finalizados->count() : 0.0;

            $topServico = (clone $base)
                ->where('status', Agendamento::STATUS_FINALIZADO)
                ->with('servico:id,nome')
                ->selectRaw('servico_id, COUNT(*) as qtd')
                ->groupBy('servico_id')
                ->orderByDesc('qtd')
                ->first()?->servico?->nome;

            $topProfissional = (clone $base)
                ->where('status', Agendamento::STATUS_FINALIZADO)
                ->with('profissional:id,name')
                ->selectRaw('profissional_id, COUNT(*) as qtd')
                ->groupBy('profissional_id')
                ->orderByDesc('qtd')
                ->first()?->profissional?->name;

            $stats = [
                'total' => $total,
                'finalizados' => $finalizados->count(),
                'receita' => $receita,
                'ticket_medio' => $ticketMedio,
                'top_servico' => $topServico,
                'top_profissional' => $topProfissional,
            ];

            Mail::to($admin->email)->queue(new RelatorioSemanal($company, $stats));
            $enviados++;
        }

        $this->info("Relatórios enviados para {$enviados} empresa".($enviados !== 1 ? 's' : '').'.');

        return self::SUCCESS;
    }
}
