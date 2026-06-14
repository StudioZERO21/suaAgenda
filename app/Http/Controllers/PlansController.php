<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlansController extends Controller
{
    public function index(): View
    {
        $plans = Plan::ordered();
        $company = auth()->user()->company;
        $currentPlan = $company?->plan ?? Plan::find('starter');

        $companyId = auth()->user()->empresa_id;
        $mesAtual = now()->format('Y-m');

        // Use confirmados/created agendamentos as proxy for WhatsApp notifications sent
        $whatsappUsado = Agendamento::where('company_id', $companyId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->whereNotNull('created_at')
            ->count();

        // Email usage: agendamentos where cliente has email
        $emailUsado = Agendamento::where('company_id', $companyId)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->whereHas('cliente', fn ($q) => $q->whereNotNull('email'))
            ->count();

        $diasNoMes = now()->daysInMonth;
        $diaAtual = now()->day;
        $diasRestantes = $diasNoMes - $diaAtual;

        $smsLimite = $currentPlan?->sms_mensal ?? 0;

        $usage = [
            'whatsapp' => [
                'usado' => $whatsappUsado,
                'limite' => $currentPlan?->whatsapp_mensal ?? 50,
                'cor' => '#10b981',
                'label' => 'WhatsApp',
            ],
            'sms' => [
                'usado' => 0,
                'limite' => $smsLimite,
                'cor' => '#6366f1',
                'label' => 'SMS',
            ],
            'email' => [
                'usado' => $emailUsado,
                'limite' => -1,
                'cor' => 'var(--sa-secondary)',
                'label' => 'E-mail',
            ],
        ];

        $emTrial = $company?->emTrial() ?? false;
        $trialExpirado = $company?->trial_ends_at !== null && $company->trial_ends_at->isPast();

        $proximaCobranca = $emTrial
            ? $company->trial_ends_at?->translatedFormat('d/m/Y')
            : now()->addMonth()->startOfMonth()->translatedFormat('d/m/Y');

        $statusAssinatura = match (true) {
            $emTrial => ['label' => 'Período de teste', 'color' => 'var(--sa-secondary)', 'sub' => 'Trial ativo até '.$company->trial_ends_at?->translatedFormat('d/m/Y')],
            $trialExpirado || $company?->plano === 'trial' => ['label' => 'Trial expirado', 'color' => '#ef4444', 'sub' => 'Escolha um plano para continuar'],
            default => ['label' => '✓ Ativo (pago)', 'color' => '#10b981', 'sub' => 'Cliente desde '.$company?->created_at?->translatedFormat('M/Y')],
        };

        $clienteDesde = $company?->created_at?->translatedFormat('M/Y');

        return view('planos.index', compact(
            'plans',
            'company',
            'currentPlan',
            'usage',
            'diasRestantes',
            'mesAtual',
            'proximaCobranca',
            'statusAssinatura',
            'clienteDesde',
            'emTrial',
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('update', auth()->user()->company);

        $validated = $request->validate([
            'plan_slug' => ['required', 'string', 'exists:plans,slug'],
        ]);

        auth()->user()->company->update(['plan_slug' => $validated['plan_slug']]);

        return back()->with('success', 'Plano atualizado com sucesso.');
    }
}
