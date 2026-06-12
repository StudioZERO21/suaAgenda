<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Produto;
use App\Models\ProdutoImagem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Imagem', 'slug' => 'barbearia-imagem',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->produto = Produto::create([
        'company_id' => $this->company->id, 'nome' => 'Pomada',
        'preco' => 30.0, 'estoque' => 10, 'ativo' => true,
    ]);
});

describe('produto_imagem_store', function () {
    it('admin pode fazer upload de imagem para um produto', function () {
        $file = UploadedFile::fake()->image('pomada.jpg', 800, 800);

        $data = $this->actingAs($this->admin)
            ->postJson(route('produtos.imagens.store', $this->produto), ['imagem' => $file])
            ->assertCreated()
            ->assertJsonStructure(['id', 'url', 'is_capa'])
            ->json();

        expect($data['is_capa'])->toBeTrue();
        Storage::disk('public')->assertExists(
            ProdutoImagem::find($data['id'])->imagem_path
        );
    });

    it('segunda imagem não é capa automaticamente', function () {
        $first = UploadedFile::fake()->image('first.jpg');
        $this->actingAs($this->admin)
            ->postJson(route('produtos.imagens.store', $this->produto), ['imagem' => $first]);

        $second = UploadedFile::fake()->image('second.jpg');
        $data = $this->actingAs($this->admin)
            ->postJson(route('produtos.imagens.store', $this->produto), ['imagem' => $second])
            ->json();

        expect($data['is_capa'])->toBeFalse();
    });

    it('rejeita arquivo não imagem', function () {
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $this->actingAs($this->admin)
            ->postJson(route('produtos.imagens.store', $this->produto), ['imagem' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['imagem']);
    });

    it('não pode fazer upload em produto de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-img', 'plano' => 'trial', 'ativo' => true]);
        $prodOutra = Produto::create([
            'company_id' => $outra->id, 'nome' => 'X', 'preco' => 5.0, 'estoque' => 0, 'ativo' => true,
        ]);

        $file = UploadedFile::fake()->image('test.jpg');
        $this->actingAs($this->admin)
            ->postJson(route('produtos.imagens.store', $prodOutra), ['imagem' => $file])
            ->assertNotFound();
    });
});

describe('produto_imagem_destroy', function () {
    it('admin pode remover imagem', function () {
        $file = UploadedFile::fake()->image('to-delete.jpg');
        $createData = $this->actingAs($this->admin)
            ->postJson(route('produtos.imagens.store', $this->produto), ['imagem' => $file])
            ->json();

        $this->actingAs($this->admin)
            ->deleteJson(route('produtos.imagens.destroy', $createData['id']))
            ->assertNoContent();

        expect(ProdutoImagem::find($createData['id']))->toBeNull();
    });

    it('não pode remover imagem de produto de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra2', 'slug' => 'outra-img2', 'plano' => 'trial', 'ativo' => true]);
        $prodOutra = Produto::create([
            'company_id' => $outra->id, 'nome' => 'Y', 'preco' => 5.0, 'estoque' => 0, 'ativo' => true,
        ]);
        $imagem = $prodOutra->imagens()->create(['imagem_path' => 'fake.jpg', 'is_capa' => false, 'ordem' => 1]);

        $this->actingAs($this->admin)
            ->deleteJson(route('produtos.imagens.destroy', $imagem))
            ->assertNotFound();
    });
});

describe('produto_imagem_set_capa', function () {
    it('admin pode definir imagem como capa', function () {
        $f1 = UploadedFile::fake()->image('img1.jpg');
        $f2 = UploadedFile::fake()->image('img2.jpg');

        $d1 = $this->actingAs($this->admin)
            ->postJson(route('produtos.imagens.store', $this->produto), ['imagem' => $f1])->json();
        $d2 = $this->actingAs($this->admin)
            ->postJson(route('produtos.imagens.store', $this->produto), ['imagem' => $f2])->json();

        $data = $this->actingAs($this->admin)
            ->patchJson(route('produtos.imagens.capa', $d2['id']))
            ->assertOk()
            ->json();

        expect($data['is_capa'])->toBeTrue();
        expect(ProdutoImagem::find($d1['id'])->is_capa)->toBeFalse();
    });

    it('não pode setar capa de produto de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra3', 'slug' => 'outra-img3', 'plano' => 'trial', 'ativo' => true]);
        $prodOutra = Produto::create([
            'company_id' => $outra->id, 'nome' => 'Z', 'preco' => 5.0, 'estoque' => 0, 'ativo' => true,
        ]);
        $imagem = $prodOutra->imagens()->create(['imagem_path' => 'fake2.jpg', 'is_capa' => false, 'ordem' => 1]);

        $this->actingAs($this->admin)
            ->patchJson(route('produtos.imagens.capa', $imagem))
            ->assertNotFound();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('produtos.imagens.capa', 'fake-id'))
            ->assertUnauthorized();
    });
});
