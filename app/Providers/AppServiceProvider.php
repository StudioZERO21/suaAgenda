<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Agendamento;
use App\Models\Company;
use App\Observers\AgendamentoObserver;
use App\Support\SaPalettes;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Agendamento::observe(AgendamentoObserver::class);

        // Trilha de auditoria de autenticação (LGPD)
        Event::listen(Login::class, function (Login $event): void {
            activity('auth')->causedBy($event->user)->event('login')
                ->withProperties(['ip' => request()->ip(), 'guard' => $event->guard])
                ->log('Login realizado');
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user === null) {
                return;
            }
            activity('auth')->causedBy($event->user)->event('logout')
                ->withProperties(['ip' => request()->ip(), 'guard' => $event->guard])
                ->log('Logout realizado');
        });

        Event::listen(Failed::class, function (Failed $event): void {
            activity('auth')->event('login_falho')
                ->withProperties([
                    'ip' => request()->ip(),
                    'email' => $event->credentials['email'] ?? null,
                    'guard' => $event->guard,
                ])
                ->log('Tentativa de login falhou');
        });

        View::composer('layouts.app', function ($view): void {
            $user = auth()->user();

            if (! $user?->empresa_id) {
                return;
            }

            $company = Company::query()->find($user->empresa_id);

            if ($company) {
                $settings = $company->resolvedSettings();

                $view->with('saCompany', $company);
                $view->with('saCompanySettings', $settings);
                $view->with('saFontsResolved', SaPalettes::resolveFonts(
                    $settings['heading_font'] ?? 'poppins',
                    $settings['body_font'] ?? 'inter',
                ));
            }
        });
    }
}
