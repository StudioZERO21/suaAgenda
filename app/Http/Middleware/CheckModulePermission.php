<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforcement por módulo: mapeia o nome da rota para a(s) permission(s)
 * necessária(s) (qualquer uma libera). Espelha o mapa do NavMenu — item
 * escondido do menu também fica inacessível por URL direta (403).
 *
 * Regras mais específicas (chaves mais longas) devem vir primeiro.
 * Rotas não mapeadas seguem apenas com as checagens de controller/policy.
 */
class CheckModulePermission
{
    /** @var array<string, list<string>> */
    private const MAPA = [
        'permissoes.grupos' => ['cfg_perms'],
        'permissoes.cargos' => ['cfg_perms'],
        'permissoes.index' => ['cfg_perms'],
        'calendario' => ['cal_view', 'cal_own'],
        'agendamentos.' => ['cal_view', 'cal_own'],
        'clientes.' => ['cli_view'],
        'profissionais.' => ['stf_view'],
        'servicos.' => ['srv_view'],
        'produtos.' => ['prd_view'],
        'pdv' => ['fin_pdv'],
        'financeiro' => ['fin_view', 'fin_own'],
        'lancamentos.' => ['fin_view', 'fin_own'],
        'regras.' => ['cfg_rules'],
        'relatorios' => ['fin_view', 'fin_export'],
        'portfolio.' => ['ptf_view'],
        'cargos.' => ['stf_view', 'stf_edit'],
        'planos.' => ['cfg_plans'],
        'site.' => ['cfg_site'],
        'configuracoes' => ['cfg_theme', 'cfg_company'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = $request->route()?->getName();

        if ($user === null || $routeName === null) {
            return $next($request);
        }

        foreach (self::MAPA as $prefixo => $permissions) {
            if (! str_starts_with($routeName, $prefixo)) {
                continue;
            }

            foreach ($permissions as $permission) {
                if ($user->can($permission)) {
                    return $next($request);
                }
            }

            abort(403, 'Você não tem permissão para acessar o que está solicitando.');
        }

        return $next($request);
    }
}
