<?php

declare(strict_types=1);

namespace App\Services\Pagamento;

use Illuminate\Support\Facades\Http;

class MercadoPagoService
{
    private const BASE = 'https://api.mercadopago.com';

    /**
     * Testa se o access_token é válido.
     *
     * @return array{ok: bool, nome?: string, erro?: string}
     */
    public static function testarCredenciais(string $accessToken): array
    {
        if (trim($accessToken) === '') {
            return ['ok' => false, 'erro' => 'Access Token não configurado.'];
        }

        try {
            $resp = Http::timeout(8)
                ->withToken($accessToken)
                ->get(self::BASE.'/v1/payment_methods');

            if ($resp->successful()) {
                return ['ok' => true, 'nome' => 'Conta Mercado Pago ativa'];
            }

            return ['ok' => false, 'erro' => 'Access Token inválido (HTTP '.$resp->status().')'];
        } catch (\Exception $e) {
            return ['ok' => false, 'erro' => $e->getMessage()];
        }
    }

    /**
     * Cria preferência de pagamento e retorna o link de checkout.
     */
    public static function criarLink(
        string $accessToken,
        float $valor,
        string $descricao,
        string $referencia = ''
    ): string {
        $resp = Http::timeout(15)
            ->withToken($accessToken)
            ->post(self::BASE.'/checkout/preferences', [
                'items' => [[
                    'title' => $descricao,
                    'quantity' => 1,
                    'unit_price' => round($valor, 2),
                    'currency_id' => 'BRL',
                ]],
                'external_reference' => $referencia,
                'auto_return' => 'approved',
            ]);

        return $resp->json('init_point') ?? '';
    }
}
