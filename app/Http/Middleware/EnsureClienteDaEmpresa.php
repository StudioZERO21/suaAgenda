<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que o cliente autenticado pertence à empresa do slug
 * — impede que a sessão de uma empresa acesse o portal de outra.
 */
class EnsureClienteDaEmpresa
{
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('slug');
        $cliente = Auth::guard('cliente')->user();

        if ($cliente === null) {
            return redirect()->route('portal.entrar', $slug);
        }

        $company = Company::where('slug', $slug)->first();

        if ($company === null || $cliente->company_id !== $company->id) {
            Auth::guard('cliente')->logout();

            return redirect()->route('portal.entrar', $slug)
                ->with('erro', 'Sessão inválida. Entre novamente.');
        }

        return $next($request);
    }
}
