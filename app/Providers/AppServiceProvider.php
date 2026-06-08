<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Company;
use App\Support\SaPalettes;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
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
