<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\SaDemoData;
use Illuminate\View\View;

/**
 * Gestão de grupos de permissão ACL (MVP — dados de demonstração).
 */
class PermissaoController extends Controller
{
    /**
     * Exibe grupos de acesso e catálogo de permissões.
     */
    public function index(): View
    {
        $roleGroups = [
            1 => 'g-admin',
            2 => 'g-mgr',
            3 => 'g-prof',
            4 => 'g-prof',
            5 => 'g-recep',
        ];

        return view('permissoes.index', [
            'catalogo' => SaDemoData::aclCatalogo(),
            'gruposJson' => SaDemoData::gruposAcesso(),
            'cargosJson' => SaDemoData::cargos(),
            'roleGroupsJson' => $roleGroups,
        ]);
    }
}
