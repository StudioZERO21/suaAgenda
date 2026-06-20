<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente HTTP para a Evolution API (WhatsApp Baileys multi-empresa).
 */
final class EvolutionService
{
    public const INSTANCIA_PLATAFORMA_PADRAO = 'plataforma';

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
     * Nome da instância Evolution usada pela plataforma (notificações fallback).
     */
    public function instanciaPlataforma(): string
    {
        return PlatformSetting::get('evolution', 'platform_instance')
            ?? self::INSTANCIA_PLATAFORMA_PADRAO;
    }

    /**
     * Gera o nome de instância único por empresa.
     */
    public static function nomeInstanciaEmpresa(string $companyId): string
    {
        return 'sa_'.substr(str_replace('-', '', $companyId), 0, 12);
    }

    /**
     * Cria instância Baileys e configura webhook (formato Evolution API v2).
     */
    public function criarInstancia(string $instanceName, string $webhookUrl): bool
    {
        if (! $this->configurado()) {
            return false;
        }

        $payload = [
            'instanceName' => $instanceName,
            'integration' => 'WHATSAPP-BAILEYS',
            'qrcode' => false,
            'webhook' => [
                'enabled' => true,
                'url' => $webhookUrl,
                'byEvents' => false,
                'base64' => false,
                'events' => ['MESSAGES_UPSERT', 'CONNECTION_UPDATE'],
            ],
        ];

        $resp = Http::timeout(15)
            ->withHeaders(['apikey' => $this->apiKey])
            ->post("{$this->baseUrl}/instance/create", $payload);

        if ($resp->successful() || $resp->status() === 409) {
            return $this->configurarWebhook($instanceName, $webhookUrl);
        }

        Log::warning('EvolutionService: falha ao criar instância', [
            'instance' => $instanceName,
            'status' => $resp->status(),
            'body' => $resp->body(),
        ]);

        return false;
    }

    /**
     * Atualiza webhook de uma instância existente.
     */
    public function configurarWebhook(string $instanceName, string $webhookUrl): bool
    {
        if (! $this->configurado()) {
            return false;
        }

        $resp = Http::timeout(10)
            ->withHeaders(['apikey' => $this->apiKey])
            ->post("{$this->baseUrl}/webhook/set/{$instanceName}", [
                'webhook' => [
                    'enabled' => true,
                    'url' => $webhookUrl,
                    'byEvents' => false,
                    'base64' => false,
                    'events' => ['MESSAGES_UPSERT', 'CONNECTION_UPDATE'],
                ],
            ]);

        return $resp->successful();
    }

    /**
     * Provisiona instância da plataforma para notificações globais (sem empresa).
     */
    public function provisionarInstanciaPlataforma(): array
    {
        if (! $this->configurado()) {
            return ['ok' => false, 'erro' => 'Evolution API não configurada.'];
        }

        $instance = $this->instanciaPlataforma();
        $webhookUrl = route('webhooks.evolution.inbound', ['instanceName' => $instance]);

        if (! $this->criarInstancia($instance, $webhookUrl)) {
            return ['ok' => false, 'erro' => 'Não foi possível criar a instância plataforma.'];
        }

        PlatformSetting::set('evolution', 'platform_instance', $instance);
        PlatformSetting::clearCache();

        $qr = $this->obterQrCode($instance);
        $status = $this->status($instance);

        return [
            'ok' => true,
            'instance' => $instance,
            'status' => $status,
            'qr' => $qr,
        ];
    }

    /**
     * Verifica se a instância da plataforma está conectada.
     */
    public function plataformaConectada(): bool
    {
        return $this->status($this->instanciaPlataforma()) === 'open';
    }

    /**
     * Obtém QR code base64 para escanear.
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
     * Status da conexão: open | close | connecting | not_configured.
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
     * Desconecta e remove instância no Evolution.
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
     * Envia mensagem de texto via instância Evolution.
     */
    public function enviarTexto(string $instanceName, string $numero, string $mensagem): bool
    {
        if (! $this->configurado()) {
            return false;
        }

        $digits = preg_replace('/\D/', '', $numero) ?? '';
        if (strlen($digits) <= 11) {
            $digits = '55'.$digits;
        }

        try {
            $resp = Http::timeout(15)
                ->withHeaders(['apikey' => $this->apiKey])
                ->post("{$this->baseUrl}/message/sendText/{$instanceName}", [
                    'number' => $digits,
                    'text' => $mensagem,
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

    /**
     * Testa conectividade com o servidor Evolution.
     *
     * @return array{ok: bool, nome?: string, erro?: string}
     */
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
                $json = $resp->json();
                $count = is_array($json) ? (isset($json[0]) ? count($json) : 1) : 0;

                return ['ok' => true, 'nome' => "{$count} instância(s) encontrada(s)"];
            }

            return ['ok' => false, 'erro' => 'HTTP '.$resp->status().' — verifique URL e API key'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'erro' => $e->getMessage()];
        }
    }
}
