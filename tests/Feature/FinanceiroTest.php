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
        'name' => 'Financeiro Barbearia',
        'slug' => 'financeiro-barbearia',
        'plano' => 'starter',
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

$lancamentoPayload = fn (array $merge = []) => array_merge([
    'tipo' => 'receita',
    'descricao' => 'Serviço manual',
    'categoria' => 'servico',
    'valor' => 80.00,
    'data' => now()->toDateString(),
    'status' => 'pago',
], $merge);

describe('financeiro_index', function () {
    it('admin acessa a página financeiro', function () {
        $this->actingAs($this->admin)
            ->get(route('financeiro'))
            ->assertOk()
            ->assertViewIs('financeiro.index');
    });

    it('analista acessa a página financeiro', function () {
        $this->actingAs($this->analista)
            ->get(route('financeiro'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->get(route('financeiro'))->assertRedirect();
    });
});

describe('financeiro_lancamentos', function () use (&$lancamentoPayload) {
    it('admin pode criar lançamento', function () use (&$lancamentoPayload) {
        $this->actingAs($this->admin)
            ->postJson(route('financeiro.lancamentos.store'), $lancamentoPayload())
            ->assertCreated()
            ->assertJsonStructure(['id', 'valor', 'tipo', 'status']);

        expect(Lancamento::where('company_id', $this->company->id)->count())->toBe(1);
    });

    it('gestor pode criar lançamento', function () use (&$lancamentoPayload) {
        $this->actingAs($this->gestor)
            ->postJson(route('financeiro.lancamentos.store'), $lancamentoPayload())
            ->assertCreated();
    });

    it('analista não pode criar lançamento', function () use (&$lancamentoPayload) {
        $this->actingAs($this->analista)
            ->postJson(route('financeiro.lancamentos.store'), $lancamentoPayload())
            ->assertForbidden();
    });

    it('admin pode atualizar lançamento', function () use (&$lancamentoPayload) {
        $lancamento = Lancamento::create([...$lancamentoPayload(), 'company_id' => $this->company->id]);

        $this->actingAs($this->admin)
            ->putJson(route('financeiro.lancamentos.update', $lancamento), $lancamentoPayload(['valor' => 120.00]))
            ->assertOk();

        expect((float) $lancamento->fresh()->valor)->toBe(120.0);
    });

    it('admin pode excluir lançamento', function () use (&$lancamentoPayload) {
        $lancamento = Lancamento::create([...$lancamentoPayload(), 'company_id' => $this->company->id]);

        $this->actingAs($this->admin)
            ->deleteJson(route('financeiro.lancamentos.destroy', $lancamento))
            ->assertNoContent();

        expect(Lancamento::find($lancamento->id))->toBeNull();
    });

    it('isolamento: não pode editar lançamento de outra empresa', function () use (&$lancamentoPayload) {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-fin', 'plano' => 'trial', 'ativo' => true]);
        $lancamento = Lancamento::create([...$lancamentoPayload(), 'company_id' => $outra->id]);

        $this->actingAs($this->admin)
            ->putJson(route('financeiro.lancamentos.update', $lancamento), $lancamentoPayload())
            ->assertNotFound();
    });

    it('isolamento: não pode excluir lançamento de outra empresa', function () use (&$lancamentoPayload) {
        $outra = Company::create(['name' => 'Outra2', 'slug' => 'outra-fin2', 'plano' => 'trial', 'ativo' => true]);
        $lancamento = Lancamento::create([...$lancamentoPayload(), 'company_id' => $outra->id]);

        $this->actingAs($this->admin)
            ->deleteJson(route('financeiro.lancamentos.destroy', $lancamento))
            ->assertNotFound();
    });

    it('valor é obrigatório e positivo', function () use (&$lancamentoPayload) {
        $this->actingAs($this->admin)
            ->postJson(route('financeiro.lancamentos.store'), $lancamentoPayload(['valor' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['valor']);
    });
});

describe('financeiro_export_csv', function () {
    it('admin pode exportar CSV financeiro', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('financeiro.exportar'));

        $response->assertOk();
        expect($response->headers->get('content-type'))->toContain('text/csv');
    });

    it('unauthenticated não acessa exportação', function () {
        $this->get(route('financeiro.exportar'))->assertRedirect();
    });
});
