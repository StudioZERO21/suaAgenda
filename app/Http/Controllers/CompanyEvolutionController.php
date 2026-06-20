<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WhatsappConversa;
use App\Services\EvolutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CompanyEvolutionController extends Controller
{
    public function index(): View
    {
        $company = auth()->user()->company;
        $svc = EvolutionService::fromConfig();
        $status = 'not_configured';
        $instance = $company->evolution_instance;

        if ($svc->configurado() && $instance) {
            $status = $svc->status($instance);

            // Sincroniza o campo connected no banco
            $connected = $status === 'open';
            if ($connected !== $company->evolution_connected) {
                $company->update([
                    'evolution_connected' => $connected,
                    'evolution_connected_at' => $connected ? now() : null,
                ]);
            }
        }

        $conversas = $instance
            ? WhatsappConversa::where('company_id', $company->id)
                ->latest()
                ->take(5)
                ->get()
            : collect();

        return view('configuracoes.whatsapp', compact('company', 'status', 'instance', 'conversas'));
    }

    public function conectar(Request $request): JsonResponse
    {
        $company = auth()->user()->company;
        $svc = EvolutionService::fromConfig();

        if (! $svc->configurado()) {
            return response()->json(['ok' => false, 'erro' => 'Evolution API não configurada na plataforma.']);
        }

        $instance = EvolutionService::nomeInstanciaEmpresa($company->id);
        $webhookUrl = route('webhooks.evolution.inbound', ['instanceName' => $instance]);

        $criou = $svc->criarInstancia($instance, $webhookUrl);

        if (! $criou) {
            return response()->json(['ok' => false, 'erro' => 'Não foi possível criar a instância no Evolution API.']);
        }

        $company->update(['evolution_instance' => $instance]);

        $qr = $svc->obterQrCode($instance);

        return response()->json([
            'ok' => true,
            'qr' => $qr,
            'instance' => $instance,
        ]);
    }

    public function statusPoll(): JsonResponse
    {
        $company = auth()->user()->company;
        $svc = EvolutionService::fromConfig();
        $instance = $company->evolution_instance;

        if (! $instance || ! $svc->configurado()) {
            return response()->json(['status' => 'not_configured']);
        }

        $status = $svc->status($instance);
        $connected = $status === 'open';

        if ($connected !== $company->evolution_connected) {
            $company->update([
                'evolution_connected' => $connected,
                'evolution_connected_at' => $connected ? now() : null,
            ]);
        }

        // Se ainda não conectado mas está "connecting", busca novo QR
        $qr = null;
        if ($status === 'connecting' || $status === 'close') {
            $qr = $svc->obterQrCode($instance);
        }

        return response()->json(['status' => $status, 'qr' => $qr]);
    }

    public function desconectar(): RedirectResponse
    {
        $company = auth()->user()->company;
        $svc = EvolutionService::fromConfig();
        $instance = $company->evolution_instance;

        if ($instance && $svc->configurado()) {
            $svc->desconectar($instance);
        }

        $company->update([
            'evolution_instance' => null,
            'evolution_connected' => false,
            'evolution_connected_at' => null,
        ]);

        return back()->with('success', 'WhatsApp desconectado com sucesso.');
    }
}
