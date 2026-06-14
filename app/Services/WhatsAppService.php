<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Agendamento;
use Illuminate\Support\Facades\Http;

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

    /**
     * Testa credenciais Twilio — retorna ['ok'=>bool, 'nome'|'erro'=>string].
     *
     * @param  array<string,string>  $config
     * @return array{ok: bool, nome?: string, erro?: string}
     */
    public static function testarCredenciais(array $config): array
    {
        $sid = trim($config['twilio_sid'] ?? '');
        $token = trim($config['twilio_token'] ?? '');

        if ($sid === '' || $token === '') {
            return ['ok' => false, 'erro' => 'Account SID e Auth Token são obrigatórios.'];
        }

        try {
            $resp = Http::timeout(8)
                ->withBasicAuth($sid, $token)
                ->get("https://api.twilio.com/2010-04-01/Accounts/{$sid}.json");

            if ($resp->successful()) {
                return ['ok' => true, 'nome' => $resp->json('friendly_name') ?? 'Conta ativa'];
            }

            return ['ok' => false, 'erro' => 'Credenciais inválidas (HTTP '.$resp->status().')'];
        } catch (\Exception $e) {
            return ['ok' => false, 'erro' => $e->getMessage()];
        }
    }

    /**
     * Envia mensagem via Twilio WhatsApp.
     *
     * @param  array<string,string>  $config  ['twilio_sid','twilio_token','twilio_numero']
     */
    public static function enviar(array $config, string $destinatario, string $mensagem): bool
    {
        $sid = trim($config['twilio_sid'] ?? '');
        $token = trim($config['twilio_token'] ?? '');
        $numero = trim($config['twilio_numero'] ?? '');

        if ($sid === '' || $token === '' || $numero === '') {
            return false;
        }

        $dest = 'whatsapp:+'.preg_replace('/\D/', '', $destinatario);
        $from = 'whatsapp:+'.preg_replace('/\D/', '', $numero);

        $resp = Http::timeout(10)
            ->withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => $dest,
                'Body' => $mensagem,
            ]);

        return $resp->successful();
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
