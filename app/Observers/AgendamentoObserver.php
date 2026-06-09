<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Agendamento;
use App\Models\Notificacao;

class AgendamentoObserver
{
    public function created(Agendamento $agendamento): void
    {
        $cliente = $agendamento->cliente;
        $servico = $agendamento->servico;
        $prof = $agendamento->profissional;

        $msg = trim(implode(' — ', array_filter([
            $cliente?->name,
            $servico?->nome,
            $prof?->name ? 'com '.$prof->name : null,
            $agendamento->data_hora->format('d/m H:i'),
        ])));

        Notificacao::create([
            'company_id' => $agendamento->company_id,
            'tipo' => 'novo_agendamento',
            'titulo' => 'Novo agendamento',
            'mensagem' => $msg ?: 'Agendamento recebido.',
        ]);
    }

    public function updated(Agendamento $agendamento): void
    {
        if (! $agendamento->wasChanged('status')) {
            return;
        }

        $cliente = $agendamento->cliente;
        $hora = $agendamento->data_hora->format('d/m H:i');

        match ($agendamento->status) {
            Agendamento::STATUS_CANCELADO => Notificacao::create([
                'company_id' => $agendamento->company_id,
                'tipo' => 'cancelamento',
                'titulo' => 'Agendamento cancelado',
                'mensagem' => ($cliente?->name ?? 'Cliente')." cancelou o agendamento das {$hora}.",
            ]),
            Agendamento::STATUS_CONFIRMADO => Notificacao::create([
                'company_id' => $agendamento->company_id,
                'tipo' => 'confirmado',
                'titulo' => 'Agendamento confirmado',
                'mensagem' => ($cliente?->name ?? 'Cliente')." confirmou para {$hora}.",
            ]),
            default => null,
        };
    }
}
