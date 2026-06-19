<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminEmpresaController extends Controller
{
    public function index(Request $request): View
    {
        $busca = trim((string) $request->query('q', ''));

        $empresas = Company::withCount(['users', 'agendamentos'])
            ->when($busca !== '', function ($q) use ($busca): void {
                $q->where(function ($qq) use ($busca): void {
                    $qq->where('name', 'like', "%{$busca}%")
                        ->orWhere('slug', 'like', "%{$busca}%")
                        ->orWhere('email', 'like', "%{$busca}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.empresas', compact('empresas', 'busca'));
    }

    public function show(Company $empresa): View
    {
        $empresa->loadCount(['users', 'agendamentos']);

        $agendamentos30 = Agendamento::where('company_id', $empresa->id)
            ->where('data_hora', '>=', now()->subDays(30))
            ->count();

        $receita30 = (float) Agendamento::where('company_id', $empresa->id)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->where('data_hora', '>=', now()->subDays(30))
            ->sum('valor');

        return view('admin.empresa-detalhe', compact('empresa', 'agendamentos30', 'receita30'));
    }

    public function toggle(Company $empresa): JsonResponse
    {
        $empresa->update(['ativo' => ! $empresa->ativo]);

        return response()->json(['success' => true, 'ativo' => $empresa->ativo]);
    }

    public function updateLimites(Request $request, Company $empresa): RedirectResponse
    {
        $empresa->update([
            'notif_limit_whatsapp' => $request->integer('notif_limit_whatsapp') ?: null,
            'notif_limit_sms' => $request->integer('notif_limit_sms') ?: null,
            'notif_limit_email' => $request->integer('notif_limit_email') ?: null,
        ]);

        return back()->with('success_limites', 'Limites de notificação atualizados.');
    }
}
