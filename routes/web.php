<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
Route::middleware('guest')->group(function () {
    Route::get('/login',[AuthController::class,'showLogin'])->name('login');
    Route::post('/login',[AuthController::class,'login']);
});
Route::post('/logout',[AuthController::class,'logout'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function () {
    Route::get('/',fn()=>redirect()->route('dashboard'));
    Route::get('/dashboard',[DashboardController::class,'index'])->name('dashboard');
});