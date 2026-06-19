<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WhatsappConversa;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Recebe mensagens inbound do Twilio WhatsApp sandbox.
 * URL configurada em: Twilio Console → Messaging → Sandbox → "WHEN A MESSAGE COMES IN"
 */
final class WebhookTwilioInboundController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Log::channel('single')->info('Twilio inbound', $request->all());

        $sid = $request->input('MessageSid');
        $from = $this->normalizar($request->input('From', ''));
        $to = $this->normalizar($request->input('To', ''));
        $body = $request->input('Body', '');

        // Evita duplicatas por SID
        if ($sid && WhatsappConversa::where('twilio_sid', $sid)->exists()) {
            return $this->twiml();
        }

        WhatsappConversa::create([
            'direction' => 'inbound',
            'from_number' => $from,
            'to_number' => $to,
            'body' => $body,
            'twilio_sid' => $sid,
            'status' => 'received',
        ]);

        return $this->twiml();
    }

    private function normalizar(string $numero): string
    {
        // Remove prefixo "whatsapp:" do Twilio
        return preg_replace('/^whatsapp:/i', '', $numero) ?? $numero;
    }

    private function twiml(): Response
    {
        // Resposta vazia — não enviamos resposta automática por aqui
        return response('<?xml version="1.0" encoding="UTF-8"?><Response></Response>', 200)
            ->header('Content-Type', 'text/xml');
    }
}
