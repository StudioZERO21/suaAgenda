<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
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
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Carlos Barbeiro',
        'especialidade' => 'Barbeiro',
        'ativo' => true,
    ]);
});

describe('profissionais', function () {
    it('admin pode listar profissionais', function () {
        $this->actingAs($this->admin)
            ->get(route('profissionais.index'))
            ->assertOk();
    });

    it('admin pode ver detalhes do profissional', function () {
        $this->actingAs($this->admin)
            ->get(route('profissionais.show', $this->profissional))
            ->assertOk()
            ->assertSee('Carlos Barbeiro');
    });

    it('admin pode criar profissional', function () {
        $this->actingAs($this->admin)
            ->post(route('profissionais.store'), [
                'name' => 'João Novo',
                'ativo' => '1',
            ])
            ->assertRedirect();

        expect(Profissional::where('name', 'João Novo')->where('company_id', $this->company->id)->exists())->toBeTrue();
    });

    it('gestor pode criar profissional', function () {
        $this->actingAs($this->gestor)
            ->post(route('profissionais.store'), [
                'name' => 'Ana Gestora',
                'ativo' => '1',
            ])
            ->assertRedirect();
    });

    it('analista não pode criar profissional', function () {
        $this->actingAs($this->analista)
            ->post(route('profissionais.store'), ['name' => 'Hack'])
            ->assertForbidden();
    });

    it('admin pode atualizar profissional', function () {
        $this->actingAs($this->admin)
            ->put(route('profissionais.update', $this->profissional), [
                'name' => 'Carlos Atualizado',
                'ativo' => '1',
            ])
            ->assertRedirect(route('profissionais.show', $this->profissional));

        expect($this->profissional->fresh()->name)->toBe('Carlos Atualizado');
    });

    it('apenas admin pode excluir profissional', function () {
        $this->actingAs($this->admin)
            ->delete(route('profissionais.destroy', $this->profissional))
            ->assertRedirect(route('profissionais.index'));

        expect(Profissional::find($this->profissional->id))->toBeNull();
        expect(Profissional::withTrashed()->find($this->profissional->id))->not->toBeNull();
    });

    it('gestor não pode excluir profissional', function () {
        $this->actingAs($this->gestor)
            ->delete(route('profissionais.destroy', $this->profissional))
            ->assertForbidden();
    });

    it('isolamento: não acessa profissional de outra empresa', function () {
        $outraCompany = Company::create([
            'name' => 'Outra',
            'slug' => 'outra',
            'plano' => 'trial',
            'ativo' => true,
        ]);
        $profOutro = Profissional::create([
            'company_id' => $outraCompany->id,
            'name' => 'Alheio',
            'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('profissionais.show', $profOutro))
            ->assertForbidden();
    });

    it('vincula serviços ao criar profissional', function () {
        $servico = Servico::create([
            'company_id' => $this->company->id,
            'nome' => 'Corte',
            'duracao_minutos' => 30,
            'preco' => 45,
            'cor' => '#1a1a1a',
            'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('profissionais.store'), [
                'name' => 'Novo Prof',
                'ativo' => '1',
                'servicos' => [$servico->id],
            ]);

        $novo = Profissional::where('name', 'Novo Prof')->firstOrFail();
        expect($novo->servicos->pluck('id')->contains($servico->id))->toBeTrue();
    });

    it('scopeAtivo exclui profissionais inativos', function () {
        Profissional::create([
            'company_id' => $this->company->id,
            'name' => 'Inativo',
            'ativo' => false,
        ]);

        expect(Profissional::ativo()->where('company_id', $this->company->id)->count())->toBe(1);
    });
});
