<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Cliente;
use App\Models\Company;
use Illuminate\View\View;

/**
 * Portal LGPD do super_admin: consentimento por empresa, anonimizações
 * e exportações recentes (via trilha de auditoria).
 */
class AdminLgpdController extends Controller
{
    public function index(): View
    {
        $consentPorEmpresa = Company::orderBy('name')
            ->get(['id', 'name'])
            ->map(function (Company $company): array {
                $stats = Cliente::withoutGlobalScopes()
                    ->where('company_id', $company->id)
                    ->whereNull('deleted_at')
                    ->selectRaw('COUNT(*) as total')
                    ->selectRaw('SUM(CASE WHEN lgpd_consent = 1 THEN 1 ELSE 0 END) as com_consent')
                    ->first();

                $anonimizados = Cliente::withoutGlobalScopes()
                    ->where('company_id', $company->id)
                    ->whereNotNull('anonymized_at')
                    ->count();

                $total = (int) ($stats->total ?? 0);
                $comConsent = (int) ($stats->com_consent ?? 0);

                return [
                    'empresa' => $company->name,
                    'total_clientes' => $total,
                    'com_consent' => $comConsent,
                    'pct_consent' => $total > 0 ? (int) round($comConsent / $total * 100) : 0,
                    'anonimizados' => $anonimizados,
                ];
            });

        $atividadesLgpd = Activity::inLog('lgpd')
            ->with('causer:id,name')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn (Activity $a) => [
                'quando' => $a->created_at->format('d/m/Y H:i'),
                'evento' => $a->event,
                'descricao' => $a->description,
                'causer' => $a->causer?->name ?? 'Sistema',
                'company_id' => $a->company_id,
            ]);

        $totais = [
            'exportacoes_30d' => Activity::inLog('lgpd')->forEvent('exportado')
                ->where('created_at', '>=', now()->subDays(30))->count(),
            'anonimizacoes_30d' => Activity::inLog('lgpd')->forEvent('anonimizado')
                ->where('created_at', '>=', now()->subDays(30))->count(),
            'clientes_sem_consent' => Cliente::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('lgpd_consent', false)
                ->count(),
        ];

        return view('admin.lgpd', compact('consentPorEmpresa', 'atividadesLgpd', 'totais'));
    }
}
