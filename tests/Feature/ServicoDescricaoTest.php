<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Descricao', 'slug' => 'barbearia-desc',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->servico = Servico::create([
        'company_id' => $this->company->id,
        'nome' => 'Corte Clássico', 'preco' => 45.0,
        'duracao_minutos' => 30, 'ativo' => true,
    ]);
});

describe('servico_descricao', function () {
    it('admin pode atualizar descrição do serviço', function () {
        $texto = 'Corte clássico masculino com navalha e acabamento perfeito.';

        $data = $this->actingAs($this->admin)
            ->patchJson(route('servicos.descricao', $this->servico), ['descricao' => $texto])
            ->assertOk()
            ->assertJsonStructure(['descricao', 'updated_at'])
            ->json();

        expect($data['descricao'])->toBe($texto);
        expect($this->servico->fresh()->descricao)->toBe($texto);
    });

    it('gestor pode atualizar descrição', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('servicos.descricao', $this->servico), ['descricao' => 'Descrição gestora.'])
            ->assertOk();
    });

    it('analista não pode atualizar descrição', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('servicos.descricao', $this->servico), ['descricao' => 'Tentativa.'])
            ->assertForbidden();
    });

    it('aceita descrição nula para limpar campo', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('servicos.descricao', $this->servico), ['descricao' => null])
            ->assertOk()
            ->json();

        expect($data['descricao'])->toBe('');
    });

    it('rejeita descrição maior que 500 caracteres', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('servicos.descricao', $this->servico), ['descricao' => str_repeat('x', 501)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['descricao']);
    });

    it('não pode atualizar serviço de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-desc', 'plano' => 'trial', 'ativo' => true]);
        $servOutra = Servico::create([
            'company_id' => $outra->id, 'nome' => 'X', 'preco' => 30.0, 'duracao_minutos' => 30, 'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('servicos.descricao', $servOutra), ['descricao' => 'Invasão.'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('servicos.descricao', $this->servico), ['descricao' => 'X'])
            ->assertUnauthorized();
    });
});
