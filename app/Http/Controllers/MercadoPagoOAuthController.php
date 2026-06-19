<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\Pagamento\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class MercadoPagoOAuthController extends Controller
{
    /**
     * Inicia o fluxo OAuth — redireciona para a página de autorização do Mercado Pago.
     */
    public function redirect(): RedirectResponse
    {
        $company = Company::findOrFail(auth()->user()->empresa_id);
        $this->authorize('update', $company);

        if (! MercadoPagoService::isOAuthConfigured()) {
            return $this->redirectWithError(
                'OAuth Mercado Pago não configurado na plataforma. Contate o suporte.'
            );
        }

        $redirectUri = MercadoPagoService::getRedirectUri();
        $currentOrigin = request()->getSchemeAndHttpHost();
        $redirectOrigin = parse_url($redirectUri, PHP_URL_SCHEME).'://'.parse_url($redirectUri, PHP_URL_HOST);

        if ($currentOrigin !== $redirectOrigin) {
            return $this->redirectWithError(
                'Acesse o sistema pelo mesmo endereço do callback OAuth ('.$redirectOrigin.') antes de conectar.'
            );
        }

        $state = bin2hex(random_bytes(24));
        Session::put('mp_oauth_state', $state);
        Session::put('mp_oauth_company', $company->id);

        $codeChallenge = null;
        if (config('services.mercadopago.pkce', true)) {
            $pkce = MercadoPagoService::generatePkce();
            Session::put('mp_oauth_pkce_verifier', $pkce['verifier']);
            $codeChallenge = $pkce['challenge'];
        }

        return redirect(MercadoPagoService::getAuthUrl($state, $codeChallenge));
    }

    /**
     * Callback OAuth — recebe o code do Mercado Pago e armazena os tokens criptografados.
     */
    public function callback(Request $request): RedirectResponse
    {
        $error = $request->query('error');
        if ($error) {
            return $this->redirectWithError('Autorização negada: '.$request->query('error_description', $error));
        }

        $state = (string) $request->query('state', '');
        $code = (string) $request->query('code', '');

        if (! $state || $state !== Session::get('mp_oauth_state')) {
            return $this->redirectWithError('Estado OAuth inválido. Tente novamente.');
        }

        $pkceVerifier = Session::pull('mp_oauth_pkce_verifier');
        Session::forget('mp_oauth_state');
        Session::forget('mp_oauth_company');

        if (! $code) {
            return $this->redirectWithError('Código de autorização não recebido.');
        }

        try {
            $tokens = MercadoPagoService::exchangeCode($code, is_string($pkceVerifier) ? $pkceVerifier : null);
            $info = MercadoPagoService::getAccountInfo($tokens['access_token']);

            $nome = trim(
                ($info['first_name'] ?? '').
                (isset($info['last_name']) ? ' '.$info['last_name'] : '')
            ) ?: ($info['name'] ?? 'Conta Mercado Pago');

            $company = Company::findOrFail(auth()->user()->empresa_id);
            $settings = $company->settings ?? [];

            $settings['integrations']['gateway'] = 'mercadopago';
            $settings['integrations']['mercadopago'] = [
                'connected' => true,
                'access_token_enc' => encrypt($tokens['access_token']),
                'refresh_token_enc' => isset($tokens['refresh_token']) ? encrypt($tokens['refresh_token']) : null,
                'mp_user_id' => (string) ($tokens['user_id'] ?? $info['id'] ?? ''),
                'account_nome' => $nome,
                'account_email' => $info['email'] ?? '',
                'connected_at' => now()->toIso8601String(),
            ];

            $company->settings = $settings;
            $company->save();

            Log::info('MercadoPago OAuth conectado', [
                'company_id' => $company->id,
                'mp_user_id' => $tokens['user_id'] ?? '',
            ]);

        } catch (\Throwable $e) {
            Log::error('MercadoPago OAuth callback falhou', ['error' => $e->getMessage()]);

            return $this->redirectWithError('Falha ao conectar: '.$e->getMessage());
        }

        return redirect()
            ->route('configuracoes', ['tab' => 'integracoes'])
            ->with('success', 'Mercado Pago conectado com sucesso! Gateway ativado.');
    }

    /**
     * Remove os tokens OAuth e desativa o gateway MP.
     */
    public function disconnect(): RedirectResponse
    {
        $company = Company::findOrFail(auth()->user()->empresa_id);
        $this->authorize('update', $company);

        $settings = $company->settings ?? [];
        $settings['integrations']['mercadopago'] = ['connected' => false];

        if (($settings['integrations']['gateway'] ?? '') === 'mercadopago') {
            $settings['integrations']['gateway'] = 'nenhum';
        }

        $company->settings = $settings;
        $company->save();

        Log::info('MercadoPago OAuth desconectado', ['company_id' => $company->id]);

        return redirect()
            ->route('configuracoes', ['tab' => 'integracoes'])
            ->with('success', 'Mercado Pago desconectado.');
    }

    /**
     * Retorna métricas da conta MP (saldo + receita do mês) como JSON.
     */
    public function metrics(Request $request): JsonResponse
    {
        $company = Company::findOrFail(auth()->user()->empresa_id);
        $integrations = ($company->resolvedSettings())['integrations'] ?? [];
        $mp = $integrations['mercadopago'] ?? [];

        if (empty($mp['connected'])) {
            return response()->json(['ok' => false, 'erro' => 'Não conectado'], 422);
        }

        try {
            $token = decrypt($mp['access_token_enc']);
            $balance = MercadoPagoService::getBalance($token);
            $monthRevenue = MercadoPagoService::getMonthRevenue($token);

            return response()->json([
                'ok' => true,
                'balance' => $balance,
                'month_revenue' => $monthRevenue,
            ]);
        } catch (\Throwable $e) {
            Log::warning('MercadoPago metrics falhou', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'erro' => 'Falha ao buscar métricas'], 422);
        }
    }

    private function redirectWithError(string $message): RedirectResponse
    {
        return redirect()
            ->route('configuracoes', ['tab' => 'integracoes'])
            ->with('error', $message);
    }
}
