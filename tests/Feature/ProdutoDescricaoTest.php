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
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia PD', 'slug' => 'barbearia-pd',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->produto = Produto::create([
        'company_id' => $this->company->id,
        'nome' => 'Pomada',
        'preco' => 30.0,
        'estoque' => 10,
        'unidade' => 'un',
        'ativo' => true,
    ]);
});

describe('produto_descricao', function () {
    it('admin pode atualizar descrição do produto', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('produtos.descricao', $this->produto), ['descricao' => 'Pomada modeladora forte'])
            ->assertOk()
            ->assertJsonStructure(['descricao', 'updated_at'])
            ->json();

        expect($data['descricao'])->toBe('Pomada modeladora forte');
        expect($this->produto->fresh()->descricao)->toBe('Pomada modeladora forte');
    });

    it('analista pode atualizar descrição (abort_if apenas)', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('produtos.descricao', $this->produto), ['descricao' => 'Texto analista'])
            ->assertOk();
    });

    it('descrição nula limpa o campo', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('produtos.descricao', $this->produto), ['descricao' => null])
            ->assertOk()
            ->json();

        expect($data['descricao'])->toBe('');
    });

    it('descrição acima de 500 chars é rejeitada', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('produtos.descricao', $this->produto), ['descricao' => str_repeat('x', 501)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['descricao']);
    });

    it('não pode atualizar produto de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-pd', 'plano' => 'trial', 'ativo' => true]);
        $prodOutra = Produto::create([
            'company_id' => $outra->id, 'nome' => 'X', 'preco' => 10.0,
            'estoque' => 1, 'unidade' => 'un', 'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('produtos.descricao', $prodOutra), ['descricao' => 'Hack'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('produtos.descricao', $this->produto), ['descricao' => 'Teste'])
            ->assertUnauthorized();
    });
});
