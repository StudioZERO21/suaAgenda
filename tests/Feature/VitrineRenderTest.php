<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\PortfolioItem;
use App\Models\Profissional;
use App\Models\Servico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

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

    it('exibe a galeria com as fotos publicadas que têm imagem', function () {
        Storage::disk('public')->put('portfolio/teste/foto.jpg', 'fake');
        PortfolioItem::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'titulo' => 'Degradê moderno', 'categoria' => 'Corte',
            'destaque' => true, 'publicado' => true, 'imagem_path' => 'portfolio/teste/foto.jpg',
        ]);

        $this->get(route('vitrine.show', $this->company->slug))
            ->assertOk()
            ->assertSee('id="galeria"', false)
            ->assertSee('Degradê moderno')
            ->assertSee('vitrineGaleria', false);
    });

    it('não exibe na galeria fotos não publicadas', function () {
        PortfolioItem::create([
            'company_id' => $this->company->id,
            'titulo' => 'Rascunho', 'categoria' => 'Corte',
            'publicado' => false, 'imagem_path' => 'portfolio/teste/draft.jpg',
        ]);

        $this->get(route('vitrine.show', $this->company->slug))
            ->assertOk()
            ->assertDontSee('id="galeria"', false)
            ->assertDontSee('Rascunho');
    });

    it('não exibe na galeria fotos cujo arquivo não existe mais', function () {
        PortfolioItem::create([
            'company_id' => $this->company->id,
            'titulo' => 'Arquivo sumido', 'categoria' => 'Corte',
            'publicado' => true, 'imagem_path' => 'portfolio/teste/inexistente.jpg',
        ]);

        $this->get(route('vitrine.show', $this->company->slug))
            ->assertOk()
            ->assertDontSee('id="galeria"', false)
            ->assertDontSee('Arquivo sumido');
    });

    it('não exibe a galeria quando não há fotos com imagem', function () {
        PortfolioItem::create([
            'company_id' => $this->company->id,
            'titulo' => 'Sem foto', 'categoria' => 'Corte', 'publicado' => true,
        ]);

        $this->get(route('vitrine.show', $this->company->slug))
            ->assertOk()
            ->assertDontSee('id="galeria"', false);
    });

    it('respeita o flag show_gallery=false nas configurações do site', function () {
        Storage::disk('public')->put('portfolio/teste/x.jpg', 'fake');
        PortfolioItem::create([
            'company_id' => $this->company->id,
            'titulo' => 'Oculta', 'categoria' => 'Corte',
            'publicado' => true, 'imagem_path' => 'portfolio/teste/x.jpg',
        ]);
        $this->company->update(['settings' => ['site' => ['show_gallery' => false]]]);

        $this->get(route('vitrine.show', $this->company->slug))
            ->assertOk()
            ->assertDontSee('id="galeria"', false);
    });
});
