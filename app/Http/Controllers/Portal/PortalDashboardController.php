<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\Company;
use App\Services\AgendamentoCancelamentoService;
use App\Services\LgpdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Área autenticada do cliente: histórico, gastos, próximos
 * agendamentos, cancelamento conforme regra e dados (LGPD).
 */
class PortalDashboardController extends Controller
{
    public function dashboard(string $slug, AgendamentoCancelamentoService $cancelamento): View
    {
        $company = Company::where('slug', $slug)->firstOrFail();
        $cliente = Auth::guard('cliente')->user();

        $base = Agendamento::where('cliente_id', $cliente->id);

        $proximos = (clone $base)
            ->where('data_hora', '>=', now())
            ->whereIn('status', [Agendamento::STATUS_PENDENTE, Agendamento::STATUS_CONFIRMADO])
            ->with(['servico:id,nome,cor', 'profissional:id,name'])
            ->orderBy('data_hora')
            ->get()
            ->map(fn (Agendamento $ag) => [
                'model' => $ag,
                'pode_cancelar' => $cancelamento->podeCancelar($ag)['ok'],
            ]);

        $historico = (clone $base)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->with(['servico:id,nome', 'profissional:id,name', 'avaliacao'])
            ->orderByDesc('data_hora')
            ->limit(30)
            ->get();

        $totalGasto = (float) (clone $base)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->sum('valor');

        $totalAtendimentos = (clone $base)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->count();

        $politica = $cancelamento->descricaoPolitica($company->id);

        return view('portal.dashboard', compact(
            'company', 'cliente', 'proximos', 'historico',
            'totalGasto', 'totalAtendimentos', 'politica',
        ));
    }

    public function cancelar(Request $request, string $slug, Agendamento $agendamento, AgendamentoCancelamentoService $cancelamento): RedirectResponse
    {
        $cliente = Auth::guard('cliente')->user();

        abort_if($agendamento->cliente_id !== $cliente->id, 404);

        $resultado = $cancelamento->podeCancelar($agendamento);

        if (! $resultado['ok']) {
            return back()->with('erro', $resultado['motivo'] ?? 'Não foi possível cancelar.');
        }

        $agendamento->update(['status' => Agendamento::STATUS_CANCELADO]);

        return back()->with('sucesso', 'Agendamento cancelado.');
    }

    public function dados(string $slug): View
    {
        $company = Company::where('slug', $slug)->firstOrFail();
        $cliente = Auth::guard('cliente')->user();

        return view('portal.dados', compact('company', 'cliente'));
    }

    public function exportarDados(string $slug, LgpdService $lgpd): JsonResponse
    {
        $cliente = Auth::guard('cliente')->user();

        $dados = $lgpd->exportarDados($cliente);
        $lgpd->registrarExportacao($cliente);

        return response()->json($dados, 200, [
            'Content-Disposition' => 'attachment; filename="meus-dados-'.now()->format('Ymd').'.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function atualizarConsentimento(Request $request, string $slug): RedirectResponse
    {
        $cliente = Auth::guard('cliente')->user();

        $consent = $request->boolean('consent');

        $cliente->update([
            'lgpd_consent' => $consent,
            'lgpd_consent_at' => $consent ? now() : null,
            'lgpd_consent_ip' => $consent ? $request->ip() : null,
        ]);

        return back()->with('sucesso', 'Preferência de privacidade atualizada.');
    }
}
