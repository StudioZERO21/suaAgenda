<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreLancamentoRequest;
use App\Models\Agendamento;
use App\Models\Lancamento;
use App\Support\SaDemoData;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Painel financeiro com receita, transações e comissões.
 */
class FinanceiroController extends Controller
{
    /**
     * Exibe o dashboard financeiro do mês corrente.
     */
    public function index(Request $request): View
    {
        $empresaId = auth()->user()->empresa_id;
        $periodo = $request->input('periodo', 'month');

        [$inicio, $fim] = $this->resolverPeriodo($periodo);

        $base = Agendamento::where('company_id', $empresaId)
            ->with(['cliente', 'servico', 'profissional'])
            ->whereBetween('data_hora', [
                $inicio->copy()->startOfDay(),
                $fim->copy()->endOfDay(),
            ]);

        $finalizados = (clone $base)
            ->where('status', Agendamento::STATUS_FINALIZADO);

        $receitaTotal = (float) (clone $finalizados)->sum('valor');
        $totalFinalizados = (clone $finalizados)->count();
        $ticketMedio = $totalFinalizados > 0
            ? $receitaTotal / $totalFinalizados
            : 0.0;
        $comissaoTotal = round($receitaTotal * 0.30, 2);

        $pendentes = (clone $base)
            ->whereIn('status', [
                Agendamento::STATUS_PENDENTE,
                Agendamento::STATUS_CONFIRMADO,
            ]);

        $aReceber = (float) (clone $pendentes)->sum('valor');
        $qtdPendentes = (clone $pendentes)->count();

        $metodoLabels = ['Pix', 'Cartão Crédito', 'Cartão Débito', 'Dinheiro'];

        $transacoes = (clone $base)
            ->orderByDesc('data_hora')
            ->limit(50)
            ->get()
            ->map(fn (Agendamento $a, int $i) => [
                'id' => $a->id,
                'data' => $a->data_hora->format('Y-m-d'),
                'cliente' => $a->cliente?->name ?? 'Cliente avulso',
                'servico' => $a->servico?->nome ?? '—',
                'profissional' => $a->profissional?->name ?? '—',
                'valor' => (float) $a->valor,
                'status' => $a->status,
                'status_key' => match ($a->status) {
                    Agendamento::STATUS_FINALIZADO => 'paid',
                    Agendamento::STATUS_CANCELADO => 'refunded',
                    default => 'pending',
                },
                'tipo' => 'receita',
                'metodo' => $metodoLabels[$i % count($metodoLabels)],
            ]);

        $profissionaisFiltro = $transacoes
            ->pluck('profissional')
            ->unique()
            ->filter(fn (string $nome) => $nome !== '—')
            ->values()
            ->all();

        $receitaDiaria = $this->receitaDiaria(
            $empresaId,
            $inicio,
            $fim,
        );

        $comissoesProfissionais = $this->comissoesPorProfissional(
            $empresaId,
            $inicio,
            $fim,
            $receitaTotal,
        );

        $metodos = SaDemoData::metodosPagamento();

        return view('financeiro.index', compact(
            'receitaTotal',
            'ticketMedio',
            'comissaoTotal',
            'aReceber',
            'qtdPendentes',
            'totalFinalizados',
            'transacoes',
            'profissionaisFiltro',
            'receitaDiaria',
            'comissoesProfissionais',
            'metodos',
            'periodo',
            'inicio',
            'fim',
        ));
    }

    public function storeLancamento(StoreLancamentoRequest $request): RedirectResponse
    {
        Lancamento::create([
            ...$request->validated(),
            'company_id' => auth()->user()->empresa_id,
        ]);

        return back()->with('success', 'Lançamento criado.');
    }

    public function updateLancamento(StoreLancamentoRequest $request, Lancamento $lancamento): RedirectResponse
    {
        abort_if($lancamento->company_id !== auth()->user()->empresa_id, 403);

        $lancamento->update($request->validated());

        return back()->with('success', 'Lançamento atualizado.');
    }

    public function destroyLancamento(Lancamento $lancamento): RedirectResponse
    {
        abort_if($lancamento->company_id !== auth()->user()->empresa_id, 403);

        $lancamento->delete();

        return back()->with('success', 'Lançamento removido.');
    }

    /**
     * Resolve intervalo de datas conforme o período selecionado.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolverPeriodo(string $periodo): array
    {
        $hoje = Carbon::today();

        return match ($periodo) {
            'quarter' => [$hoje->copy()->startOfQuarter(), $hoje->copy()->endOfQuarter()],
            'year' => [$hoje->copy()->startOfYear(), $hoje->copy()->endOfYear()],
            default => [$hoje->copy()->startOfMonth(), $hoje->copy()->endOfMonth()],
        };
    }

    /**
     * Monta série de receita diária para o gráfico.
     *
     * @return list<float>
     */
    private function receitaDiaria(
        string $empresaId,
        Carbon $inicio,
        Carbon $fim,
    ): array {
        $dias = $inicio->diffInDays($fim) + 1;
        $serie = [];

        for ($i = 0; $i < $dias; $i++) {
            $dia = $inicio->copy()->addDays($i);

            $serie[] = (float) Agendamento::where('company_id', $empresaId)
                ->where('status', Agendamento::STATUS_FINALIZADO)
                ->whereDate('data_hora', $dia)
                ->sum('valor');
        }

        return $serie;
    }

    /**
     * Calcula comissões estimadas por profissional.
     *
     * @return list<array{name: string, cor: string, valor: float, pct: float}>
     */
    private function comissoesPorProfissional(
        string $empresaId,
        Carbon $inicio,
        Carbon $fim,
        float $receitaTotal,
    ): array {
        if ($receitaTotal <= 0) {
            return [];
        }

        $porProf = Agendamento::where('company_id', $empresaId)
            ->where('status', Agendamento::STATUS_FINALIZADO)
            ->whereBetween('data_hora', [
                $inicio->copy()->startOfDay(),
                $fim->copy()->endOfDay(),
            ])
            ->with('profissional')
            ->selectRaw('profissional_id, SUM(valor) as total')
            ->groupBy('profissional_id')
            ->orderByDesc('total')
            ->get();

        return $porProf->map(function ($row) use ($receitaTotal) {
            $total = (float) $row->total;
            $pct = $total / $receitaTotal;

            return [
                'name' => $row->profissional?->name ?? 'Sem profissional',
                'cor' => '#6366f1',
                'valor' => round($total * 0.30, 2),
                'pct' => $pct,
            ];
        })->values()->all();
    }
}
