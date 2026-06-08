<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlansController extends Controller
{
    public function index(): View
    {
        $plans = Plan::ordered();
        $company = auth()->user()->company;
        $currentPlan = $company?->plan ?? Plan::find('starter');

        return view('planos.index', compact('plans', 'company', 'currentPlan'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('update', auth()->user()->company);

        $validated = $request->validate([
            'plan_slug' => ['required', 'string', 'exists:plans,slug'],
        ]);

        auth()->user()->company->update(['plan_slug' => $validated['plan_slug']]);

        return back()->with('success', 'Plano atualizado com sucesso.');
    }
}
