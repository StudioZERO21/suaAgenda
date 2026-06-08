<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use App\Policies\AgendamentoPolicy;
use App\Policies\ClientePolicy;
use App\Policies\CompanyPolicy;
use App\Policies\ProfissionalPolicy;
use App\Policies\ServicoPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Agendamento::class => AgendamentoPolicy::class,
        Cliente::class => ClientePolicy::class,
        Company::class => CompanyPolicy::class,
        Servico::class => ServicoPolicy::class,
        Profissional::class => ProfissionalPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole('super_admin')) {
                return true;
            }
        });
    }
}
