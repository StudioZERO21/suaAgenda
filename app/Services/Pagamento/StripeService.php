<?php

declare(strict_types=1);

namespace App\Services\Pagamento;

use Illuminate\Support\Facades\Http;

class StripeService
{
    private const BASE = 'https://api.stripe.com/v1';

    /**
     * Testa se a secret key é válida.
     *
     * @return array{ok: bool, nome?: string, erro?: string}
     */
    public static function testarCredenciais(string $secretKey): array
    {
        if (trim($secretKey) === '') {
            return ['ok' => false, 'erro' => 'Secret Key não configurada.'];
        }

        try {
            $resp = Http::timeout(8)
                ->withBasicAuth($secretKey, '')
                ->get(self::BASE.'/account');

            if ($resp->successful()) {
                $nome = $resp->json('business_profile.name')
                    ?? $resp->json('email')
                    ?? 'Conta Stripe ativa';

                return ['ok' => true, 'nome' => $nome];
            }

            return ['ok' => false, 'erro' => 'Secret Key inválida (HTTP '.$resp->status().')'];
        } catch (\Exception $e) {
            return ['ok' => false, 'erro' => $e->getMessage()];
        }
    }

    /**
     * Cria um Payment Link e retorna a URL.
     */
    public static function criarLink(
        string $secretKey,
        float $valor,
        string $descricao,
        string $moeda = 'brl'
    ): string {
        // Cria price inline
        $priceResp = Http::timeout(15)
            ->withBasicAuth($secretKey, '')
            ->asForm()
            ->post(self::BASE.'/prices', [
                'unit_amount' => (int) round($valor * 100),
                'currency' => $moeda,
                'product_data[name]' => $descricao,
            ]);

        $priceId = $priceResp->json('id');
        if (! $priceId) {
            return '';
        }

        $linkResp = Http::timeout(15)
            ->withBasicAuth($secretKey, '')
            ->asForm()
            ->post(self::BASE.'/payment_links', [
                'line_items[0][price]' => $priceId,
                'line_items[0][quantity]' => 1,
            ]);

        return $linkResp->json('url') ?? '';
    }
}
