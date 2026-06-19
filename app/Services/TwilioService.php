<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Twilio platform-level service — credenciais globais do .env.
 *
 * Diferente de WhatsAppService (credenciais por empresa em settings JSON),
 * este serviço usa TWILIO_SID / TWILIO_TOKEN / TWILIO_* do ambiente da plataforma.
 * Suporta WhatsApp e SMS.
 */
final class TwilioService
{
    private const API = 'https://api.twilio.com/2010-04-01/Accounts';

    public function __construct(
        private readonly string $sid,
        private readonly string $token,
        private readonly string $whatsappNumber,
        private readonly string $smsNumber,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            sid: (string) config('services.twilio.sid', ''),
            token: (string) config('services.twilio.token', ''),
            whatsappNumber: (string) config('services.twilio.whatsapp_number', ''),
            smsNumber: (string) config('services.twilio.sms_number', ''),
        );
    }

    public function isConfigured(): bool
    {
        return $this->sid !== '' && $this->token !== '';
    }

    public function whatsappConfigured(): bool
    {
        return $this->isConfigured() && $this->whatsappNumber !== '';
    }

    public function smsConfigured(): bool
    {
        return $this->isConfigured() && $this->smsNumber !== '';
    }

    /**
     * Testa a conexão com a conta Twilio.
     *
     * @return array{ok: bool, friendly_name?: string, erro?: string}
     */
    public function testarConexao(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'erro' => 'TWILIO_SID e TWILIO_TOKEN não configurados no .env'];
        }

        try {
            $resp = Http::timeout(8)
                ->withBasicAuth($this->sid, $this->token)
                ->get(self::API."/{$this->sid}.json");

            if ($resp->successful()) {
                return [
                    'ok' => true,
                    'friendly_name' => $resp->json('friendly_name') ?? 'Conta Twilio ativa',
                ];
            }

            return ['ok' => false, 'erro' => 'Credenciais inválidas (HTTP '.$resp->status().')'];
        } catch (\Exception $e) {
            return ['ok' => false, 'erro' => $e->getMessage()];
        }
    }

    /**
     * Envia mensagem WhatsApp via Twilio Business API.
     *
     * @param  string  $to  Número do destinatário (com ou sem +55)
     */
    public function enviarWhatsApp(string $to, string $message): bool
    {
        if (! $this->whatsappConfigured()) {
            Log::warning('TwilioService::enviarWhatsApp — TWILIO_WHATSAPP_NUMBER não configurado');

            return false;
        }

        $dest = 'whatsapp:+'.$this->normalizar($to);
        $from = 'whatsapp:+'.$this->normalizar($this->whatsappNumber);

        return $this->enviarMensagem($from, $dest, $message);
    }

    /**
     * Envia SMS via Twilio.
     *
     * @param  string  $to  Número do destinatário (com ou sem +55)
     */
    public function enviarSms(string $to, string $message): bool
    {
        if (! $this->smsConfigured()) {
            Log::warning('TwilioService::enviarSms — TWILIO_SMS_NUMBER não configurado');

            return false;
        }

        $dest = '+'.$this->normalizar($to);
        $from = '+'.$this->normalizar($this->smsNumber);

        return $this->enviarMensagem($from, $dest, $message);
    }

    /**
     * Testa envio real de WhatsApp para um número específico.
     *
     * @return array{ok: bool, sid?: string, erro?: string}
     */
    public function testarWhatsApp(string $to): array
    {
        if (! $this->whatsappConfigured()) {
            return ['ok' => false, 'erro' => 'Número WhatsApp Twilio não configurado (TWILIO_WHATSAPP_NUMBER).'];
        }

        $dest = 'whatsapp:+'.$this->normalizar($to);
        $from = 'whatsapp:+'.$this->normalizar($this->whatsappNumber);

        try {
            $resp = Http::timeout(10)
                ->withBasicAuth($this->sid, $this->token)
                ->asForm()
                ->post(self::API."/{$this->sid}/Messages.json", [
                    'From' => $from,
                    'To' => $dest,
                    'Body' => '✅ Teste de notificação WhatsApp — suaAgenda.pro funcionando!',
                ]);

            if ($resp->successful()) {
                return ['ok' => true, 'sid' => $resp->json('sid') ?? ''];
            }

            $errMsg = $resp->json('message') ?? ('HTTP '.$resp->status());

            return ['ok' => false, 'erro' => $errMsg];
        } catch (\Exception $e) {
            return ['ok' => false, 'erro' => $e->getMessage()];
        }
    }

    /**
     * Testa envio real de SMS para um número específico.
     *
     * @return array{ok: bool, sid?: string, erro?: string}
     */
    public function testarSms(string $to): array
    {
        if (! $this->smsConfigured()) {
            return ['ok' => false, 'erro' => 'Número SMS Twilio não configurado (TWILIO_SMS_NUMBER).'];
        }

        $dest = '+'.$this->normalizar($to);
        $from = '+'.$this->normalizar($this->smsNumber);

        try {
            $resp = Http::timeout(10)
                ->withBasicAuth($this->sid, $this->token)
                ->asForm()
                ->post(self::API."/{$this->sid}/Messages.json", [
                    'From' => $from,
                    'To' => $dest,
                    'Body' => 'Teste SMS suaAgenda.pro: tudo funcionando!',
                ]);

            if ($resp->successful()) {
                return ['ok' => true, 'sid' => $resp->json('sid') ?? ''];
            }

            $errMsg = $resp->json('message') ?? ('HTTP '.$resp->status());

            return ['ok' => false, 'erro' => $errMsg];
        } catch (\Exception $e) {
            return ['ok' => false, 'erro' => $e->getMessage()];
        }
    }

    private function enviarMensagem(string $from, string $to, string $body): bool
    {
        try {
            $resp = Http::timeout(10)
                ->withBasicAuth($this->sid, $this->token)
                ->asForm()
                ->post(self::API."/{$this->sid}/Messages.json", [
                    'From' => $from,
                    'To' => $to,
                    'Body' => $body,
                ]);

            if (! $resp->successful()) {
                Log::warning('TwilioService: falha ao enviar', [
                    'from' => $from, 'to' => $to,
                    'status' => $resp->status(),
                    'body' => $resp->body(),
                ]);
            }

            return $resp->successful();
        } catch (\Exception $e) {
            Log::error('TwilioService: exceção ao enviar — '.$e->getMessage());

            return false;
        }
    }

    private function normalizar(string $numero): string
    {
        $digits = preg_replace('/\D/', '', $numero) ?? '';

        // Adiciona 55 (Brasil) se não tiver código de país
        if (strlen($digits) <= 11) {
            $digits = '55'.$digits;
        }

        return $digits;
    }
}
