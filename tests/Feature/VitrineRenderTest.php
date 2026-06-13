<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'name' => 'Barbearia Vitrine', 'slug' => 'barbearia-vitrine',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id, 'name' => 'Carlos',
        'especialidade' => 'Barbeiro', 'ativo' => true,
    ]);
    $this->profissional->servicos()->attach($this->servico->id);
});

describe('vitrine_render', function () {
    it('renderiza a vitrine com a equipe e o botão Ver Horários', function () {
        $this->get(route('vitrine.show', $this->company->slug))
            ->assertOk()
            ->assertViewIs('public.vitrine')
            ->assertSee('Nossa Equipe')
            ->assertSee('Ver Horários')
            ->assertSee('Carlos');
    });

    it('a vitrine inclui o modal de agendamento (sem caracteres quebrados)', function () {
        $this->get(route('vitrine.show', $this->company->slug))
            ->assertOk()
            ->assertSee('Escolha o serviço')
            ->assertSee('Confirmar agendamento')
            ->assertDontSee('�');
    });

    it('/agendar redireciona para a vitrine com o modal', function () {
        $this->get(route('agendar.show', $this->company->slug))
            ->assertRedirect(route('vitrine.show', ['slug' => $this->company->slug, 'book' => 1]));
    });
});
