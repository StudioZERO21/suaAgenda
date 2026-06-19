<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Agendamento;
use App\Models\BillingConfig;
use App\Models\Invoice;
use App\Services\NotificationDispatcher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Processes incoming Asaas webhook events and updates invoice/subscription state.
 */
final class PaymentWebhookService
{
    private const ASAAS_PAID_EVENTS = ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED'];

    private const ASAAS_OVERDUE_EVENTS = ['PAYMENT_OVERDUE'];

    private const ASAAS_CANCELLED_EVENTS = ['PAYMENT_DELETED', 'PAYMENT_REFUNDED', 'PAYMENT_CHARGEBACK_REQUESTED'];

    public function __construct(private readonly InvoiceService $invoiceService) {}

    public static function make(): self
    {
        return new self(
            new InvoiceService(new BillingGatewayService(BillingConfig::current())),
        );
    }

    /**
     * Handle an incoming Asaas webhook payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): void
    {
        $event = $payload['event'] ?? null;
        $payment = $payload['payment'] ?? [];
        $gatewayInvoiceId = $payment['id'] ?? null;
        $externalRef = $payment['externalReference'] ?? null;

        if (! $event || ! $gatewayInvoiceId) {
            return;
        }

        Log::info("PaymentWebhookService: received {$event}", [
            'gateway_id' => $gatewayInvoiceId,
            'external_ref' => $externalRef,
        ]);

        // Rota eventos de sinal/saldo de agendamento (externalRef = "sinal:{id}" ou "saldo:{id}")
        if ($externalRef && str_starts_with($externalRef, 'sinal:')) {
            $this->handleSinalEvent($event, $payment, substr($externalRef, 6));

            return;
        }

        if ($externalRef && str_starts_with($externalRef, 'saldo:')) {
            $this->handleSaldoEvent($event, $payment, substr($externalRef, 6));

            return;
        }

        $invoice = $this->resolveInvoice($gatewayInvoiceId, $externalRef);

        if (! $invoice) {
            Log::warning('PaymentWebhookService: invoice not found', [
                'gateway_id' => $gatewayInvoiceId,
                'external_ref' => $externalRef,
            ]);

            return;
        }

        if (in_array($event, self::ASAAS_PAID_EVENTS)) {
            $this->handlePaid($invoice, $payment);
        } elseif (in_array($event, self::ASAAS_OVERDUE_EVENTS)) {
            $this->handleOverdue($invoice);
        } elseif (in_array($event, self::ASAAS_CANCELLED_EVENTS)) {
            $this->handleCancelled($invoice, $event);
        }
    }

    private function handlePaid(Invoice $invoice, array $payment): void
    {
        if ($invoice->isPaid()) {
            return; // Idempotent — already processed
        }

        $paidAt = isset($payment['paymentDate'])
            ? Carbon::parse($payment['paymentDate'])
            : now();

        $method = strtolower($payment['billingType'] ?? 'pix');

        $this->invoiceService->markPaid($invoice, $method, $paidAt);

        Log::info('PaymentWebhookService: invoice marked paid', [
            'invoice_id' => $invoice->id,
            'method' => $method,
            'paid_at' => $paidAt->toISOString(),
        ]);
    }

    private function handleOverdue(Invoice $invoice): void
    {
        if ($invoice->status === Invoice::STATUS_OVERDUE) {
            return;
        }

        $invoice->update(['status' => Invoice::STATUS_OVERDUE]);

        Log::info('PaymentWebhookService: invoice marked overdue', ['invoice_id' => $invoice->id]);
    }

    private function handleCancelled(Invoice $invoice, string $event): void
    {
        if ($invoice->status === Invoice::STATUS_CANCELLED) {
            return;
        }

        $status = $event === 'PAYMENT_REFUNDED' ? Invoice::STATUS_REFUNDED : Invoice::STATUS_CANCELLED;
        $invoice->update(['status' => $status]);

        Log::info("PaymentWebhookService: invoice {$status}", ['invoice_id' => $invoice->id]);
    }

    private function handleSinalEvent(string $event, array $payment, string $agendamentoId): void
    {
        $agendamento = Agendamento::find($agendamentoId);

        if (! $agendamento) {
            Log::warning('PaymentWebhookService: agendamento não encontrado para sinal', ['id' => $agendamentoId]);

            return;
        }

        if (in_array($event, self::ASAAS_PAID_EVENTS)) {
            // Sinal pago → confirmar agendamento
            if ($agendamento->sinalPago()) {
                return; // idempotente
            }

            $paidAt = isset($payment['paymentDate']) ? Carbon::parse($payment['paymentDate']) : now();

            $agendamento->update([
                'status' => Agendamento::STATUS_CONFIRMADO,
                'sinal_status' => Agendamento::SINAL_PAGO,
                'sinal_pago_em' => $paidAt,
            ]);

            Log::info('PaymentWebhookService: sinal pago, agendamento confirmado', ['id' => $agendamentoId]);

            $agendamento->load(['cliente', 'profissional', 'servico', 'company']);
            NotificationDispatcher::dispatch('pagamento_confirmado', $agendamento->company, ['agendamento' => $agendamento]);
        } elseif (in_array($event, self::ASAAS_CANCELLED_EVENTS) || in_array($event, self::ASAAS_OVERDUE_EVENTS)) {
            // Sinal cancelado/vencido → liberar slot
            if ($agendamento->status === Agendamento::STATUS_AGUARDANDO_SINAL) {
                $agendamento->update([
                    'status' => Agendamento::STATUS_CANCELADO,
                    'sinal_status' => Agendamento::SINAL_EXPIRADO,
                ]);

                Log::info('PaymentWebhookService: sinal expirado, slot liberado', ['id' => $agendamentoId]);
            }
        }
    }

    private function handleSaldoEvent(string $event, array $payment, string $agendamentoId): void
    {
        if (! in_array($event, self::ASAAS_PAID_EVENTS)) {
            return;
        }

        $agendamento = Agendamento::find($agendamentoId);

        if (! $agendamento) {
            return;
        }

        // Saldo pago → finalizar agendamento (se já em atendimento) ou só registrar
        $paidAt = isset($payment['paymentDate']) ? Carbon::parse($payment['paymentDate']) : now();

        $agendamento->update([
            'sinal_status' => Agendamento::SINAL_PAGO,
            'sinal_pago_em' => $agendamento->sinal_pago_em ?? $paidAt,
            // valor total considerado pago
        ]);

        Log::info('PaymentWebhookService: saldo pago', ['id' => $agendamentoId]);
    }

    private function resolveInvoice(string $gatewayInvoiceId, ?string $externalRef): ?Invoice
    {
        // Try by gateway_invoice_id first (most precise)
        $invoice = Invoice::where('gateway_invoice_id', $gatewayInvoiceId)->first();

        if ($invoice) {
            return $invoice;
        }

        // Fallback: external reference = invoice UUID
        if ($externalRef) {
            return Invoice::find($externalRef);
        }

        return null;
    }
}
