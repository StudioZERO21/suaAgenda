<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Dashboard global do SaaS — visão exclusiva do super_admin.
 */
class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $hoje = today();
        $inicio30 = $hoje->copy()->subDays(29)->startOfDay();

        $totalEmpresas = Company::count();
        $empresasAtivas = Company::where('ativo', true)->count();
        $trialExpirando = Company::where('ativo', true)
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDays(7)])
            ->count();

        $usuariosAtivos = User::where('ativo', true)->count();

        $porPlano = Company::select('plan_slug', DB::raw('COUNT(*) as total'))
            ->groupBy('plan_slug')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'plano' => $row->plan_slug ?? 'sem plano',
                'total' => (int) $row->total,
            ]);

        $agendamentosPorDia = Agendamento::where('data_hora', '>=', $inicio30)
            ->where('data_hora', '<=', now()->endOfDay())
            ->select(DB::raw('DATE(data_hora) as dia'), DB::raw('COUNT(*) as total'))
            ->groupBy('dia')
            ->orderBy('dia')
            ->get()
            ->keyBy('dia');

        $serie30Dias = collect(range(29, 0))->map(function (int $i) use ($hoje, $agendamentosPorDia): array {
            $d = $hoje->copy()->subDays($i);
            $key = $d->format('Y-m-d');

            return [
                'data' => $d->format('d/m'),
                'total' => (int) ($agendamentosPorDia->get($key)->total ?? 0),
            ];
        })->values();

        $agendamentos30 = (int) $serie30Dias->sum('total');
        $maxSerie = max($serie30Dias->max('total'), 1);

        $empresasRecentes = Company::orderByDesc('created_at')->limit(6)->get();

        $topEmpresas = Company::withCount([
            'agendamentos as agendamentos_30d' => fn ($q) => $q->where('data_hora', '>=', $inicio30),
        ])
            ->orderByDesc('agendamentos_30d')
            ->limit(6)
            ->get();

        // ── Métricas SaaS ─────────────────────────────────────────────────
        $mrr = (float) Subscription::whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_GRACE])
            ->sum('monthly_amount');

        $novasEmpresas30d = Company::where('created_at', '>=', $inicio30)->count();

        $churned30d = Subscription::where('status', Subscription::STATUS_CANCELLED)
            ->where('cancelled_at', '>=', $inicio30)
            ->count();

        $totalAtivos30dAtras = Subscription::whereIn('status', [
            Subscription::STATUS_ACTIVE,
            Subscription::STATUS_GRACE,
            Subscription::STATUS_TRIAL,
        ])
            ->where('created_at', '<=', $inicio30)
            ->count();

        $churnRate = $totalAtivos30dAtras > 0
            ? round($churned30d / $totalAtivos30dAtras * 100, 1)
            : 0.0;

        $conversoesTrial30d = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->where('updated_at', '>=', $inicio30)
            ->count();

        $triaisAtivos = Subscription::where('status', Subscription::STATUS_TRIAL)->count();

        return view('admin.dashboard', compact(
            'totalEmpresas',
            'empresasAtivas',
            'trialExpirando',
            'usuariosAtivos',
            'porPlano',
            'serie30Dias',
            'agendamentos30',
            'maxSerie',
            'empresasRecentes',
            'topEmpresas',
            'mrr',
            'novasEmpresas30d',
            'churned30d',
            'churnRate',
            'conversoesTrial30d',
            'triaisAtivos',
        ));
    }
}
