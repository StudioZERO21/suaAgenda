<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        if (Auth::attempt($request->credentials(), $request->boolean('remember'))) {
            $request->session()->regenerate();
            Log::channel('security')->info('login_sucesso', ['user_id' => Auth::id(), 'ip' => $request->ip()]);

            return redirect()->intended(route('dashboard'));
        }

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
