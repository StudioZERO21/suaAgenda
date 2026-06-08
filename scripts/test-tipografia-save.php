<?php

declare(strict_types=1);
use App\Models\Company;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$company = Company::first();
if (! $company) {
    echo "NO COMPANY\n";
    exit(1);
}

echo 'Before: '.json_encode($company->settings)."\n";

$settings = array_merge($company->settings ?? [], [
    'heading_font' => 'dm-serif',
    'body_font' => 'lato',
]);
$company->settings = $settings;
$saved = $company->save();

echo 'Save returned: '.($saved ? 'true' : 'false')."\n";
echo 'After: '.json_encode($company->fresh()->settings)."\n";
