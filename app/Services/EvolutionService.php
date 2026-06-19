<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class EvolutionService
{
    private string $baseUrl;

    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            PlatformSetting::get('evolution', 'api_url') ?? config('services.evolution.url', ''),
            '/'
        );
        $this->apiKey = PlatformSetting::get('evolution', 'api_key') ?? config('services.evolution.key', '');
    }

    public static function fromConfig(): self
    {
        return new self;
    }

    public function configurado(): bool
    {
        return $this->baseUrl !== '' && $this->apiKey !== '';
    }

    /**
     * Cria ou garante existência da instância para a empresa.
     * Retorna nome da instância criada.
     */
    public function criarInstancia(string $instanceName, string $webhookUrl): bool
    {
        if (! $this->configurado()) {
            return false;
        }

        $resp = Http::timeout(10)
            ->withHeaders(['apikey' => $this->apiKey])
            ->post("{$this->baseUrl}/instance/create", [
                'instanceName' => $instanceName,
                'integration' => 'WHATSAPP-BAILEYS',
                'webhook' => $webhookUrl,
                'webhook_by_events' => false,
                'events' => ['MESSAGES_UPSERT', 'CONNECTION_UPDATE'],
            ]);

        return $resp->successful() || $resp->status() === 409; // 409 = já existe
    }

    /**
     * Obtém o QR code base64 para escanear.
     * Retorna null se não disponível (já conectado ou erro).
     */
    public function obterQrCode(string $instanceName): ?string
    {
        if (! $this->configurado()) {
            return null;
        }

        $resp = Http::timeout(10)
            ->withHeaders(['apikey' => $this->apiKey])
            ->get("{$this->baseUrl}/instance/connect/{$instanceName}");

        if (! $resp->successful()) {
            return null;
        }

        return $resp->json('base64') ?? $resp->json('qrcode.base64');
    }

    /**
     * Retorna status da conexão: open | close | connecting.
     */
    public function status(string $instanceName): string
    {
        if (! $this->configurado()) {
            return 'not_configured';
        }

        try {
            $resp = Http::timeout(8)
                ->withHeaders(['apikey' => $this->apiKey])
                ->get("{$this->baseUrl}/instance/connectionState/{$instanceName}");

            if (! $resp->successful()) {
                return 'close';
            }

            return $resp->json('instance.state') ?? $resp->json('state') ?? 'close';
        } catch (\Throwable) {
            return 'close';
        }
    }

    /**
     * Desconecta e deleta a instância.
     */
    public function desconectar(string $instanceName): bool
    {
        if (! $this->configurado()) {
            return false;
        }

        Http::timeout(8)->withHeaders(['apikey' => $this->apiKey])
            ->delete("{$this->baseUrl}/instance/logout/{$instanceName}");

        Http::timeout(8)->withHeaders(['apikey' => $this->apiKey])
            ->delete("{$this->baseUrl}/instance/delete/{$instanceName}");

        return true;
    }

    /**
     * Envia mensagem de texto via WhatsApp da empresa.
     */
    public function enviarTexto(string $instanceName, string $numero, string $mensagem): bool
    {
        if (! $this->configurado()) {
            return false;
        }

        // Normaliza: apenas dígitos, garante DDI 55
        $digits = preg_replace('/\D/', '', $numero) ?? '';
        if (strlen($digits) <= 11) {
            $digits = '55'.$digits;
        }
        $jid = $digits.'@s.whatsapp.net';

        try {
            $resp = Http::timeout(15)
                ->withHeaders(['apikey' => $this->apiKey])
                ->post("{$this->baseUrl}/message/sendText/{$instanceName}", [
                    'number' => $jid,
                    'options' => ['delay' => 1000],
                    'textMessage' => ['text' => $mensagem],
                ]);

            if (! $resp->successful()) {
                Log::warning('EvolutionService: falha ao enviar', [
                    'instance' => $instanceName,
                    'status' => $resp->status(),
                    'body' => $resp->body(),
                ]);
            }

            return $resp->successful();
        } catch (\Throwable $e) {
            Log::error('EvolutionService: exception ao enviar', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function testarConexao(): array
    {
        if (! $this->configurado()) {
            return ['ok' => false, 'erro' => 'URL ou API key não configurados.'];
        }

        try {
            $resp = Http::timeout(8)
                ->withHeaders(['apikey' => $this->apiKey])
                ->get("{$this->baseUrl}/instance/fetchInstances");

            if ($resp->successful()) {
                $count = count($resp->json() ?? []);

                return ['ok' => true, 'nome' => "{$count} instâncias encontradas"];
            }

            return ['ok' => false, 'erro' => 'HTTP '.$resp->status().' — verifique URL e API key'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'erro' => $e->getMessage()];
        }
    }
}
