<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'name' => 'Vitrine Barbearia', 'slug' => 'vitrine-barbearia',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true,
    ]);

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true,
    ]);
});

describe('vitrine_landing', function () {
    it('exibe a vitrine pública pelo slug', function () {
        $this->get(route('vitrine.show', $this->company->slug))
            ->assertOk()
            ->assertViewIs('public.vitrine')
            ->assertSee($this->company->name);
    });

    it('retorna 404 para slug inexistente', function () {
        $this->get(route('vitrine.show', 'slug-nao-existe'))
            ->assertNotFound();
    });

    it('retorna 404 para empresa inativa', function () {
        $this->company->update(['ativo' => false]);
        $this->get(route('vitrine.show', $this->company->slug))
            ->assertNotFound();
    });

    it('exibe lista de serviços ativos na vitrine', function () {
        $this->get(route('vitrine.show', $this->company->slug))
            ->assertOk()
            ->assertViewHas('servicos');
    });

    it('não exibe serviço inativo na vitrine', function () {
        $inativo = Servico::create([
            'company_id' => $this->company->id, 'nome' => 'ServiçoInativo',
            'duracao_minutos' => 30, 'preco' => 20.0, 'cor' => '#000', 'ativo' => false,
        ]);

        $response = $this->get(route('vitrine.show', $this->company->slug));
        $response->assertOk();
        $servicos = $response->viewData('servicos');
        expect($servicos->pluck('id')->contains($inativo->id))->toBeFalse();
    });
});

describe('minhas_reservas', function () {
    // Busca por telefone aposentada (expunha agenda de qualquer cliente):
    // agora redireciona para o portal autenticado por link mágico.
    it('redireciona para o portal autenticado', function () {
        $this->get(route('vitrine.minhas-reservas', $this->company->slug))
            ->assertRedirect(route('portal.entrar', $this->company->slug));
    });

    it('ignora telefone na query e ainda redireciona ao portal', function () {
        $this->get(route('vitrine.minhas-reservas', $this->company->slug).'?phone=11999990088')
            ->assertRedirect(route('portal.entrar', $this->company->slug));
    });
});
