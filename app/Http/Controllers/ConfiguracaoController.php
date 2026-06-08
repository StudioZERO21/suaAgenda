<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateConfiguracaoRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConfiguracaoController extends Controller
{
    public function show(): View
    {
        $company = Company::findOrFail(auth()->user()->empresa_id);

        $this->authorize('view', $company);

        return view('configuracoes.index', compact('company'));
    }

    public function update(UpdateConfiguracaoRequest $request): RedirectResponse
    {
        $company = Company::findOrFail(auth()->user()->empresa_id);

        $this->authorize('update', $company);

        $company->update([
            'name' => $request->name,
            'whatsapp' => $request->whatsapp,
            'lgpd_consent' => $request->boolean('lgpd_consent'),
        ]);

        return redirect()->route('configuracoes')->with('success', 'Configurações salvas com sucesso!');
    }
}
