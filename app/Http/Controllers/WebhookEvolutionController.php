<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\WhatsappDesconectado;
use App\Models\Company;
use App\Models\WhatsappConversa;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Recebe webhooks da Evolution API (mensagens e status de conexão).
 */
final class WebhookEvolutionController extends Controller
{
    public function __invoke(Request $request, string $instanceName): Response
    {
        $event = (string) $request->input('event', '');
        $data = $request->input('data', []);

        Log::debug('Evolution webhook', ['instance' => $instanceName, 'event' => $event]);

        if ($this->isMessageEvent($event)) {
            $this->handleMessage($instanceName, $data);
        }

        if ($this->isConnectionEvent($event)) {
            $this->handleConnectionUpdate($instanceName, $data);
        }

        return response('', 200);
    }

    private function isMessageEvent(string $event): bool
    {
        return in_array($event, ['messages.upsert', 'MESSAGES_UPSERT'], true);
    }

    private function isConnectionEvent(string $event): bool
    {
        return in_array($event, ['connection.update', 'CONNECTION_UPDATE'], true);
    }

    private function handleMessage(string $instanceName, array $data): void
    {
        $messages = isset($data[0]) ? $data : [$data];

        foreach ($messages as $msg) {
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

            $fromNumber = preg_replace('/@.*$/', '', $from) ?? $from;
            $company = Company::where('evolution_instance', $instanceName)->first();

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

    /**
     * Sincroniza status da empresa e envia e-mail se desconectou.
     */
    private function handleConnectionUpdate(string $instanceName, array $data): void
    {
        $company = Company::where('evolution_instance', $instanceName)->first();

        if (! $company) {
            return;
        }

        $state = $data['state'] ?? $data['status'] ?? null;
        if (! is_string($state)) {
            return;
        }

        $connected = $state === 'open';
        $wasConnected = (bool) $company->evolution_connected;

        $company->update([
            'evolution_connected' => $connected,
            'evolution_connected_at' => $connected ? now() : $company->evolution_connected_at,
        ]);

        if ($connected) {
            $this->limparAlertaDesconexao($company);

            return;
        }

        if ($wasConnected && in_array($state, ['close', 'connecting'], true)) {
            $this->notificarDesconexao($company);
        }
    }

    private function notificarDesconexao(Company $company): void
    {
        $settings = $company->settings ?? [];
        $notifiedAt = $settings['evolution']['disconnect_notified_at'] ?? null;

        // Evita spam: no máximo 1 e-mail a cada 6 horas por empresa
        if ($notifiedAt && now()->diffInHours(\Illuminate\Support\Carbon::parse($notifiedAt)) < 6) {
            return;
        }

        $email = $company->email;
        if (! $email) {
            return;
        }

        Mail::to($email)->queue(new WhatsappDesconectado($company));

        $settings['evolution']['disconnect_notified_at'] = now()->toIso8601String();
        $company->update(['settings' => $settings]);

        Log::info('WhatsApp desconectado: alerta enviado', [
            'company_id' => $company->id,
            'email' => $email,
        ]);
    }

    private function limparAlertaDesconexao(Company $company): void
    {
        $settings = $company->settings ?? [];
        if (! isset($settings['evolution']['disconnect_notified_at'])) {
            return;
        }

        unset($settings['evolution']['disconnect_notified_at']);
        $company->update(['settings' => $settings]);
    }
}
