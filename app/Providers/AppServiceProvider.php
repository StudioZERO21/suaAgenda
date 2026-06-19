<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Agendamento;
use App\Models\Company;
use App\Models\PlatformSetting;
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

    private function loadPlatformSettings(): void
    {
        try {
            $all = PlatformSetting::allCached();

            // Twilio — sobrescreve config('services.twilio.*')
            foreach ($all['twilio'] ?? [] as $key => $value) {
                if ($value !== null) {
                    config(["services.twilio.{$key}" => $value]);
                }
            }

            // Stripe — sobrescreve config('services.stripe_platform.*')
            foreach ($all['stripe'] ?? [] as $key => $value) {
                if ($value !== null) {
                    config(["services.stripe_platform.{$key}" => $value]);
                }
            }

            // Mercado Pago — sobrescreve config('services.mercadopago.*')
            foreach ($all['mercadopago'] ?? [] as $key => $value) {
                if ($value !== null) {
                    config(["services.mercadopago.{$key}" => $value]);
                }
            }

            // Email — sobrescreve config('mail.*')
            $email = $all['email'] ?? [];
            $map = [
                'mailer' => 'mail.default',
                'host' => 'mail.mailers.smtp.host',
                'port' => 'mail.mailers.smtp.port',
                'username' => 'mail.mailers.smtp.username',
                'password' => 'mail.mailers.smtp.password',
                'encryption' => 'mail.mailers.smtp.encryption',
                'from_address' => 'mail.from.address',
                'from_name' => 'mail.from.name',
            ];
            foreach ($map as $key => $configKey) {
                if (isset($email[$key]) && $email[$key] !== null) {
                    config([$configKey => $email[$key]]);
                }
            }
        } catch (\Throwable) {
            // Tabela pode não existir ainda (primeira migração)
        }
    }

    public function boot(): void
    {
        $this->loadPlatformSettings();

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
