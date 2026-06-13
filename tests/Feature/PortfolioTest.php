<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\PortfolioItem;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Port',
        'slug' => 'empresa-port',
        'plano' => 'trial',
        'whatsapp' => '(11) 99999-0010',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'João Silva',
        'especialidade' => 'Corte',
        'comissao_pct' => 30,
        'ativo' => true,
    ]);

    $this->company2 = Company::create([
        'name' => 'Outra Empresa',
        'slug' => 'outra-empresa-port',
        'plano' => 'trial',
        'whatsapp' => '(11) 99999-0011',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->admin2 = User::factory()->create(['empresa_id' => $this->company2->id]);
    $this->admin2->assignRole('admin_empresa');
});

describe('portfolio crud', function () {
    it('index carrega a view', function () {
        $this->actingAs($this->admin)
            ->get(route('portfolio.index'))
            ->assertOk();
    });

    it('admin pode adicionar foto e recebe json 201', function () {
        $this->actingAs($this->admin)
            ->postJson(route('portfolio.fotos.store'), [
                'titulo' => 'Degradê moderno',
                'categoria' => 'Corte',
                'profissional_id' => $this->profissional->id,
            ])
            ->assertStatus(201)
            ->assertJsonStructure(['id', 'titulo', 'categoria', 'destaque', 'prof'])
            ->assertJson(['titulo' => 'Degradê moderno', 'destaque' => false]);

        expect(PortfolioItem::where('company_id', $this->company->id)->count())->toBe(1);
    });

    it('admin pode adicionar foto sem profissional', function () {
        $this->actingAs($this->admin)
            ->postJson(route('portfolio.fotos.store'), [
                'titulo' => 'Sem profissional',
                'categoria' => 'Barba',
            ])
            ->assertStatus(201)
            ->assertJson(['prof' => '—']);
    });

    it('validação rejeita titulo vazio', function () {
        $this->actingAs($this->admin)
            ->postJson(route('portfolio.fotos.store'), ['titulo' => '', 'categoria' => 'Corte'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['titulo']);
    });

    it('não aceita profissional de outra empresa', function () {
        $profOutro = Profissional::create([
            'company_id' => $this->company2->id,
            'name' => 'Estranho',
            'especialidade' => 'Corte',
            'comissao_pct' => 30,
            'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('portfolio.fotos.store'), [
                'titulo' => 'Teste',
                'categoria' => 'Corte',
                'profissional_id' => $profOutro->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['profissional_id']);
    });

    it('admin pode excluir foto e recebe 204', function () {
        $item = PortfolioItem::create([
            'company_id' => $this->company->id,
            'titulo' => 'Para excluir',
            'categoria' => 'Corte',
            'destaque' => false,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('portfolio.fotos.destroy', $item))
            ->assertNoContent();

        expect(PortfolioItem::find($item->id))->toBeNull();
        expect(PortfolioItem::withTrashed()->find($item->id))->not->toBeNull();
    });

    it('não pode excluir foto de outra empresa', function () {
        $item = PortfolioItem::create([
            'company_id' => $this->company2->id,
            'titulo' => 'Alheio',
            'categoria' => 'Corte',
            'destaque' => false,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('portfolio.fotos.destroy', $item))
            ->assertNotFound();
    });

    it('admin pode marcar foto como destaque', function () {
        $item = PortfolioItem::create([
            'company_id' => $this->company->id,
            'titulo' => 'Destaque',
            'categoria' => 'Corte',
            'destaque' => false,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('portfolio.fotos.toggle', $item))
            ->assertOk()
            ->assertJson(['destaque' => true]);

        expect((bool) $item->fresh()->destaque)->toBeTrue();
    });

    it('toggle remove destaque se já estava marcado', function () {
        $item = PortfolioItem::create([
            'company_id' => $this->company->id,
            'titulo' => 'Já destaque',
            'categoria' => 'Corte',
            'destaque' => true,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('portfolio.fotos.toggle', $item))
            ->assertOk()
            ->assertJson(['destaque' => false]);
    });

    it('não pode alterar foto de outra empresa via toggle', function () {
        $item = PortfolioItem::create([
            'company_id' => $this->company2->id,
            'titulo' => 'Alheio',
            'categoria' => 'Corte',
            'destaque' => false,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('portfolio.fotos.toggle', $item))
            ->assertNotFound();
    });
});

describe('portfolio upload', function () {
    it('foto enviada com imagem já entra publicada', function () {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post(route('portfolio.fotos.store'), [
                'arquivo' => UploadedFile::fake()->image('trabalho.jpg', 800, 600),
                'titulo' => 'Trabalho novo',
                'categoria' => 'Corte',
            ])
            ->assertStatus(201)
            ->assertJson(['publicado' => true]);

        $item = PortfolioItem::where('company_id', $this->company->id)->first();
        expect($item->publicado)->toBeTrue()
            ->and($item->imagem_path)->not->toBeNull();
        Storage::disk('public')->assertExists($item->imagem_path);
    });

    it('foto sem imagem (demonstração) não entra publicada', function () {
        $this->actingAs($this->admin)
            ->postJson(route('portfolio.fotos.store'), [
                'titulo' => 'Sem imagem', 'categoria' => 'Corte',
            ])
            ->assertStatus(201)
            ->assertJson(['publicado' => false]);
    });
});

describe('portfolio publicação', function () {
    it('publicar em massa publica todas as fotos com imagem', function () {
        $comImg1 = PortfolioItem::create([
            'company_id' => $this->company->id, 'titulo' => 'A', 'categoria' => 'Corte',
            'imagem_path' => 'portfolio/a.jpg',
        ]);
        $comImg2 = PortfolioItem::create([
            'company_id' => $this->company->id, 'titulo' => 'B', 'categoria' => 'Corte',
            'imagem_path' => 'portfolio/b.jpg',
        ]);
        $semImg = PortfolioItem::create([
            'company_id' => $this->company->id, 'titulo' => 'C', 'categoria' => 'Corte',
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('portfolio.publicar'))
            ->assertOk()
            ->assertJson(['total' => 2]);

        expect($comImg1->fresh()->publicado)->toBeTrue()
            ->and($comImg2->fresh()->publicado)->toBeTrue()
            ->and($semImg->fresh()->publicado)->toBeFalse();
    });

    it('togglePublicado publica e despublica uma foto com imagem', function () {
        $item = PortfolioItem::create([
            'company_id' => $this->company->id, 'titulo' => 'Foto', 'categoria' => 'Corte',
            'imagem_path' => 'portfolio/x.jpg',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('portfolio.fotos.publicar', $item))
            ->assertOk()->assertJson(['publicado' => true]);
        expect($item->fresh()->publicado)->toBeTrue();

        $this->actingAs($this->admin)
            ->patchJson(route('portfolio.fotos.publicar', $item))
            ->assertOk()->assertJson(['publicado' => false]);
        expect($item->fresh()->publicado)->toBeFalse();
    });

    it('não publica foto sem imagem', function () {
        $item = PortfolioItem::create([
            'company_id' => $this->company->id, 'titulo' => 'Sem img', 'categoria' => 'Corte',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('portfolio.fotos.publicar', $item))
            ->assertOk()->assertJson(['publicado' => false]);

        expect($item->fresh()->publicado)->toBeFalse();
    });

    it('não publica foto de outra empresa', function () {
        $item = PortfolioItem::create([
            'company_id' => $this->company2->id, 'titulo' => 'Alheio', 'categoria' => 'Corte',
            'imagem_path' => 'portfolio/y.jpg',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('portfolio.fotos.publicar', $item))
            ->assertNotFound();
    });

    it('publicar só afeta a própria empresa', function () {
        PortfolioItem::create([
            'company_id' => $this->company2->id, 'titulo' => 'Outra', 'categoria' => 'Corte',
            'imagem_path' => 'portfolio/z.jpg',
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('portfolio.publicar'))
            ->assertOk()
            ->assertJson(['total' => 0]);
    });
});
