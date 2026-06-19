<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Company;
use App\Services\Pagamento\GatewayFactory;
use App\Services\Pagamento\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookMercadoPagoController extends Controller
{
    private const MP_API = 'https://api.mercadopago.com';

    /**
     * Recebe notificações IPN/Webhook do Mercado Pago.
     *
     * Suporta:
     *  - IPN v2 (JSON body): {"type":"payment","data":{"id":"123"},"user_id":"456"}
     *  - IPN v1 (query): ?topic=payment&id=123
     */
    public function __invoke(Request $request): Response
    {
        $type = $request->input('type') ?? $request->input('topic');

        if ($type !== 'payment') {
            return response('', 200);
        }

        // Suporta ambos os formatos IPN
        $paymentId = (string) ($request->json('data.id') ?? $request->query('id') ?? '');
        $mpUserId = (string) ($request->json('user_id') ?? '');

        if ($paymentId === '') {
            return response('', 200);
        }

        Log::info('WebhookMercadoPago: recebido', ['payment_id' => $paymentId, 'user_id' => $mpUserId]);

        try {
            $this->processarPagamento($paymentId, $mpUserId);
        } catch (\Throwable $e) {
            Log::error('WebhookMercadoPago: erro ao processar', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
        }

        // MP exige sempre 200 — erros internos não devem resultar em retry
        return response('', 200);
    }

    private function processarPagamento(string $paymentId, string $mpUserId): void
    {
        $token = $this->resolveToken($mpUserId);

        if ($token === '') {
            Log::warning('WebhookMercadoPago: nenhum token MP disponível', ['user_id' => $mpUserId]);

            return;
        }

        $resp = Http::timeout(10)
            ->withToken($token)
            ->get(self::MP_API.'/v1/payments/'.$paymentId);

        if (! $resp->successful()) {
            Log::warning('WebhookMercadoPago: falha ao buscar pagamento', [
                'payment_id' => $paymentId,
                'http_status' => $resp->status(),
            ]);

            return;
        }

        $payment = $resp->json();
        $status = $payment['status'] ?? null;
        $externalRef = (string) ($payment['external_reference'] ?? '');

        if ($externalRef === '') {
            return;
        }

        // external_reference = agendamento UUID (formato sinal) ou "saldo:{uuid}"
        $tipo = 'sinal';
        $agendamentoId = $externalRef;

        if (str_starts_with($externalRef, 'saldo:')) {
            $tipo = 'saldo';
            $agendamentoId = substr($externalRef, 6);
        }

        $agendamento = Agendamento::find($agendamentoId);

        if (! $agendamento) {
            Log::warning('WebhookMercadoPago: agendamento não encontrado', ['external_ref' => $externalRef]);

            return;
        }

        if ($status === 'approved') {
            $this->marcarPago($agendamento, $tipo, $paymentId);
        } elseif (in_array($status, ['cancelled', 'rejected', 'refunded', 'charged_back'], true)) {
            $this->marcarCancelado($agendamento, $tipo, $status, $paymentId);
        }
    }

    /**
     * Resolve o token MP a usar: busca empresa pelo mp_user_id ou usa token da plataforma.
     */
    private function resolveToken(string $mpUserId): string
    {
        if ($mpUserId !== '') {
            $company = Company::whereRaw(
                "JSON_UNQUOTE(JSON_EXTRACT(settings, '$.integrations.mercadopago.mp_user_id')) = ?",
                [$mpUserId]
            )->first();

            if ($company) {
                $integrations = $company->resolvedSettings()['integrations'] ?? [];

                return GatewayFactory::getMpAccessToken($integrations);
            }
        }

        // Fallback: token da plataforma (sandbox ou produção)
        return MercadoPagoService::getPlatformAccessToken();
    }

    private function marcarPago(Agendamento $agendamento, string $tipo, string $paymentId): void
    {
        if ($tipo === 'sinal') {
            if ($agendamento->sinalPago()) {
                return; // idempotente
            }

            $agendamento->update([
                'status' => Agendamento::STATUS_CONFIRMADO,
                'sinal_status' => Agendamento::SINAL_PAGO,
                'sinal_pago_em' => now(),
                'sinal_payment_id' => $paymentId,
            ]);

            Log::info('WebhookMercadoPago: sinal pago — agendamento confirmado', [
                'agendamento_id' => $agendamento->id,
                'payment_id' => $paymentId,
            ]);
        } else {
            // saldo
            $agendamento->update([
                'sinal_status' => Agendamento::SINAL_PAGO,
                'sinal_pago_em' => $agendamento->sinal_pago_em ?? now(),
            ]);

            Log::info('WebhookMercadoPago: saldo pago', [
                'agendamento_id' => $agendamento->id,
                'payment_id' => $paymentId,
            ]);
        }
    }

    private function marcarCancelado(Agendamento $agendamento, string $tipo, string $status, string $paymentId): void
    {
        if ($tipo === 'sinal' && $agendamento->status === Agendamento::STATUS_AGUARDANDO_SINAL) {
            $agendamento->update([
                'status' => Agendamento::STATUS_CANCELADO,
                'sinal_status' => Agendamento::SINAL_EXPIRADO,
            ]);

            Log::info('WebhookMercadoPago: pagamento '.$status.' — slot liberado', [
                'agendamento_id' => $agendamento->id,
                'payment_id' => $paymentId,
            ]);
        }
    }
}
