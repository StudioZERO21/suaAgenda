<?php

declare(strict_types=1);

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'name' => 'Barbearia Reservas', 'slug' => 'barbearia-reservas',
        'plano' => 'trial', 'ativo' => true,
    ]);
});

describe('minhas_reservas', function () {
    // A busca por telefone (sem autenticação) foi aposentada por expor a
    // agenda de qualquer cliente. Agora redireciona para o portal autenticado.
    it('redireciona para o portal autenticado do cliente', function () {
        $this->get(route('vitrine.minhas-reservas', $this->company->slug))
            ->assertRedirect(route('portal.entrar', $this->company->slug));
    });

    it('ignora telefone na query e ainda redireciona ao portal', function () {
        $this->get(route('vitrine.minhas-reservas', $this->company->slug).'?phone=11999990001')
            ->assertRedirect(route('portal.entrar', $this->company->slug));
    });
});
