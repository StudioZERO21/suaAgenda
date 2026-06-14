<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\BillingConfig;
use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Asaas wrapper for SaaS billing (distinct from the client-facing gateway in Pagamento/).
 * Handles customer creation and invoice (cobrança) generation for platform subscriptions.
 */
final class BillingGatewayService
{
    private string $apiKey;

    private string $baseUrl;

    public function __construct(private readonly BillingConfig $config)
    {
        $creds = $config->credentials ?? [];
        $this->apiKey = $creds['asaas_api_key'] ?? '';
        $ambiente = $creds['asaas_ambiente'] ?? 'sandbox';
        $this->baseUrl = $ambiente === 'producao'
            ? 'https://api.asaas.com/v3'
            : 'https://sandbox.asaas.com/api/v3';
    }

    public static function fromConfig(): self
    {
        return new self(BillingConfig::current());
    }

    /**
     * Create or retrieve a customer in Asaas for the given company.
     */
    public function ensureCustomer(Company $company): ?string
    {
        if (empty($this->apiKey)) {
            return null;
        }

        // Search existing customer by cpfCnpj or email
        $response = Http::withHeaders(['access_token' => $this->apiKey])
            ->get("{$this->baseUrl}/customers", ['email' => $company->email, 'limit' => 1]);

        if ($response->successful() && ! empty($response->json('data'))) {
            return $response->json('data.0.id');
        }

        // Create new customer
        $response = Http::withHeaders(['access_token' => $this->apiKey])
            ->post("{$this->baseUrl}/customers", [
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'externalReference' => $company->id,
                'notificationDisabled' => false,
            ]);

        if ($response->successful()) {
            return $response->json('id');
        }

        Log::error('BillingGatewayService: customer creation failed', [
            'company_id' => $company->id,
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        return null;
    }

    /**
     * Create a PIX charge in Asaas for a subscription invoice.
     *
     * @return array{id: string, url: string}|null
     */
    public function createCharge(
        string $customerId,
        float $amount,
        \DateTimeInterface $dueDate,
        string $description,
        string $externalRef,
    ): ?array {
        if (empty($this->apiKey)) {
            return null;
        }

        $response = Http::withHeaders(['access_token' => $this->apiKey])
            ->post("{$this->baseUrl}/payments", [
                'customer' => $customerId,
                'billingType' => 'PIX',
                'value' => $amount,
                'dueDate' => (new \DateTime($dueDate->format('Y-m-d')))->format('Y-m-d'),
                'description' => $description,
                'externalReference' => $externalRef,
            ]);

        if ($response->successful()) {
            return [
                'id' => $response->json('id'),
                'url' => $response->json('invoiceUrl') ?? $response->json('bankSlipUrl') ?? '',
            ];
        }

        Log::error('BillingGatewayService: charge creation failed', [
            'customer' => $customerId,
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        return null;
    }

    /**
     * Check payment status from Asaas.
     */
    public function getPaymentStatus(string $gatewayInvoiceId): ?string
    {
        if (empty($this->apiKey)) {
            return null;
        }

        $response = Http::withHeaders(['access_token' => $this->apiKey])
            ->get("{$this->baseUrl}/payments/{$gatewayInvoiceId}");

        if ($response->successful()) {
            return $response->json('status'); // PENDING, RECEIVED, CONFIRMED, OVERDUE, etc.
        }

        return null;
    }

    /**
     * Test that gateway credentials are valid.
     *
     * @return array{ok: bool, nome?: string, erro?: string}
     */
    public function testar(): array
    {
        if (empty($this->apiKey)) {
            return ['ok' => false, 'erro' => 'API Key não configurada'];
        }

        $response = Http::withHeaders(['access_token' => $this->apiKey])
            ->get("{$this->baseUrl}/myAccount");

        if ($response->successful()) {
            return ['ok' => true, 'nome' => $response->json('name') ?? 'Conta Asaas'];
        }

        return ['ok' => false, 'erro' => $response->json('errors.0.description') ?? "HTTP {$response->status()}"];
    }
}
