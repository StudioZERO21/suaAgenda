<?php

declare(strict_types=1);

use App\Http\Controllers\AgendamentoController;
use App\Http\Controllers\AgendamentoPublicoController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CalendarioController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConfiguracaoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dev\DevLoginController;
use App\Http\Controllers\HorarioTrabalhoController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ProfissionalController;
use App\Http\Controllers\RelatorioController;
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
    Route::patch('agendamentos/{agendamento}/status', [AgendamentoController::class, 'updateStatus'])->name('agendamentos.updateStatus');
    Route::get('calendario', [CalendarioController::class, 'index'])->name('calendario');
    Route::get('relatorios', [RelatorioController::class, 'index'])->name('relatorios');
    Route::resource('clientes', ClienteController::class);
    Route::resource('servicos', ServicoController::class)->except(['show']);
    Route::resource('profissionais', ProfissionalController::class)->parameters(['profissionais' => 'profissional']);
    Route::get('profissionais/{profissional}/horarios', [HorarioTrabalhoController::class, 'show'])->name('profissionais.horarios');
    Route::put('profissionais/{profissional}/horarios', [HorarioTrabalhoController::class, 'update'])->name('profissionais.horarios.update');

    Route::get('perfil', [PerfilController::class, 'show'])->name('perfil');
    Route::put('perfil', [PerfilController::class, 'update'])->name('perfil.update');

    Route::get('configuracoes', [ConfiguracaoController::class, 'show'])->name('configuracoes');
    Route::put('configuracoes', [ConfiguracaoController::class, 'update'])->name('configuracoes.update');
});

// Agendamento público — sem autenticação
Route::get('/agendar/{slug}', [AgendamentoPublicoController::class, 'show'])->name('agendar.show');
Route::post('/agendar/{slug}', [AgendamentoPublicoController::class, 'store'])->name('agendar.store');
Route::get('/agendar/{slug}/slots', [AgendamentoPublicoController::class, 'slots'])->name('agendar.slots');
Route::get('/agendar/{slug}/confirmado/{agendamento}', [AgendamentoPublicoController::class, 'confirmado'])->name('agendar.confirmado');

if (app()->isLocal()) {
    Route::post('/dev/login', [DevLoginController::class, 'login'])->name('dev.login');
}
