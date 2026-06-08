<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\SaDemoData;
use Illuminate\View\View;

/**
 * Gestão de produtos (MVP — dados de demonstração).
 */
class ProdutoController extends Controller
{
    /**
     * Lista produtos com estatísticas e filtros.
     */
    public function index(): View
    {
        return view('produtos.index', [
            'produtosJson' => SaDemoData::produtos(),
            'categorias' => SaDemoData::categoriasProduto(),
            'unidades' => ['un.', 'ml', 'g', 'L', 'kg', 'caixa', 'par'],
        ]);
    }
}
