<?php

declare(strict_types=1);

use App\Models\Cargo;
use App\Models\Company;
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
        'whatsapp' => '(11) 99999-0000',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cargo = Cargo::create([
        'company_id' => $this->company->id,
        'nome' => 'Barbeiro',
        'nivel' => 'professional',
        'cor' => '#1a1a1a',
        'descricao' => 'Realiza cortes.',
        'comissao_pct' => 35.00,
    ]);
});

describe('cargos index', function () {
    it('admin pode ver a página de cargos', function () {
        $this->actingAs($this->admin)
            ->get(route('cargos.index'))
            ->assertOk()
            ->assertSee('Cargos');
    });

    it('gestor pode ver a página de cargos', function () {
        $this->actingAs($this->gestor)
            ->get(route('cargos.index'))
            ->assertOk();
    });
});

describe('cargos CRUD', function () {
    it('admin pode criar cargo', function () {
        $this->actingAs($this->admin)
            ->postJson(route('cargos.store'), [
                'nome' => 'Recepcionista',
                'nivel' => 'receptionist',
                'cor' => '#10b981',
                'descricao' => 'Gerencia agendamentos.',
                'comissao' => null,
            ])
            ->assertStatus(201)
            ->assertJsonFragment(['nome' => 'Recepcionista']);

        expect(Cargo::where('nome', 'Recepcionista')->where('company_id', $this->company->id)->exists())->toBeTrue();
    });

    it('gestor não pode criar cargo', function () {
        $this->actingAs($this->gestor)
            ->postJson(route('cargos.store'), ['nome' => 'Hack', 'nivel' => 'admin'])
            ->assertForbidden();
    });

    it('admin pode atualizar cargo', function () {
        $this->actingAs($this->admin)
            ->putJson(route('cargos.update', $this->cargo), [
                'nome' => 'Barbeiro Sênior',
                'nivel' => 'professional',
                'cor' => '#1a1a1a',
                'comissao' => 40.00,
            ])
            ->assertOk()
            ->assertJsonFragment(['nome' => 'Barbeiro Sênior', 'comissao' => 40.0]);

        expect($this->cargo->fresh()->nome)->toBe('Barbeiro Sênior');
        expect((float) $this->cargo->fresh()->comissao_pct)->toBe(40.0);
    });

    it('admin pode deletar cargo', function () {
        $this->actingAs($this->admin)
            ->deleteJson(route('cargos.destroy', $this->cargo))
            ->assertNoContent();

        expect(Cargo::find($this->cargo->id))->toBeNull();
    });

    it('nome é obrigatório', function () {
        $this->actingAs($this->admin)
            ->postJson(route('cargos.store'), ['nivel' => 'admin'])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['nome']]);
    });

    it('comissao deve estar entre 0 e 100', function () {
        $this->actingAs($this->admin)
            ->postJson(route('cargos.store'), ['nome' => 'Cargo', 'nivel' => 'professional', 'comissao' => 150])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['comissao']]);
    });

    it('isolamento: cargo de outra empresa retorna 404', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra', 'plano' => 'trial', 'ativo' => true]);
        $cargoAlheio = Cargo::create([
            'company_id' => $outra->id,
            'nome' => 'Cargo Alheio',
            'nivel' => 'professional',
            'cor' => '#000',
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('cargos.destroy', $cargoAlheio))
            ->assertNotFound();
    });
});
