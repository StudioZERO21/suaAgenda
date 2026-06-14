<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\BloqueioAgenda;
use App\Models\Cargo;
use App\Models\Cliente;
use App\Models\ClienteFoto;
use App\Models\Company;
use App\Models\Lancamento;
use App\Models\Notificacao;
use App\Models\PortfolioItem;
use App\Models\Produto;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use App\Models\Venda;
use App\Policies\AgendamentoPolicy;
use App\Policies\AvaliacaoPolicy;
use App\Policies\BloqueioAgendaPolicy;
use App\Policies\CargoPolicy;
use App\Policies\ClienteFotoPolicy;
use App\Policies\ClientePolicy;
use App\Policies\CompanyPolicy;
use App\Policies\LancamentoPolicy;
use App\Policies\NotificacaoPolicy;
use App\Policies\PortfolioItemPolicy;
use App\Policies\ProdutoPolicy;
use App\Policies\ProfissionalPolicy;
use App\Policies\ServicoPolicy;
use App\Policies\VendaPolicy;
use App\Support\UserPermissions;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Agendamento::class => AgendamentoPolicy::class,
        Avaliacao::class => AvaliacaoPolicy::class,
        BloqueioAgenda::class => BloqueioAgendaPolicy::class,
        Cargo::class => CargoPolicy::class,
        Cliente::class => ClientePolicy::class,
        ClienteFoto::class => ClienteFotoPolicy::class,
        Company::class => CompanyPolicy::class,
        Lancamento::class => LancamentoPolicy::class,
        Notificacao::class => NotificacaoPolicy::class,
        PortfolioItem::class => PortfolioItemPolicy::class,
        Produto::class => ProdutoPolicy::class,
        Servico::class => ServicoPolicy::class,
        Profissional::class => ProfissionalPolicy::class,
        Venda::class => VendaPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('super_admin')) {
                return true;
            }

            if (! UserPermissions::isModulePermission($ability)) {
                return null;
            }

            if ($user->hasRole('admin_empresa')) {
                return true;
            }

            if (UserPermissions::hasCompanyGrupo($user)) {
                return UserPermissions::can($user, $ability);
            }

            return null;
        });
    }
}
