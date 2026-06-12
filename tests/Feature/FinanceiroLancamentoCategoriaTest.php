<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Lancamento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia FLCat', 'slug' => 'barbearia-flcat',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->lancamento = Lancamento::create([
        'company_id' => $this->company->id,
        'tipo' => 'receita',
        'descricao' => 'Corte',
        'valor' => 50.00,
        'data' => now()->toDateString(),
        'status' => 'pago',
    ]);
});

describe('financeiro_lancamento_categoria', function () {
    it('admin atualiza categoria do lançamento', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('financeiro.lancamentos.categoria', $this->lancamento), ['categoria' => 'Serviços'])
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['categoria', 'updated_at']);
        expect($data['categoria'])->toBe('Serviços');
        expect($this->lancamento->fresh()->categoria)->toBe('Serviços');
    });

    it('analista pode atualizar categoria', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('financeiro.lancamentos.categoria', $this->lancamento), ['categoria' => 'Produtos'])
            ->assertOk();
    });

    it('categoria nula limpa o campo', function () {
        $this->lancamento->update(['categoria' => 'Antiga']);

        $data = $this->actingAs($this->admin)
            ->patchJson(route('financeiro.lancamentos.categoria', $this->lancamento), ['categoria' => null])
            ->assertOk()
            ->json();

        expect($data['categoria'])->toBe('');
    });

    it('categoria acima de 60 caracteres retorna 422', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('financeiro.lancamentos.categoria', $this->lancamento), ['categoria' => str_repeat('A', 61)])
            ->assertUnprocessable();
    });

    it('não pode atualizar lançamento de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-flcat', 'plano' => 'trial', 'ativo' => true]);
        $lancOutra = Lancamento::create([
            'company_id' => $outra->id, 'tipo' => 'receita', 'descricao' => 'X', 'valor' => 10.00,
            'data' => now()->toDateString(), 'status' => 'pago',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('financeiro.lancamentos.categoria', $lancOutra), ['categoria' => 'Hack'])
            ->assertNotFound();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('financeiro.lancamentos.categoria', $this->lancamento), ['categoria' => 'X'])
            ->assertUnauthorized();
    });
});
