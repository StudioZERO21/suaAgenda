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
        'name' => 'Barbearia PNome', 'slug' => 'barbearia-pnome',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->produto = Produto::create([
        'company_id' => $this->company->id,
        'nome' => 'Pomada Antiga',
        'preco' => 30.0,
        'estoque' => 10,
        'unidade' => 'un',
        'ativo' => true,
    ]);
});

describe('produto_nome', function () {
    it('admin pode atualizar nome do produto', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('produtos.nome', $this->produto), ['nome' => 'Pomada Nova'])
            ->assertOk()
            ->assertJsonStructure(['nome', 'updated_at'])
            ->json();

        expect($data['nome'])->toBe('Pomada Nova');
        expect($this->produto->fresh()->nome)->toBe('Pomada Nova');
    });

    it('analista pode atualizar nome (abort_if apenas)', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('produtos.nome', $this->produto), ['nome' => 'Pomada Analista'])
            ->assertOk();
    });

    it('nome vazio é rejeitado', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('produtos.nome', $this->produto), ['nome' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nome']);
    });

    it('nome acima de 100 chars é rejeitado', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('produtos.nome', $this->produto), ['nome' => str_repeat('x', 101)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nome']);
    });

    it('não pode atualizar produto de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-pnome', 'plano' => 'trial', 'ativo' => true]);
        $prodOutra = Produto::create([
            'company_id' => $outra->id, 'nome' => 'X', 'preco' => 10.0,
            'estoque' => 1, 'unidade' => 'un', 'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('produtos.nome', $prodOutra), ['nome' => 'Hack'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('produtos.nome', $this->produto), ['nome' => 'Teste'])
            ->assertUnauthorized();
    });
});
