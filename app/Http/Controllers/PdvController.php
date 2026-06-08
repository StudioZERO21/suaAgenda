<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Servico;
use App\Support\SaDemoData;
use Illuminate\View\View;

/**
 * Ponto de Venda — catálogo de produtos (demo) e serviços (banco).
 */
class PdvController extends Controller
{
    /**
     * Exibe a tela do PDV com itens disponíveis para venda.
     */
    public function index(): View
    {
        $empresaId = auth()->user()->empresa_id;

        // Catálogo formatado para consumo direto pelo Alpine (evita lógica
        // complexa no Blade, que quebra o parser do diretivo @json).
        $produtosJs = collect(SaDemoData::produtos())
            ->where('ativo', true)
            ->map(fn (array $p): array => [
                'key' => 'prd-'.$p['id'],
                'id' => $p['id'],
                'name' => $p['nome'],
                'price' => (float) $p['preco'],
                'stock' => $p['estoque'],
                'type' => 'product',
            ])
            ->values()
            ->all();

        $servicosJs = Servico::where('company_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get(['id', 'nome', 'preco', 'duracao_minutos', 'cor'])
            ->map(fn (Servico $s): array => [
                'key' => 'svc-'.$s->id,
                'id' => $s->id,
                'name' => $s->nome,
                'price' => (float) $s->preco,
                'duration' => $s->duracao_minutos,
                'color' => $s->cor ?? '#6366f1',
                'type' => 'service',
                'stock' => null,
            ])
            ->all();

        $clientes = Cliente::where('company_id', $empresaId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pdv.index', compact('produtosJs', 'servicosJs', 'clientes'));
    }
}
