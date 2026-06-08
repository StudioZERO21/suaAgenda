<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\PortfolioItem;
use App\Models\Profissional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

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
            ->assertForbidden();
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
            ->assertForbidden();
    });
});
