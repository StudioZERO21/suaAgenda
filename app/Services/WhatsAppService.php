<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Agendamento;

class WhatsAppService
{
    public static function link(string $phone, string $mensagem): string
    {
        $numero = preg_replace('/\D/', '', $phone);
        if (! str_starts_with($numero, '55')) {
            $numero = '55'.$numero;
        }

        return 'https://wa.me/'.$numero.'?text='.rawurlencode($mensagem);
    }

    public static function mensagemConfirmacao(Agendamento $ag): string
    {
        $data = $ag->data_hora->format('d/m/Y \à\s H:i');
        $servico = $ag->servico?->nome ?? 'Serviço';
        $empresa = $ag->company?->name ?? 'nossa empresa';
        $profissional = $ag->profissional?->name ?? '';

        $msg = "Olá! Seu agendamento foi confirmado! ✅\n\n";
        $msg .= "📅 *{$data}*\n";
        $msg .= "✂️ {$servico}";
        if ($profissional) {
            $msg .= " com {$profissional}";
        }
        $msg .= "\n\n{$empresa} aguarda você!";

        return $msg;
    }

    public static function mensagemLembrete(Agendamento $ag): string
    {
        $data = $ag->data_hora->format('d/m/Y \à\s H:i');
        $servico = $ag->servico?->nome ?? 'Serviço';
        $empresa = $ag->company?->name ?? 'nossa empresa';

        $msg = "Lembrete de agendamento! ⏰\n\n";
        $msg .= "📅 *{$data}*\n";
        $msg .= "✂️ {$servico}\n\n";
        $msg .= "Qualquer dúvida, entre em contato. {$empresa}";

        return $msg;
    }
}
