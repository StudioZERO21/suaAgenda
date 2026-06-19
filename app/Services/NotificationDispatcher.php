<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\AgendamentoCancelado;
use App\Mail\AgendamentoConfirmado;
use App\Mail\AgendamentoLembrete;
use App\Mail\PagamentoConfirmado;
use App\Mail\RelatorioSemanal;
use App\Models\Agendamento;
use App\Models\Company;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Dispatches notifications based on per-event channel configuration (notifications_v2).
 */
final class NotificationDispatcher
{
    /**
     * Events delivered to the client (uses client email/phone).
     * Other events are delivered to the business owner (company email).
     */
    private const CLIENT_EVENTS = [
        'agendamento_confirmado',
        'agendamento_cancelado',
        'lembrete_24h',
        'lembrete_1h',
        'pagamento_confirmado',
    ];

    /**
     * Dispatch all enabled channels for a booking event.
     *
     * @param  array<string,mixed>  $context  Must contain 'agendamento' key for booking events
     */
    public static function dispatch(string $event, Company $company, array $context = []): void
    {
        $settings = $company->resolvedSettings();
        $channels = $settings['notifications_v2'][$event] ?? [];

        if (empty(array_filter($channels))) {
            return;
        }

        foreach ($channels as $channel => $enabled) {
            if (! $enabled) {
                continue;
            }

            try {
                match ($channel) {
                    'email' => self::dispatchEmail($event, $company, $context),
                    'whatsapp' => self::dispatchWhatsApp($event, $company, $context, $settings),
                    'sms' => null, // future: Twilio SMS
                    default => null,
                };
            } catch (\Throwable $e) {
                Log::error("NotificationDispatcher [{$event}/{$channel}] failed", [
                    'company_id' => $company->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private static function dispatchEmail(string $event, Company $company, array $context): void
    {
        /** @var Agendamento|null $agendamento */
        $agendamento = $context['agendamento'] ?? null;

        $mailable = self::buildMailable($event, $agendamento, $company, $context);

        if (! $mailable) {
            return;
        }

        $to = in_array($event, self::CLIENT_EVENTS)
            ? ($agendamento?->cliente?->email ?? null)
            : ($company->email ?? null);

        if (! $to) {
            return;
        }

        Mail::to($to)->queue($mailable);
    }

    private static function dispatchWhatsApp(
        string $event,
        Company $company,
        array $context,
        array $settings,
    ): void {
        $waConfig = $settings['integrations']['whatsapp'] ?? [];

        if (empty($waConfig['ativo']) || empty($waConfig['twilio_sid'])) {
            return;
        }

        /** @var Agendamento|null $agendamento */
        $agendamento = $context['agendamento'] ?? null;

        $to = in_array($event, self::CLIENT_EVENTS)
            ? ($agendamento?->cliente?->phone ?? null)
            : ($company->whatsapp ?? null);

        if (! $to) {
            return;
        }

        $message = self::buildWhatsAppMessage($event, $agendamento, $company, $context);

        if (! $message) {
            return;
        }

        WhatsAppService::enviar($waConfig, $to, $message);
    }

    private static function buildMailable(
        string $event,
        ?Agendamento $agendamento,
        Company $company,
        array $context,
    ): ?Mailable {
        if ($event === 'relatorio_semanal') {
            return new RelatorioSemanal($company, $context['stats'] ?? []);
        }

        if (! $agendamento) {
            return null;
        }

        return match ($event) {
            'agendamento_confirmado' => new AgendamentoConfirmado($agendamento),
            'agendamento_cancelado' => new AgendamentoCancelado($agendamento),
            'lembrete_24h',
            'lembrete_1h' => new AgendamentoLembrete($agendamento),
            'pagamento_confirmado' => new PagamentoConfirmado($agendamento),
            default => null,
        };
    }

    private static function buildWhatsAppMessage(
        string $event,
        ?Agendamento $agendamento,
        Company $company,
        array $context,
    ): ?string {
        $cliente = $agendamento?->cliente?->name ?? 'Cliente';
        $data = $agendamento?->data_hora->format('d/m/Y \à\s H:i') ?? '';
        $servico = $agendamento?->servico?->nome ?? '';
        $profissional = $agendamento?->profissional?->name ?? '';
        $empresa = $company->name;

        return match ($event) {
            'agendamento_confirmado' => "✅ *Agendamento confirmado!*\n\nOlá {$cliente}!\n\n📅 {$data}\n💈 {$servico}\n👤 {$profissional}\n\n_{$empresa}_",
            'agendamento_cancelado' => "❌ *Agendamento cancelado*\n\nOlá {$cliente},\n\nSeu agendamento de {$data} foi cancelado. Entre em contato para reagendar.\n\n_{$empresa}_",
            'lembrete_24h' => "⏰ *Lembrete — amanhã!*\n\nOlá {$cliente}!\n\nAmanhã você tem:\n📅 {$data}\n💈 {$servico}\n👤 {$profissional}\n\n_{$empresa}_",
            'lembrete_1h' => "⏰ *Em 1 hora!*\n\nOlá {$cliente}, seu serviço começa em 1 hora:\n📅 {$data}\n💈 {$servico}\n\n_{$empresa}_",
            'pagamento_confirmado' => "✅ *Pagamento confirmado!*\n\nOlá {$cliente}, seu pagamento foi recebido com sucesso.\n\n_{$empresa}_",
            default => null,
        };
    }
}
