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
        'name' => 'Barbearia MR', 'slug' => 'barbearia-mr',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

function makeProdMR(string $companyId, string $nome, float $preco, float $custo, bool $ativo = true): Produto
{
    return Produto::create([
        'company_id' => $companyId,
        'nome' => $nome,
        'preco' => $preco,
        'custo' => $custo,
        'estoque' => 10,
        'estoque_min' => 2,
        'ativo' => $ativo,
    ]);
}

describe('produto_mais_rentaveis', function () {
    it('retorna estrutura correta sem produtos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.mais-rentaveis'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['total', 'items']);
        expect($data['total'])->toBe(0);
        expect($data['items'])->toBeEmpty();
    });

    it('items têm campos corretos', function () {
        makeProdMR($this->company->id, 'Pomada MR', 50.0, 20.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.mais-rentaveis'))
            ->assertOk()
            ->json();

        $item = $data['items'][0];
        expect($item)->toHaveKeys(['id', 'nome', 'categoria', 'preco', 'custo', 'lucro_unitario', 'margem_pct', 'estoque', 'valor_lucro_estoque', 'ativo']);
        expect((float) $item['lucro_unitario'])->toBe(30.0);
        expect((float) $item['margem_pct'])->toBe(60.0);
    });

    it('ordena por margem descendente', function () {
        makeProdMR($this->company->id, 'Produto Baixa Margem MR', 100.0, 90.0);
        makeProdMR($this->company->id, 'Produto Alta Margem MR', 100.0, 20.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.mais-rentaveis'))
            ->assertOk()
            ->json();

        expect($data['items'][0]['nome'])->toBe('Produto Alta Margem MR');
        expect($data['items'][1]['nome'])->toBe('Produto Baixa Margem MR');
    });

    it('exclui produtos sem custo cadastrado', function () {
        Produto::create([
            'company_id' => $this->company->id,
            'nome' => 'Sem Custo MR',
            'preco' => 50,
            'estoque' => 5,
            'estoque_min' => 1,
            'ativo' => true,
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.mais-rentaveis'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('ignora produtos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra MR', 'slug' => 'outra-mr', 'plano' => 'trial', 'ativo' => true]);
        makeProdMR($outra->id, 'Prod Outra MR', 100.0, 40.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('produtos.mais-rentaveis'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('produtos.mais-rentaveis'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('produtos.mais-rentaveis'))
            ->assertUnauthorized();
    });
});
