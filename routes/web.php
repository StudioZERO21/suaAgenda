<?php

declare(strict_types=1);

use App\Http\Controllers\AgendamentoController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dev\DevLoginController;
use App\Http\Controllers\ProfissionalController;
use App\Http\Controllers\ServicoController;
use App\Http\Middleware\SetTenantMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', SetTenantMiddleware::class])->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('agendamentos', AgendamentoController::class);
    Route::resource('clientes', ClienteController::class);
    Route::resource('servicos', ServicoController::class)->except(['show']);
    Route::resource('profissionais', ProfissionalController::class);
});

if (app()->isLocal()) {
    Route::post('/dev/login', [DevLoginController::class, 'login'])->name('dev.login');
}
