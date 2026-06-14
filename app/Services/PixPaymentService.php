<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use Piggly\Pix\Exceptions\InvalidPixKeyException;
use Piggly\Pix\Exceptions\InvalidPixKeyTypeException;
use Piggly\Pix\Parser;
use Piggly\Pix\StaticPayload;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

/**
 * Gera payload Pix estático e QR Code para cobranças no PDV.
 */
final class PixPaymentService
{
    /**
     * Monta QR Code e código copia-e-cola para a empresa autenticada.
     *
     * @return array{configured: bool, copy_paste?: string, qr_code?: string, message?: string}
     */
    public function generateForCompany(Company $company, float $amount, string $tid): array
    {
        $payments = $company->resolvedSettings()['payments'] ?? [];
        $key = trim((string) ($payments['pix_key'] ?? ''));
        $keyType = (string) ($payments['pix_key_type'] ?? Parser::KEY_TYPE_RANDOM);
        $city = trim((string) ($payments['pix_city'] ?? '')) ?: 'Brasil';

        if ($key === '') {
            return [
                'configured' => false,
                'message' => 'Configure a chave Pix em Configurações da Empresa.',
            ];
        }

        try {
            $payload = (new StaticPayload)
                ->setAmount(max($amount, 0.01))
                ->setTid($tid)
                ->setDescription('Venda PDV')
                ->setPixKey($keyType, $key)
                ->setMerchantName($company->name)
                ->setMerchantCity($city);

            $copyPaste = $payload->getPixCode();

            return [
                'configured' => true,
                'copy_paste' => $copyPaste,
                'qr_code' => (string) QrCode::format('svg')->size(220)->margin(1)->generate($copyPaste),
            ];
        } catch (InvalidPixKeyException|InvalidPixKeyTypeException $e) {
            return [
                'configured' => false,
                'message' => 'Chave Pix inválida. Revise em Configurações da Empresa.',
            ];
        } catch (Throwable) {
            return [
                'configured' => false,
                'message' => 'Não foi possível gerar o QR Code Pix.',
            ];
        }
    }
}
