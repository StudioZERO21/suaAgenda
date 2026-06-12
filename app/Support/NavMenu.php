<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

/**
 * Fonte única do menu lateral/drawer. Cada item declara a(s) permission(s)
 * necessária(s) (qualquer uma libera o item); null = visível para todos os
 * usuários autenticados. Gate::before garante que super_admin vê tudo.
 */
final class NavMenu
{
    /**
     * @return list<array{route: string, label: string, icon: string, match: string, permission: list<string>|null}>
     */
    public static function itens(User $user): array
    {
        return array_values(array_filter(
            self::todos(),
            fn (array $item): bool => self::pode($user, $item['permission'])
        ));
    }

    /**
     * Menu do painel super_admin (visão global do SaaS).
     *
     * @return list<array{route: string, label: string, icon: string, match: string, permission: null}>
     */
    public static function admin(): array
    {
        return [
            ['route' => 'admin.dashboard',      'label' => 'Dashboard', 'permission' => null, 'match' => 'admin.dashboard',  'icon' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>'],
            ['route' => 'admin.empresas.index', 'label' => 'Empresas',  'permission' => null, 'match' => 'admin.empresas.*', 'icon' => '<path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/>'],
        ];
    }

    /**
     * @param  list<string>|null  $permissions
     */
    public static function pode(User $user, ?array $permissions): bool
    {
        if ($permissions === null) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{route: string, label: string, icon: string, match: string, permission: list<string>|null}>
     */
    private static function todos(): array
    {
        return [
            ['route' => 'dashboard',           'label' => 'Dashboard',     'permission' => null,                          'match' => 'dashboard',       'icon' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>'],
            ['route' => 'calendario',          'label' => 'Agenda',        'permission' => ['cal_view', 'cal_own'],       'match' => 'calendario',      'icon' => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>'],
            ['route' => 'clientes.index',      'label' => 'Clientes',      'permission' => ['cli_view'],                  'match' => 'clientes.*',      'icon' => '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>'],
            ['route' => 'profissionais.index', 'label' => 'Funcionários',  'permission' => ['stf_view'],                  'match' => 'profissionais.*', 'icon' => '<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>'],
            ['route' => 'servicos.index',      'label' => 'Serviços',      'permission' => ['srv_view'],                  'match' => 'servicos.*',      'icon' => '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/>'],
            ['route' => 'produtos.index',      'label' => 'Produtos',      'permission' => ['prd_view'],                  'match' => 'produtos.*',      'icon' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>'],
            ['route' => 'pdv',                 'label' => 'PDV',           'permission' => ['fin_pdv'],                   'match' => 'pdv',             'icon' => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>'],
            ['route' => 'financeiro',          'label' => 'Financeiro',    'permission' => ['fin_view', 'fin_own'],       'match' => 'financeiro',      'icon' => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>'],
            ['route' => 'relatorios',          'label' => 'Relatórios',    'permission' => ['fin_view', 'fin_export'],    'match' => 'relatorios',      'icon' => '<line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>'],
            ['route' => 'portfolio.index',     'label' => 'Portfólio',     'permission' => ['ptf_view'],                  'match' => 'portfolio.*',     'icon' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>'],
            ['route' => 'cargos.index',        'label' => 'Cargos',        'permission' => ['stf_view', 'stf_edit'],      'match' => 'cargos.*',        'icon' => '<path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5z"/>'],
            ['route' => 'permissoes.index',    'label' => 'Permissões',    'permission' => ['cfg_perms'],                 'match' => 'permissoes.*',    'icon' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>'],
            ['route' => 'planos.index',        'label' => 'Planos',        'permission' => ['cfg_plans'],                 'match' => 'planos.*',        'icon' => '<polyline points="20 6 9 17 4 12"/>'],
            ['route' => 'site.index',          'label' => 'Site Público',  'permission' => ['cfg_site'],                  'match' => 'site.*',          'icon' => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>'],
            ['route' => 'configuracoes',       'label' => 'Configurações', 'permission' => ['cfg_theme', 'cfg_company'],  'match' => 'configuracoes*',  'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51a1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>'],
        ];
    }
}
