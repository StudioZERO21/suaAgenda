<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\SaDemoData;
use Illuminate\View\View;

/**
 * Gestão de cargos da empresa (MVP — dados de demonstração).
 */
class CargoController extends Controller
{
    /**
     * Lista os cargos configurados na empresa.
     */
    public function index(): View
    {
        return view('cargos.index', [
            'cargosJson' => SaDemoData::cargos(),
            'niveis' => SaDemoData::niveisPermissao(),
            'cores' => SaDemoData::coresCargo(),
        ]);
    }
}
