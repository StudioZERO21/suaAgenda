<?php

declare(strict_types=1);

use App\Http\Controllers\AgendamentoController;
use App\Http\Controllers\AgendamentoPublicoController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CalendarioController;
use App\Http\Controllers\CargoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConfiguracaoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dev\DevLoginController;
use App\Http\Controllers\FinanceiroController;
use App\Http\Controllers\HorarioTrabalhoController;
use App\Http\Controllers\PdvController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PermissaoController;
use App\Http\Controllers\PlansController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\ProfissionalController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\ServicoController;
use App\Http\Controllers\SitePublicoController;
use App\Http\Middleware\SetTenantMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    Route::view('/recuperar-senha', 'auth.recover')->name('password.request');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', SetTenantMiddleware::class])->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('agendamentos', AgendamentoController::class);
    Route::patch('agendamentos/{agendamento}/status', [AgendamentoController::class, 'updateStatus'])->name('agendamentos.updateStatus');
    Route::patch('agendamentos/{agendamento}/move', [AgendamentoController::class, 'move'])->name('agendamentos.move');
    Route::get('calendario', [CalendarioController::class, 'index'])->name('calendario');
    Route::get('relatorios', [RelatorioController::class, 'index'])->name('relatorios');
    Route::get('financeiro', [FinanceiroController::class, 'index'])->name('financeiro');
    Route::get('produtos', [ProdutoController::class, 'index'])->name('produtos.index');
    Route::post('produtos', [ProdutoController::class, 'store'])->name('produtos.store');
    Route::put('produtos/{produto}', [ProdutoController::class, 'update'])->name('produtos.update');
    Route::delete('produtos/{produto}', [ProdutoController::class, 'destroy'])->name('produtos.destroy');
    Route::patch('produtos/{produto}/toggle', [ProdutoController::class, 'toggle'])->name('produtos.toggle');

    Route::get('pdv', [PdvController::class, 'index'])->name('pdv');
    Route::post('pdv/venda', [PdvController::class, 'store'])->name('pdv.store');

    Route::get('portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');

    Route::get('cargos', [CargoController::class, 'index'])->name('cargos.index');
    Route::post('cargos', [CargoController::class, 'store'])->name('cargos.store');
    Route::put('cargos/{cargo}', [CargoController::class, 'update'])->name('cargos.update');
    Route::delete('cargos/{cargo}', [CargoController::class, 'destroy'])->name('cargos.destroy');

    Route::get('permissoes', [PermissaoController::class, 'index'])->name('permissoes.index');
    Route::patch('permissoes/usuarios/{user}/role', [PermissaoController::class, 'assignUserRole'])->name('permissoes.users.role');

    Route::post('financeiro/lancamentos', [FinanceiroController::class, 'storeLancamento'])->name('financeiro.lancamentos.store');
    Route::put('financeiro/lancamentos/{lancamento}', [FinanceiroController::class, 'updateLancamento'])->name('financeiro.lancamentos.update');
    Route::delete('financeiro/lancamentos/{lancamento}', [FinanceiroController::class, 'destroyLancamento'])->name('financeiro.lancamentos.destroy');
    Route::get('site', [SitePublicoController::class, 'index'])->name('site.index');
    Route::resource('clientes', ClienteController::class);
    Route::resource('servicos', ServicoController::class)->except(['show']);
    Route::resource('profissionais', ProfissionalController::class)->parameters(['profissionais' => 'profissional']);
    Route::get('profissionais/{profissional}/horarios', [HorarioTrabalhoController::class, 'show'])->name('profissionais.horarios');
    Route::put('profissionais/{profissional}/horarios', [HorarioTrabalhoController::class, 'update'])->name('profissionais.horarios.update');

    Route::get('perfil', [PerfilController::class, 'show'])->name('perfil');
    Route::put('perfil', [PerfilController::class, 'update'])->name('perfil.update');

    Route::get('configuracoes', [ConfiguracaoController::class, 'show'])->name('configuracoes');
    Route::put('configuracoes/preferencias', [ConfiguracaoController::class, 'updatePreferencias'])->name('configuracoes.preferencias');
    Route::post('configuracoes/tipografia', [ConfiguracaoController::class, 'updateTipografia'])->name('configuracoes.tipografia');
    Route::post('configuracoes/preferencias/restaurar-tipografia', [ConfiguracaoController::class, 'resetTipografia'])->name('configuracoes.tipografia.reset');
    Route::get('configuracoes/empresa', [ConfiguracaoController::class, 'empresa'])->name('configuracoes.empresa');
    Route::put('configuracoes/empresa', [ConfiguracaoController::class, 'updateEmpresa'])->name('configuracoes.empresa.update');

    Route::get('planos', [PlansController::class, 'index'])->name('planos.index');
    Route::patch('planos', [PlansController::class, 'update'])->name('planos.update');
});

// Agendamento público — sem autenticação
Route::get('/vitrine/{slug}', [AgendamentoPublicoController::class, 'landing'])->name('vitrine.show');
Route::get('/agendar/{slug}', [AgendamentoPublicoController::class, 'show'])->name('agendar.show');
Route::post('/agendar/{slug}', [AgendamentoPublicoController::class, 'store'])->name('agendar.store');
Route::get('/agendar/{slug}/slots', [AgendamentoPublicoController::class, 'slots'])->name('agendar.slots');
Route::get('/agendar/{slug}/confirmado/{agendamento}', [AgendamentoPublicoController::class, 'confirmado'])->name('agendar.confirmado');

if (app()->isLocal()) {
    Route::post('/dev/login', [DevLoginController::class, 'login'])->name('dev.login');
}
