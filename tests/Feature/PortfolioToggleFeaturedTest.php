<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\PortfolioItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Portfolio', 'slug' => 'barbearia-portfolio',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->item = PortfolioItem::create([
        'company_id' => $this->company->id,
        'titulo' => 'Corte fade',
        'categoria' => 'Cortes',
        'destaque' => false,
    ]);
});

describe('portfolio_toggle_featured', function () {
    it('admin pode marcar item como destaque', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('portfolio.fotos.toggle', $this->item))
            ->assertOk()
            ->json();

        expect($data['destaque'])->toBeTrue();
        expect($this->item->fresh()->destaque)->toBeTrue();
    });

    it('toggle inverte destaque de true para false', function () {
        $this->item->update(['destaque' => true]);

        $data = $this->actingAs($this->admin)
            ->patchJson(route('portfolio.fotos.toggle', $this->item))
            ->assertOk()
            ->json();

        expect($data['destaque'])->toBeFalse();
        expect($this->item->fresh()->destaque)->toBeFalse();
    });

    it('resposta contém id e destaque', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('portfolio.fotos.toggle', $this->item))
            ->assertOk()
            ->assertJsonStructure(['id', 'destaque'])
            ->json();

        expect($data['id'])->toBe($this->item->id);
    });

    it('não pode alterar item de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-pf', 'plano' => 'trial', 'ativo' => true]);
        $itemOutra = PortfolioItem::create([
            'company_id' => $outra->id, 'titulo' => 'X', 'categoria' => 'Outros', 'destaque' => false,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('portfolio.fotos.toggle', $itemOutra))
            ->assertForbidden();
    });

    it('analista pode alternar destaque', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('portfolio.fotos.toggle', $this->item))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('portfolio.fotos.toggle', $this->item))
            ->assertUnauthorized();
    });
});
