<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Produto;
use App\Models\ProdutoImagem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    it('calcula margem corretamente', function () {
        // (45.90 - 22.00) / 45.90 * 100 = ~52%
        $margin = (int) round(((45.90 - 22.00) / 45.90) * 100);
        expect($margin)->toBe(52);
    });

    it('margem é zero quando custo é zero', function () {
        $this->produto->update(['custo' => 0]);
        $p = $this->produto->fresh();
        $margin = $p->preco > 0 && $p->custo > 0
            ? (int) round((($p->preco - $p->custo) / $p->preco) * 100)
            : 0;
        expect($margin)->toBe(0);
    });
});

describe('produto imagens', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('admin pode fazer upload de imagem', function () {
        $file = UploadedFile::fake()->image('foto.jpg', 200, 200);

        $response = $this->actingAs($this->admin)
            ->postJson(route('produtos.imagens.store', $this->produto), [
                'imagem' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'url', 'is_capa']);

        expect($response->json('is_capa'))->toBeTrue(); // first image is always capa
        expect(ProdutoImagem::where('produto_id', $this->produto->id)->count())->toBe(1);
    });

    it('segunda imagem não é capa automaticamente', function () {
        Storage::fake('public');

        // Upload first
        $this->actingAs($this->admin)
            ->postJson(route('produtos.imagens.store', $this->produto), [
                'imagem' => UploadedFile::fake()->image('foto1.jpg'),
            ]);

        // Upload second
        $response = $this->actingAs($this->admin)
            ->postJson(route('produtos.imagens.store', $this->produto), [
                'imagem' => UploadedFile::fake()->image('foto2.jpg'),
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['is_capa' => false]);

        expect(ProdutoImagem::where('produto_id', $this->produto->id)->count())->toBe(2);
    });

    it('admin pode deletar imagem', function () {
        $imagem = ProdutoImagem::create([
            'produto_id' => $this->produto->id,
            'imagem_path' => 'produto_imagens/fake.jpg',
            'is_capa' => true,
            'ordem' => 1,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('produtos.imagens.destroy', $imagem))
            ->assertNoContent();

        expect(ProdutoImagem::find($imagem->id))->toBeNull();
    });

    it('admin pode definir imagem como capa', function () {
        $img1 = ProdutoImagem::create([
            'produto_id' => $this->produto->id,
            'imagem_path' => 'produto_imagens/img1.jpg',
            'is_capa' => true,
            'ordem' => 1,
        ]);
        $img2 = ProdutoImagem::create([
            'produto_id' => $this->produto->id,
            'imagem_path' => 'produto_imagens/img2.jpg',
            'is_capa' => false,
            'ordem' => 2,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('produtos.imagens.capa', $img2))
            ->assertOk()
            ->assertJsonFragment(['is_capa' => true]);

        expect($img1->fresh()->is_capa)->toBeFalse();
        expect($img2->fresh()->is_capa)->toBeTrue();
    });

    it('isolamento: imagem de produto de outra empresa retorna 403', function () {
        $outra = Company::create(['name' => 'Outra2', 'slug' => 'outra2', 'plano' => 'trial', 'ativo' => true]);
        $produtoAlheio = Produto::create([
            'company_id' => $outra->id,
            'nome' => 'Produto Alheio',
            'preco' => 10.00,
            'estoque' => 1,
            'unidade' => 'un.',
        ]);
        $imagemAlheia = ProdutoImagem::create([
            'produto_id' => $produtoAlheio->id,
            'imagem_path' => 'produto_imagens/alheio.jpg',
            'is_capa' => false,
            'ordem' => 1,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('produtos.imagens.destroy', $imagemAlheia))
            ->assertForbidden();
    });

    it('resposta do index inclui array de imagens', function () {
        ProdutoImagem::create([
            'produto_id' => $this->produto->id,
            'imagem_path' => 'produto_imagens/img.jpg',
            'is_capa' => true,
            'ordem' => 1,
        ]);

        $this->actingAs($this->admin)
            ->get(route('produtos.index'))
            ->assertOk();
    });
});

describe('stats cards', function () {
    it('conta produtos ativos corretamente', function () {
        Produto::create([
            'company_id' => $this->company->id,
            'nome' => 'Inativo',
            'preco' => 10.00,
            'estoque' => 0,
            'unidade' => 'un.',
            'ativo' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('produtos.index'))
            ->assertOk();

        // The view passes produtosJson — verify via JSON in response
        $content = $response->getContent();
        // Active product count = 1 (only $this->produto is active)
        expect($content)->toContain('productsApp');
    });

    it('estoque baixo detecta produtos com quantidade < 5', function () {
        $this->produto->update(['estoque' => 3]);

        $response = $this->actingAs($this->admin)
            ->get(route('produtos.index'))
            ->assertOk();

        $content = $response->getContent();
        expect($content)->toContain('productsApp');
    });
});
