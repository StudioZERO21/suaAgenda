<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Mail\ClienteMagicLink;
use App\Models\Cliente;
use App\Models\ClienteLoginToken;
use App\Models\Company;
use App\Services\EvolutionService;
use App\Services\TwilioService;
use App\Services\WhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Autenticação do cliente por link mágico (sem senha).
 * Guard isolado 'cliente' — nunca acessa o painel interno.
 */
class PortalAuthController extends Controller
{
    public function showLogin(string $slug): View|RedirectResponse
    {
        $company = Company::where('slug', $slug)->where('ativo', true)->firstOrFail();

        if (Auth::guard('cliente')->check()) {
            return redirect()->route('portal.dashboard', $slug);
        }

        return view('portal.login', compact('company'));
    }

    public function enviarLink(Request $request, string $slug): RedirectResponse
    {
        $company = Company::where('slug', $slug)->where('ativo', true)->firstOrFail();

        $request->validate([
            'contato' => ['required', 'string', 'max:150'],
            'canal' => ['nullable', 'in:email,whatsapp'],
        ]);

        $contato = trim($request->input('contato'));
        $canal = $request->input('canal', 'email');

        $cliente = $this->localizarCliente($company, $contato);

        // Resposta neutra (anti-enumeração): sempre a mesma mensagem
        if ($cliente !== null && ! $cliente->anonimizado()) {
            ['token' => $token] = ClienteLoginToken::gerar($cliente, $canal, $request->ip());
            $url = route('portal.entrar.token', ['slug' => $slug, 'token' => $token]);

            if ($canal === 'email' && $cliente->email) {
                Mail::to($cliente->email)->queue(new ClienteMagicLink($cliente, $company, $url));
            } elseif ($canal === 'whatsapp' && $cliente->phone) {
                $enviado = $this->enviarWhatsAppMagicLink($company, $cliente->phone, $url);

                if (! $enviado) {
                    // fallback: link wa.me para o cliente abrir manualmente
                    $request->session()->flash('whatsapp_url', $this->linkWhatsapp($cliente->phone, $company, $url));
                }
            }
        }

        return redirect()->route('portal.entrar', $slug)
            ->with('enviado', true)
            ->with('canal', $canal);
    }

    public function entrarComToken(Request $request, string $slug, string $token): RedirectResponse
    {
        $company = Company::where('slug', $slug)->where('ativo', true)->firstOrFail();

        $registro = ClienteLoginToken::consumir($token);
        $cliente = $registro?->cliente;

        if ($registro === null || $cliente === null || $cliente->company_id !== $company->id || $cliente->anonimizado()) {
            return redirect()->route('portal.entrar', $slug)
                ->with('erro', 'Link inválido ou expirado. Solicite um novo acesso.');
        }

        Auth::guard('cliente')->login($cliente);
        $request->session()->regenerate();

        return redirect()->route('portal.dashboard', $slug);
    }

    public function logout(Request $request, string $slug): RedirectResponse
    {
        Auth::guard('cliente')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.entrar', $slug);
    }

    private function localizarCliente(Company $company, string $contato): ?Cliente
    {
        $clean = preg_replace('/\D/', '', $contato);

        return Cliente::where('company_id', $company->id)
            ->where(function ($q) use ($contato, $clean): void {
                $q->where('email', $contato);

                if ($clean !== '') {
                    $q->orWhere('phone', $contato)
                        ->orWhere('phone', $clean)
                        ->orWhereRaw('REPLACE(REPLACE(REPLACE(REPLACE(phone," ",""),"(",""),")",""),"-","") = ?', [$clean]);
                }
            })
            ->first();
    }

    private function enviarWhatsAppMagicLink(Company $company, string $phone, string $url): bool
    {
        $msg = "🔐 *Acesso à sua área — {$company->name}*\n\nClique no link abaixo para entrar:\n{$url}\n\n_Link válido por 15 minutos._";

        try {
            // 1. Evolution API da empresa (WhatsApp próprio)
            if ($company->evolution_connected && $company->evolution_instance) {
                $evolution = EvolutionService::fromConfig();
                if ($evolution->configurado()) {
                    return $evolution->enviarTexto($company->evolution_instance, $phone, $msg);
                }
            }

            // 2. Twilio plataforma (fallback da plataforma)
            $platform = TwilioService::fromConfig();
            if ($platform->whatsappConfigured()) {
                return $platform->enviarWhatsApp($phone, $msg);
            }

            // 3. Twilio por empresa (legado)
            $waConfig = $company->settings['integrations']['whatsapp'] ?? [];
            if (! empty($waConfig['ativo']) && ! empty($waConfig['twilio_sid'])) {
                WhatsAppService::enviar($waConfig, $phone, $msg);

                return true;
            }
        } catch (\Throwable $e) {
            Log::error('PortalAuth: falha ao enviar WhatsApp magic link', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    private function linkWhatsapp(string $phone, Company $company, string $url): string
    {
        $numero = preg_replace('/\D/', '', $phone);
        $texto = rawurlencode("Olá! Acesse sua área em {$company->name}: {$url}");

        return "https://wa.me/{$numero}?text={$texto}";
    }
}
