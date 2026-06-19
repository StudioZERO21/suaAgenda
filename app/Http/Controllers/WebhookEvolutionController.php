<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\WhatsappConversa;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

final class WebhookEvolutionController extends Controller
{
    public function __invoke(Request $request, string $instanceName): Response
    {
        $event = $request->input('event');
        $data = $request->input('data', []);

        Log::debug('Evolution webhook', ['instance' => $instanceName, 'event' => $event]);

        if ($event === 'messages.upsert' || $event === 'MESSAGES_UPSERT') {
            $this->handleMessage($instanceName, $data);
        }

        return response('', 200);
    }

    private function handleMessage(string $instanceName, array $data): void
    {
        // Suporta estrutura de array ou objeto único
        $messages = isset($data[0]) ? $data : [$data];

        foreach ($messages as $msg) {
            // Ignora mensagens enviadas pelo próprio número (fromMe)
            if ($msg['key']['fromMe'] ?? false) {
                continue;
            }

            $sid = $msg['key']['id'] ?? null;
            $from = $msg['key']['remoteJid'] ?? null;
            $body = $msg['message']['conversation']
                ?? $msg['message']['extendedTextMessage']['text']
                ?? null;

            if (! $sid || ! $from || ! $body) {
                continue;
            }

            // Remove sufixo @s.whatsapp.net
            $fromNumber = preg_replace('/@.*$/', '', $from) ?? $from;

            // Encontra empresa pela instância
            $company = Company::where('evolution_instance', $instanceName)->first();

            // Deduplica por sid
            if (WhatsappConversa::where('twilio_sid', $sid)->exists()) {
                continue;
            }

            WhatsappConversa::create([
                'direction' => 'inbound',
                'from_number' => $fromNumber,
                'to_number' => $instanceName,
                'body' => $body,
                'twilio_sid' => $sid,
                'status' => 'received',
                'company_id' => $company?->id,
            ]);
        }
    }
}
