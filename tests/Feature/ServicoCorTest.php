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
        'name' => 'Barbearia Cor Serv', 'slug' => 'barbearia-cor-serv',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte', 'preco' => 50.0,
        'duracao_minutos' => 30, 'ativo' => true, 'cor' => '#999999',
    ]);
});

describe('servico_cor', function () {
    it('admin pode atualizar cor do serviço', function () {
        $data = $this->actingAs($this->admin)
            ->patchJson(route('servicos.cor', $this->servico), ['cor' => '#d4a574'])
            ->assertOk()
            ->assertJsonStructure(['cor', 'updated_at'])
            ->json();

        expect($data['cor'])->toBe('#d4a574');
        expect($this->servico->fresh()->cor)->toBe('#d4a574');
    });

    it('gestor pode atualizar cor', function () {
        $this->actingAs($this->gestor)
            ->patchJson(route('servicos.cor', $this->servico), ['cor' => '#aabbcc'])
            ->assertOk();
    });

    it('analista não pode atualizar cor', function () {
        $this->actingAs($this->analista)
            ->patchJson(route('servicos.cor', $this->servico), ['cor' => '#aabbcc'])
            ->assertForbidden();
    });

    it('rejeita cor sem hash', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('servicos.cor', $this->servico), ['cor' => 'd4a574'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cor']);
    });

    it('rejeita cor com 3 dígitos', function () {
        $this->actingAs($this->admin)
            ->patchJson(route('servicos.cor', $this->servico), ['cor' => '#fff'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cor']);
    });

    it('não pode atualizar serviço de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-cor-sv', 'plano' => 'trial', 'ativo' => true]);
        $servOutra = Servico::create([
            'company_id' => $outra->id, 'nome' => 'X', 'preco' => 10.0, 'duracao_minutos' => 30, 'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('servicos.cor', $servOutra), ['cor' => '#ffffff'])
            ->assertForbidden();
    });

    it('unauthenticated é rejeitado', function () {
        $this->patchJson(route('servicos.cor', $this->servico), ['cor' => '#ffffff'])
            ->assertUnauthorized();
    });
});
