<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePerfilRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function show(): View
    {
        return view('perfil.index', ['user' => auth()->user()]);
    }

    public function update(UpdatePerfilRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $data = ['name' => $request->name, 'email' => $request->email];

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $user->update($data);

        return redirect()->route('perfil')->with('success', 'Perfil atualizado com sucesso.');
    }
}
