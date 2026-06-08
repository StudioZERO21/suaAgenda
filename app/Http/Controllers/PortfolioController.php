<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\SaDemoData;
use Illuminate\View\View;

/**
 * Galeria de portfólio (MVP — dados de demonstração com interação Alpine).
 */
class PortfolioController extends Controller
{
    /**
     * Exibe a galeria de fotos do portfólio.
     */
    public function index(): View
    {
        return view('portfolio.index', [
            'fotosJson' => SaDemoData::portfolio(),
            'categorias' => SaDemoData::categoriasPortfolio(),
            'profissionais' => SaDemoData::profissionaisDemo(),
        ]);
    }
}
