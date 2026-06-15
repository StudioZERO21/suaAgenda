<?php

declare(strict_types=1);

namespace App\Services\Pagamento;

use Illuminate\Support\Facades\Http;

class AsaasService
{
    private static function base(string $ambiente): string
    {
        return $ambiente === 'producao'
            ? 'https://api.asaas.com/v3'
            : 'https://sandbox.asaas.com/api/v3';
    }

    private static function headers(string $apiKey): array
    {
        return ['access_token' => $apiKey, 'Content-Type' => 'application/json'];
    }

    /**
     * Testa se a API key é válida.
     *
     * @return array{ok: bool, nome?: string, erro?: string}
     */
    public static function testarCredenciais(string $apiKey, string $ambiente = 'sandbox'): array
    {
        if (trim($apiKey) === '') {
            return ['ok' => false, 'erro' => 'API Key não configurada.'];
        }

        try {
            $resp = Http::timeout(8)
                ->withHeaders(self::headers($apiKey))
                ->get(self::base($ambiente).'/myAccount');

            if ($resp->successful()) {
                $nome = $resp->json('name') ?? 'Conta Asaas ativa';

                return ['ok' => true, 'nome' => $nome];
            }

            return ['ok' => false, 'erro' => 'API Key inválida (HTTP '.$resp->status().')'];
        } catch (\Exception $e) {
            return ['ok' => false, 'erro' => $e->getMessage()];
        }
    }

    /**
     * Cria cobrança Pix e retorna o link de pagamento.
     */
    public static function criarCobrancaPix(
        string $apiKey,
        string $ambiente,
        string $customerId,
        float $valor,
        string $descricao,
        string $referencia = ''
    ): string {
        $resp = Http::timeout(15)
            ->withHeaders(self::headers($apiKey))
            ->post(self::base($ambiente).'/payments', [
                'customer' => $customerId,
                'billingType' => 'PIX',
                'value' => round($valor, 2),
                'dueDate' => now()->addDays(1)->format('Y-m-d'),
                'description' => $descricao,
                'externalReference' => $referencia,
            ]);

        return $resp->json('invoiceUrl') ?? '';
    }

    /**
     * Cria ou busca cliente no Asaas pelo CPF/email.
     *
     * @return array{ok: bool, customer_id?: string, erro?: string}
     */
    public static function criarOuBuscarCliente(
        string $apiKey,
        string $ambiente,
        string $email,
        string $nome,
        string $cpfCnpj = ''
    ): array {
        try {
            // Buscar por email primeiro
            $busca = Http::timeout(8)
                ->withHeaders(self::headers($apiKey))
                ->get(self::base($ambiente).'/customers', ['email' => $email, 'limit' => 1]);

            if ($busca->successful() && count($busca->json('data') ?? []) > 0) {
                $id = $busca->json('data.0.id');

                return ['ok' => true, 'customer_id' => $id];
            }

            // Criar novo cliente
            $payload = ['name' => $nome, 'email' => $email, 'notificationDisabled' => false];
            if (trim($cpfCnpj) !== '') {
                $payload['cpfCnpj'] = preg_replace('/\D/', '', $cpfCnpj);
            }

            $criar = Http::timeout(10)
                ->withHeaders(self::headers($apiKey))
                ->post(self::base($ambiente).'/customers', $payload);

            if ($criar->successful()) {
                return ['ok' => true, 'customer_id' => $criar->json('id')];
            }

            return ['ok' => false, 'erro' => 'Erro ao criar cliente Asaas (HTTP '.$criar->status().')'];
        } catch (\Exception $e) {
            return ['ok' => false, 'erro' => $e->getMessage()];
        }
    }

    /**
     * Cria cobrança de sinal (depósito) com vencimento de 10 minutos.
     *
     * @return array{ok: bool, payment_id?: string, payment_url?: string, erro?: string}
     */
    public static function criarCobrancaSinal(
        string $apiKey,
        string $ambiente,
        string $customerId,
        float $valor,
        string $descricao,
        string $agendamentoId
    ): array {
        try {
            $resp = Http::timeout(15)
                ->withHeaders(self::headers($apiKey))
                ->post(self::base($ambiente).'/payments', [
                    'customer' => $customerId,
                    'billingType' => 'PIX',
                    'value' => round($valor, 2),
                    'dueDate' => now()->addMinutes(10)->format('Y-m-d'),
                    'description' => $descricao,
                    'externalReference' => 'sinal:'.$agendamentoId,
                ]);

            if ($resp->successful()) {
                return [
                    'ok' => true,
                    'payment_id' => $resp->json('id'),
                    'payment_url' => $resp->json('invoiceUrl') ?? '',
                ];
            }

            return ['ok' => false, 'erro' => 'Erro ao criar cobrança (HTTP '.$resp->status().')'];
        } catch (\Exception $e) {
            return ['ok' => false, 'erro' => $e->getMessage()];
        }
    }

    /**
     * Cria link de pagamento do saldo restante.
     *
     * @return array{ok: bool, payment_id?: string, payment_url?: string, erro?: string}
     */
    public static function criarCobrancaSaldo(
        string $apiKey,
        string $ambiente,
        string $customerId,
        float $valor,
        string $descricao,
        string $agendamentoId
    ): array {
        try {
            $resp = Http::timeout(15)
                ->withHeaders(self::headers($apiKey))
                ->post(self::base($ambiente).'/payments', [
                    'customer' => $customerId,
                    'billingType' => 'PIX',
                    'value' => round($valor, 2),
                    'dueDate' => now()->addDays(7)->format('Y-m-d'),
                    'description' => $descricao,
                    'externalReference' => 'saldo:'.$agendamentoId,
                ]);

            if ($resp->successful()) {
                return [
                    'ok' => true,
                    'payment_id' => $resp->json('id'),
                    'payment_url' => $resp->json('invoiceUrl') ?? '',
                ];
            }

            return ['ok' => false, 'erro' => 'Erro ao criar cobrança (HTTP '.$resp->status().')'];
        } catch (\Exception $e) {
            return ['ok' => false, 'erro' => $e->getMessage()];
        }
    }
}
