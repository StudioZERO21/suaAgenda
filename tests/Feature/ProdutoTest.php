<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor',        'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista',      'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Teste',
        'slug' => 'empresa-teste',
        'plano' => 'trial',
        'whatsapp' => '(11) 99999-0000',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->produto = Produto::create([
        'company_id' => $this->company->id,
        'nome' => 'Pomada Teste',
        'sku' => 'TST001',
        'categoria' => 'Cabelo',
        'preco' => 45.90,
        'custo' => 22.00,
        'estoque' => 10,
        'unidade' => 'un.',
        'ativo' => true,
    ]);
});

describe('produtos index', function () {
    it('admin pode ver a página de produtos', function () {
        $this->actingAs($this->admin)
            ->get(route('produtos.index'))
            ->assertOk()
            ->assertSee('Produtos');
    });

    it('gestor pode ver a página de produtos', function () {
        $this->actingAs($this->gestor)
            ->get(route('produtos.index'))
            ->assertOk();
    });

    it('analista pode ver a página de produtos', function () {
        $this->actingAs($this->analista)
            ->get(route('produtos.index'))
            ->assertOk();
    });
});

describe('produtos CRUD', function () {
    it('admin pode criar produto', function () {
        $response = $this->actingAs($this->admin)
            ->postJson(route('produtos.store'), [
                'nome' => 'Gel Fixador',
                'sku' => 'GEL001',
                'categoria' => 'Cabelo',
                'preco' => 22.00,
                'custo' => 9.00,
                'estoque' => 15,
                'estoque_min' => 5,
                'unidade' => 'un.',
                'ativo' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['nome' => 'Gel Fixador']);

        expect(Produto::where('nome', 'Gel Fixador')->where('company_id', $this->company->id)->exists())->toBeTrue();
    });

    it('gestor pode criar produto', function () {
        $this->actingAs($this->gestor)
            ->postJson(route('produtos.store'), [
                'nome' => 'Produto Gestor',
                'preco' => 10.00,
                'estoque' => 5,
                'estoque_min' => 2,
                'unidade' => 'un.',
            ])
            ->assertStatus(201);
    });

    it('analista não pode criar produto', function () {
        $this->actingAs($this->analista)
            ->postJson(route('produtos.store'), [
                'nome' => 'Hack',
                'preco' => 10.00,
            ])
            ->assertForbidden();
    });

    it('admin pode atualizar produto', function () {
        $this->actingAs($this->admin)
            ->putJson(route('produtos.update', $this->produto), [
                'nome' => 'Pomada Atualizada',
                'preco' => 50.00,
                'custo' => 22.00,
                'estoque' => 10,
                'estoque_min' => 5,
                'unidade' => 'un.',
            ])
            ->assertOk()
            ->assertJsonFragment(['nome' => 'Pomada Atualizada']);

        expect($this->produto->fresh()->nome)->toBe('Pomada Atualizada');
    });

    it('admin pode deletar produto', function () {
        $this->actingAs($this->admin)
            ->deleteJson(route('produtos.destroy', $this->produto))
            ->assertNoContent();

        expect(Produto::find($this->produto->id))->toBeNull();
        expect(Produto::withTrashed()->find($this->produto->id))->not->toBeNull();
    });

    it('admin pode toggle ativo', function () {
        expect($this->produto->ativo)->toBeTrue();

        $this->actingAs($this->admin)
            ->patchJson(route('produtos.toggle', $this->produto))
            ->assertOk()
            ->assertJsonFragment(['ativo' => false]);

        expect($this->produto->fresh()->ativo)->toBeFalse();
    });

    it('nome é obrigatório', function () {
        $this->actingAs($this->admin)
            ->postJson(route('produtos.store'), ['preco' => 10.00, 'estoque' => 1, 'estoque_min' => 1, 'unidade' => 'un.'])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['nome']]);
    });

    it('isolamento: produto de outra empresa retorna 403', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra', 'plano' => 'trial', 'ativo' => true]);
        $produtoAlheio = Produto::create([
            'company_id' => $outra->id,
            'nome' => 'Produto Alheio',
            'preco' => 10.00,
            'estoque' => 1,
            'unidade' => 'un.',
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('produtos.destroy', $produtoAlheio))
            ->assertForbidden();
    });
});

describe('produto model', function () {
    it('estoqueStatus ok quando acima do mínimo', function () {
        expect($this->produto->estoqueStatus())->toBe('ok');
    });

    it('estoqueStatus baixo quando no limite', function () {
        $this->produto->update(['estoque' => 3, 'estoque_min' => 5]);
        expect($this->produto->fresh()->estoqueStatus())->toBe('baixo');
    });

    it('estoqueStatus zerado quando estoque é zero', function () {
        $this->produto->update(['estoque' => 0]);
        expect($this->produto->fresh()->estoqueStatus())->toBe('zerado');
    });
});
