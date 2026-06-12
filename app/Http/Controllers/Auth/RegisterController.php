<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Company;
use App\Models\User;
use App\Services\GrupoAcessoProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $company = Company::create([
                'name' => $request->company_name,
                'slug' => Str::slug($request->company_name).'-'.Str::random(6),
                'plano' => 'trial',
                'lgpd_consent' => true,
                'trial_ends_at' => now()->addDays(7),
                'ativo' => true,
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'empresa_id' => $company->id,
                'ativo' => true,
            ]);

            $user->assignRole('admin_empresa');

            app(GrupoAcessoProvisioner::class)->provision($company);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        Log::channel('security')->info('registro_novo', ['user_id' => $user->id, 'ip' => $request->ip()]);

        return redirect()->route('dashboard')->with('success', 'Conta criada com sucesso! Seu trial de 7 dias começou.');
    }
}
