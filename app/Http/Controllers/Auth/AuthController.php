<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $devUsers = collect();
        if (app()->isLocal()) {
            $devUsers = User::with('roles')
                ->whereIn('email', [
                    'adrianoelite@msn.com',
                    'adrianoelite1980@gmail.com',
                    'carlos@barbearia.test',
                    'joao@barbearia.test',
                    'maria@cliente.test',
                ])
                ->get();
        }

        return view('auth.login', compact('devUsers'));
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        if (Auth::attempt($request->credentials(), $request->boolean('remember'))) {
            RateLimiter::clear($request->throttleKey());
            $request->session()->regenerate();
            Log::channel('security')->info('login_sucesso', ['user_id' => Auth::id(), 'ip' => $request->ip()]);

            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($request->throttleKey());
        Log::channel('security')->warning('login_falho', ['email' => $request->email, 'ip' => $request->ip()]);

        return back()->withErrors(['email' => 'E-mail ou senha incorretos.'])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Log::channel('security')->info('logout', ['user_id' => Auth::id(), 'ip' => $request->ip()]);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
