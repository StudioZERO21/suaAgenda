<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Role;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

    $this->servico = Servico::create([
        'company_id' => $this->company->id,
        'nome' => 'Corte de cabelo',
        'duracao_minutos' => 30,
        'preco' => 45.00,
        'cor' => '#1a1a1a',
        'ativo' => true,
    ]);
});

describe('servicos', function () {
    it('todos os roles podem listar serviços', function () {
        $this->actingAs($this->analista)
            ->get(route('servicos.index'))
            ->assertOk();
    });

    it('admin pode criar serviço', function () {
        $this->actingAs($this->admin)
            ->post(route('servicos.store'), [
                'nome' => 'Barba',
                'duracao_minutos' => 30,
                'preco' => 35.00,
                'cor' => '#d4a574',
                'ativo' => '1',
            ])
            ->assertRedirect(route('servicos.index'));

        expect(Servico::where('nome', 'Barba')->where('company_id', $this->company->id)->exists())->toBeTrue();
    });

    it('gestor pode criar serviço', function () {
        $this->actingAs($this->gestor)
            ->post(route('servicos.store'), [
                'nome' => 'Hidratação',
                'duracao_minutos' => 60,
                'preco' => 90.00,
                'cor' => '#10b981',
                'ativo' => '1',
            ])
            ->assertRedirect(route('servicos.index'));
    });

    it('analista não pode criar serviço', function () {
        $this->actingAs($this->analista)
            ->post(route('servicos.store'), [
                'nome' => 'Hack',
                'duracao_minutos' => 30,
                'preco' => 0,
                'cor' => '#000000',
            ])
            ->assertForbidden();
    });

    it('admin pode atualizar serviço', function () {
        $this->actingAs($this->admin)
            ->put(route('servicos.update', $this->servico), [
                'nome' => 'Corte Atualizado',
                'duracao_minutos' => 45,
                'preco' => 55.00,
                'cor' => '#1a1a1a',
                'ativo' => '1',
            ])
            ->assertRedirect(route('servicos.index'));

        expect($this->servico->fresh()->nome)->toBe('Corte Atualizado');
    });

    it('apenas admin pode excluir serviço', function () {
        $this->actingAs($this->admin)
            ->delete(route('servicos.destroy', $this->servico))
            ->assertRedirect(route('servicos.index'));

        expect(Servico::find($this->servico->id))->toBeNull();
        expect(Servico::withTrashed()->find($this->servico->id))->not->toBeNull();
    });

    it('gestor não pode excluir serviço', function () {
        $this->actingAs($this->gestor)
            ->delete(route('servicos.destroy', $this->servico))
            ->assertForbidden();
    });

    it('isolamento: não acessa serviço de outra empresa', function () {
        $outraCompany = Company::create([
            'name' => 'Outra',
            'slug' => 'outra',
            'plano' => 'trial',
            'ativo' => true,
        ]);
        $servicoOutro = Servico::create([
            'company_id' => $outraCompany->id,
            'nome' => 'Serviço Alheio',
            'duracao_minutos' => 30,
            'preco' => 10,
            'cor' => '#000',
            'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->put(route('servicos.update', $servicoOutro), [
                'nome' => 'Hack',
                'duracao_minutos' => 30,
                'preco' => 0,
                'cor' => '#000000',
            ])
            ->assertForbidden();
    });

    it('scopeAtivo exclui serviços inativos', function () {
        Servico::create([
            'company_id' => $this->company->id,
            'nome' => 'Inativo',
            'duracao_minutos' => 30,
            'preco' => 0,
            'cor' => '#000',
            'ativo' => false,
        ]);

        expect(Servico::ativo()->where('company_id', $this->company->id)->count())->toBe(1);
    });

    it('duracaoFormatada retorna texto correto', function () {
        expect($this->servico->duracaoFormatada())->toBe('30min');

        $servico90 = new Servico(['duracao_minutos' => 90]);
        expect($servico90->duracaoFormatada())->toBe('1h30min');

        $servico60 = new Servico(['duracao_minutos' => 60]);
        expect($servico60->duracaoFormatada())->toBe('1h');
    });
});
