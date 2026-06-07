<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Agendamento;
use App\Models\User;
use App\Policies\AgendamentoPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Agendamento::class => AgendamentoPolicy::class,
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
