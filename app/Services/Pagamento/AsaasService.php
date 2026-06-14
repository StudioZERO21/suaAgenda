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
}
