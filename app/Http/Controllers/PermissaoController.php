<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Cargo;
use App\Support\SaDemoData;
use Illuminate\View\View;

class PermissaoController extends Controller
{
    public function index(): View
    {
        $companyId = auth()->user()->empresa_id;

        $cargos = Cargo::where('company_id', $companyId)
            ->withCount('profissionais as membros')
            ->orderBy('nome')
            ->get()
            ->map(fn (Cargo $c): array => [
                'id' => $c->id,
                'nome' => $c->nome,
                'nivel' => $c->nivel,
                'cor' => $c->cor,
                'descricao' => $c->descricao,
                'comissao' => (float) ($c->comissao_pct ?? 0),
                'membros' => (int) ($c->membros ?? 0),
            ]);

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
            'cargosJson' => $cargos,
            'roleGroupsJson' => $roleGroups,
        ]);
    }
}
