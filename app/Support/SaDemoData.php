<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Dados de demonstração para telas MVP sem model dedicado.
 *
 * Centraliza mocks usados em Produtos, Portfólio, Cargos e Permissões
 * até a implementação dos respectivos Models na Etapa 1.4+.
 */
final class SaDemoData
{
    /**
     * Catálogo de produtos de exemplo.
     *
     * @return list<array<string, mixed>>
     */
    public static function produtos(): array
    {
        return [
            [
                'id' => 1,
                'nome' => 'Pomada modeladora',
                'categoria' => 'Cabelo',
                'preco' => 45.90,
                'custo' => 22.00,
                'estoque' => 18,
                'unidade' => 'un.',
                'ativo' => true,
                'sku' => 'POB001',
                'descricao' => 'Fixação forte, efeito matte. 100g',
            ],
            [
                'id' => 2,
                'nome' => 'Óleo de barba',
                'categoria' => 'Barba',
                'preco' => 38.00,
                'custo' => 15.00,
                'estoque' => 24,
                'unidade' => 'un.',
                'ativo' => true,
                'sku' => 'OBB002',
                'descricao' => 'Hidratação e brilho para barba. 30ml',
            ],
            [
                'id' => 3,
                'nome' => 'Shampoo profissional',
                'categoria' => 'Cabelo',
                'preco' => 65.00,
                'custo' => 28.00,
                'estoque' => 12,
                'unidade' => 'un.',
                'ativo' => true,
                'sku' => 'SHP003',
                'descricao' => 'Shampoo sem sulfato 300ml',
            ],
            [
                'id' => 4,
                'nome' => 'Cera capilar',
                'categoria' => 'Cabelo',
                'preco' => 32.00,
                'custo' => 14.00,
                'estoque' => 9,
                'unidade' => 'un.',
                'ativo' => true,
                'sku' => 'CRC004',
                'descricao' => 'Cera para acabamento texturizado. 80g',
            ],
            [
                'id' => 5,
                'nome' => 'Balm pós-barba',
                'categoria' => 'Barba',
                'preco' => 42.00,
                'custo' => 18.00,
                'estoque' => 15,
                'unidade' => 'un.',
                'ativo' => true,
                'sku' => 'BLM005',
                'descricao' => 'Hidratação e calmante pós-barba. 100ml',
            ],
            [
                'id' => 6,
                'nome' => 'Loção facial',
                'categoria' => 'Skincare',
                'preco' => 78.00,
                'custo' => 35.00,
                'estoque' => 6,
                'unidade' => 'un.',
                'ativo' => false,
                'sku' => 'LCF006',
                'descricao' => 'Hidratante facial masculino. 50ml',
            ],
            [
                'id' => 7,
                'nome' => 'Pente de madeira',
                'categoria' => 'Acessórios',
                'preco' => 24.90,
                'custo' => 8.00,
                'estoque' => 30,
                'unidade' => 'un.',
                'ativo' => true,
                'sku' => 'PMD007',
                'descricao' => 'Pente artesanal anti-estático',
            ],
        ];
    }

    /**
     * Categorias de produtos para filtros.
     *
     * @return list<string>
     */
    public static function categoriasProduto(): array
    {
        return [
            'Todos',
            'Cabelo',
            'Barba',
            'Skincare',
            'Cosméticos',
            'Acessórios',
            'Higiene',
            'Outros',
        ];
    }

    /**
     * Fotos do portfólio de exemplo.
     *
     * @return list<array<string, mixed>>
     */
    public static function portfolio(): array
    {
        return [
            ['id' => 1, 'prof_id' => 1, 'prof' => 'João Silva', 'categoria' => 'Corte', 'titulo' => 'Degradê moderno', 'data' => '2026-06-05', 'destaque' => true, 'cor' => '#1a1a1a', 'tags' => ['degradê', 'fade']],
            ['id' => 2, 'prof_id' => 1, 'prof' => 'João Silva', 'categoria' => 'Corte', 'titulo' => 'Corte clássico', 'data' => '2026-06-04', 'destaque' => false, 'cor' => '#1a1a1a', 'tags' => ['clássico']],
            ['id' => 3, 'prof_id' => 2, 'prof' => 'Carlos Mendes', 'categoria' => 'Barba', 'titulo' => 'Barba estilizada', 'data' => '2026-06-03', 'destaque' => true, 'cor' => '#d4a574', 'tags' => ['barba', 'navalha']],
            ['id' => 4, 'prof_id' => 2, 'prof' => 'Carlos Mendes', 'categoria' => 'Barba', 'titulo' => 'Bigode + barba', 'data' => '2026-06-02', 'destaque' => false, 'cor' => '#d4a574', 'tags' => ['barba']],
            ['id' => 5, 'prof_id' => 3, 'prof' => 'Ana Costa', 'categoria' => 'Coloração', 'titulo' => 'Mechas douradas', 'data' => '2026-06-01', 'destaque' => true, 'cor' => '#6366f1', 'tags' => ['mechas', 'loiro']],
            ['id' => 6, 'prof_id' => 3, 'prof' => 'Ana Costa', 'categoria' => 'Coloração', 'titulo' => 'Coloração ruivo', 'data' => '2026-05-30', 'destaque' => false, 'cor' => '#6366f1', 'tags' => ['coloração', 'ruivo']],
            ['id' => 7, 'prof_id' => 1, 'prof' => 'João Silva', 'categoria' => 'Corte', 'titulo' => 'Undercut', 'data' => '2026-05-28', 'destaque' => false, 'cor' => '#1a1a1a', 'tags' => ['undercut']],
            ['id' => 8, 'prof_id' => 3, 'prof' => 'Ana Costa', 'categoria' => 'Antes & Depois', 'titulo' => 'Transformação completa', 'data' => '2026-05-25', 'destaque' => true, 'cor' => '#6366f1', 'tags' => ['antes-depois']],
        ];
    }

    /**
     * Categorias do portfólio.
     *
     * @return list<string>
     */
    public static function categoriasPortfolio(): array
    {
        return [
            'Todos',
            'Corte',
            'Barba',
            'Coloração',
            'Penteado',
            'Antes & Depois',
            'Ambiente',
        ];
    }

    /**
     * Cargos da empresa de exemplo.
     *
     * @return list<array<string, mixed>>
     */
    public static function cargos(): array
    {
        return [
            [
                'id' => 1,
                'nome' => 'Administrador',
                'nivel' => 'admin',
                'nivel_label' => 'Administrador — acesso total',
                'cor' => '#ef4444',
                'descricao' => 'Acesso total ao sistema. Gerencia planos, configurações e permissões.',
                'membros' => 1,
                'comissao' => 0,
            ],
            [
                'id' => 2,
                'nome' => 'Gerente',
                'nivel' => 'manager',
                'nivel_label' => 'Gerente — relatórios e equipe',
                'cor' => '#f59e0b',
                'descricao' => 'Gerencia equipe, relatórios e configurações operacionais.',
                'membros' => 1,
                'comissao' => 0,
            ],
            [
                'id' => 3,
                'nome' => 'Barbeiro',
                'nivel' => 'professional',
                'nivel_label' => 'Profissional — agenda própria',
                'cor' => '#1a1a1a',
                'descricao' => 'Realiza atendimentos, acessa sua agenda e vê suas comissões.',
                'membros' => 2,
                'comissao' => 40,
            ],
            [
                'id' => 4,
                'nome' => 'Colorista',
                'nivel' => 'professional',
                'nivel_label' => 'Profissional — agenda própria',
                'cor' => '#6366f1',
                'descricao' => 'Especialista em coloração. Acessa sua agenda e histórico de clientes.',
                'membros' => 1,
                'comissao' => 42,
            ],
            [
                'id' => 5,
                'nome' => 'Recepcionista',
                'nivel' => 'receptionist',
                'nivel_label' => 'Recepcionista — agendamentos',
                'cor' => '#10b981',
                'descricao' => 'Gerencia agendamentos, cadastros de clientes e pagamentos.',
                'membros' => 1,
                'comissao' => 0,
            ],
            [
                'id' => 6,
                'nome' => 'Estagiário',
                'nivel' => 'intern',
                'nivel_label' => 'Estagiário — acesso limitado',
                'cor' => '#64748b',
                'descricao' => 'Acesso supervisionado. Pode ver agenda mas não editar dados sensíveis.',
                'membros' => 1,
                'comissao' => 20,
            ],
        ];
    }

    /**
     * Catálogo ACL de permissões agrupadas.
     *
     * @return array<string, list<array{id: string, label: string}>>
     */
    public static function aclCatalogo(): array
    {
        return [
            'Agenda' => [
                ['id' => 'cal_view', 'label' => 'Ver agenda completa'],
                ['id' => 'cal_own', 'label' => 'Ver própria agenda'],
                ['id' => 'cal_create', 'label' => 'Criar agendamentos'],
                ['id' => 'cal_edit', 'label' => 'Editar agendamentos'],
                ['id' => 'cal_delete', 'label' => 'Cancelar agendamentos'],
                ['id' => 'cal_move', 'label' => 'Mover agendamentos (drag)'],
            ],
            'Clientes' => [
                ['id' => 'cli_view', 'label' => 'Ver lista de clientes'],
                ['id' => 'cli_create', 'label' => 'Cadastrar clientes'],
                ['id' => 'cli_edit', 'label' => 'Editar dados de clientes'],
                ['id' => 'cli_delete', 'label' => 'Excluir clientes'],
                ['id' => 'cli_history', 'label' => 'Ver histórico de clientes'],
                ['id' => 'cli_photos', 'label' => 'Gerenciar fotos / antes-depois'],
            ],
            'Financeiro' => [
                ['id' => 'fin_view', 'label' => 'Ver receita total'],
                ['id' => 'fin_own', 'label' => 'Ver próprias comissões'],
                ['id' => 'fin_pdv', 'label' => 'Operar PDV'],
                ['id' => 'fin_export', 'label' => 'Exportar relatórios'],
            ],
            'Equipe' => [
                ['id' => 'stf_view', 'label' => 'Ver lista de funcionários'],
                ['id' => 'stf_create', 'label' => 'Cadastrar funcionários'],
                ['id' => 'stf_edit', 'label' => 'Editar funcionários'],
                ['id' => 'stf_delete', 'label' => 'Remover funcionários'],
            ],
            'Configurações' => [
                ['id' => 'cfg_theme', 'label' => 'Alterar tema e aparência'],
                ['id' => 'cfg_company', 'label' => 'Editar dados da empresa'],
                ['id' => 'cfg_plans', 'label' => 'Gerenciar planos'],
                ['id' => 'cfg_perms', 'label' => 'Editar permissões'],
                ['id' => 'cfg_api', 'label' => 'Acessar API & Webhooks'],
                ['id' => 'cfg_site', 'label' => 'Configurar site público'],
            ],
            'Portfólio & Produtos' => [
                ['id' => 'ptf_view', 'label' => 'Ver portfólio'],
                ['id' => 'ptf_edit', 'label' => 'Gerenciar portfólio'],
                ['id' => 'prd_view', 'label' => 'Ver produtos'],
                ['id' => 'prd_edit', 'label' => 'Gerenciar produtos'],
            ],
        ];
    }

    /**
     * Grupos de acesso padrão.
     *
     * @return list<array<string, mixed>>
     */
    public static function gruposAcesso(): array
    {
        $todos = array_merge(...array_values(self::aclCatalogo()));

        return [
            [
                'id' => 'g-admin',
                'nome' => 'Acesso Total',
                'cor' => '#ef4444',
                'descricao' => 'Todas as permissões habilitadas',
                'perms' => array_column($todos, 'id'),
            ],
            [
                'id' => 'g-mgr',
                'nome' => 'Gestão Operacional',
                'cor' => '#f59e0b',
                'descricao' => 'Relatórios, equipe e agenda completa',
                'perms' => [
                    'cal_view', 'cal_create', 'cal_edit', 'cal_delete', 'cal_move',
                    'cli_view', 'cli_create', 'cli_edit', 'cli_history',
                    'fin_view', 'fin_export', 'stf_view', 'stf_create', 'stf_edit',
                    'prd_view', 'prd_edit', 'ptf_view', 'ptf_edit',
                    'cfg_theme', 'cfg_company', 'cfg_site',
                ],
            ],
            [
                'id' => 'g-prof',
                'nome' => 'Profissional',
                'cor' => '#6366f1',
                'descricao' => 'Agenda própria, clientes atendidos e comissões',
                'perms' => [
                    'cal_own', 'cal_move', 'cli_view', 'cli_history', 'cli_photos',
                    'fin_own', 'ptf_view', 'ptf_edit', 'prd_view',
                ],
            ],
            [
                'id' => 'g-recep',
                'nome' => 'Recepção',
                'cor' => '#10b981',
                'descricao' => 'Agendamentos, cadastros e PDV',
                'perms' => [
                    'cal_view', 'cal_create', 'cal_edit', 'cal_move',
                    'cli_view', 'cli_create', 'cli_edit', 'cli_history',
                    'fin_pdv', 'prd_view', 'ptf_view',
                ],
            ],
            [
                'id' => 'g-intern',
                'nome' => 'Estagiário',
                'cor' => '#64748b',
                'descricao' => 'Visualização supervisionada',
                'perms' => ['cal_own', 'cli_view', 'ptf_view', 'prd_view'],
            ],
        ];
    }

    /**
     * Formas de pagamento com percentuais de exemplo.
     *
     * @return list<array{label: string, pct: int, cor: string}>
     */
    /**
     * Profissionais de demonstração para filtros do portfólio.
     *
     * @return list<array{id: int, nome: string}>
     */
    public static function profissionaisDemo(): array
    {
        return [
            ['id' => 1, 'nome' => 'João Silva'],
            ['id' => 2, 'nome' => 'Carlos Mendes'],
            ['id' => 3, 'nome' => 'Ana Costa'],
        ];
    }

    /**
     * Níveis de permissão para cargos.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function niveisPermissao(): array
    {
        return [
            ['value' => 'admin', 'label' => 'Administrador — acesso total'],
            ['value' => 'manager', 'label' => 'Gerente — relatórios e equipe'],
            ['value' => 'professional', 'label' => 'Profissional — agenda própria'],
            ['value' => 'receptionist', 'label' => 'Recepcionista — agendamentos'],
            ['value' => 'intern', 'label' => 'Estagiário — acesso limitado'],
        ];
    }

    /**
     * Cores para seleção de cargos.
     *
     * @return list<string>
     */
    public static function coresCargo(): array
    {
        return [
            '#ef4444', '#f59e0b', '#10b981', '#6366f1', '#ec4899',
            '#0ea5e9', '#8b5cf6', '#1a1a1a', '#d4a574', '#14b8a6',
        ];
    }

    public static function metodosPagamento(): array
    {
        return [
            ['label' => 'Pix', 'pct' => 42, 'cor' => '#10b981'],
            ['label' => 'Cartão Crédito', 'pct' => 28, 'cor' => '#6366f1'],
            ['label' => 'Cartão Débito', 'pct' => 20, 'cor' => '#f59e0b'],
            ['label' => 'Dinheiro', 'pct' => 10, 'cor' => '#ef4444'],
        ];
    }
}
