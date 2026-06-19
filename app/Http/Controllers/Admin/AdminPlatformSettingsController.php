<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

final class AdminPlatformSettingsController extends Controller
{
    private const GROUPS = ['stripe', 'mercadopago', 'asaas', 'twilio', 'email'];

    public function index(): View
    {
        $settings = [];
        foreach (self::GROUPS as $group) {
            $settings[$group] = PlatformSetting::forGroup($group);
        }

        return view('admin.configuracoes.index', compact('settings'));
    }

    public function save(Request $request, string $group): RedirectResponse
    {
        if (! in_array($group, self::GROUPS, true)) {
            abort(404);
        }

        foreach ($request->input($group, []) as $key => $value) {
            PlatformSetting::set($group, (string) $key, $value !== '' ? (string) $value : null);
        }

        PlatformSetting::clearCache();

        return back()->with('success_'.$group, 'Configurações de '.ucfirst($group).' salvas com sucesso!');
    }

    public function testarStripe(): JsonResponse
    {
        $secret = PlatformSetting::get('stripe', 'secret')
            ?? config('services.stripe_platform.secret', '');

        if (! $secret) {
            return response()->json(['ok' => false, 'erro' => 'Secret key não configurado.']);
        }

        try {
            $resp = Http::timeout(8)->withBasicAuth($secret, '')->get('https://api.stripe.com/v1/account');

            if ($resp->successful()) {
                return response()->json([
                    'ok' => true,
                    'nome' => $resp->json('business_profile.name') ?? $resp->json('email') ?? 'Conta Stripe ativa',
                ]);
            }

            return response()->json(['ok' => false, 'erro' => $resp->json('error.message') ?? 'HTTP '.$resp->status()]);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'erro' => $e->getMessage()]);
        }
    }

    public function testarTwilio(): JsonResponse
    {
        // Força re-leitura do banco (sem cache) para pegar valor recém-salvo
        $sid = PlatformSetting::get('twilio', 'sid') ?? config('services.twilio.sid', '');
        $token = PlatformSetting::get('twilio', 'token') ?? config('services.twilio.token', '');

        if (! $sid || ! $token) {
            return response()->json(['ok' => false, 'erro' => 'SID e Token não configurados.']);
        }

        try {
            $resp = Http::timeout(8)->withBasicAuth($sid, $token)
                ->get("https://api.twilio.com/2010-04-01/Accounts/{$sid}.json");

            if ($resp->successful()) {
                return response()->json(['ok' => true, 'nome' => $resp->json('friendly_name') ?? 'Conta Twilio ativa']);
            }

            return response()->json(['ok' => false, 'erro' => 'Credenciais inválidas (HTTP '.$resp->status().')']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'erro' => $e->getMessage()]);
        }
    }

    public function testarEmail(): JsonResponse
    {
        $host = PlatformSetting::get('email', 'host') ?? config('mail.mailers.smtp.host', '');

        if (! $host) {
            return response()->json(['ok' => false, 'erro' => 'Host SMTP não configurado.']);
        }

        $port = (int) (PlatformSetting::get('email', 'port') ?? config('mail.mailers.smtp.port', 587));
        $connection = @fsockopen($host, $port, $errno, $errstr, 5);

        if ($connection) {
            fclose($connection);

            return response()->json(['ok' => true, 'nome' => "Conexão com {$host}:{$port} estabelecida"]);
        }

        return response()->json(['ok' => false, 'erro' => "Não foi possível conectar a {$host}:{$port} — {$errstr}"]);
    }

    public function testarMercadoPago(): JsonResponse
    {
        $token = PlatformSetting::get('mercadopago', 'access_token')
            ?? PlatformSetting::get('mercadopago', 'access_token_test')
            ?? config('services.mercadopago.access_token', '');

        if (! $token) {
            return response()->json(['ok' => false, 'erro' => 'Access Token não configurado.']);
        }

        try {
            $resp = Http::timeout(8)->withToken($token)->get('https://api.mercadopago.com/users/me');

            if ($resp->successful()) {
                return response()->json(['ok' => true, 'nome' => $resp->json('email') ?? 'Conta MP ativa']);
            }

            return response()->json(['ok' => false, 'erro' => 'Token inválido (HTTP '.$resp->status().')']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'erro' => $e->getMessage()]);
        }
    }
}
