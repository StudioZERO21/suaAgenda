<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Kanban',
        'slug' => 'empresa-kanban',
        'plano' => 'trial',
        'whatsapp' => '(11) 99999-0040',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Carlos Barbeiro',
        'ativo' => true,
    ]);

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id,
        'name' => 'João Cliente',
    ]);
});

function criarAgendamento(array $extra = []): Agendamento
{
    return Agendamento::create(array_merge([
        'company_id' => test()->company->id,
        'profissional_id' => test()->profissional->id,
        'cliente_id' => test()->cliente->id,
        'data_hora' => now()->setTime(10, 0),
        'status' => 'pendente',
        'valor' => 50.00,
        'duracao' => 30,
    ], $extra));
}

describe('kanban view', function () {
    it('exibe view kanban', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('calendario', ['view' => 'kanban']));

        $response->assertOk();
        $response->assertSee('kanban');
    });

    it('kanban mostra agendamentos do dia', function () {
        criarAgendamento();

        $response = $this->actingAs($this->admin)
            ->get(route('calendario', ['view' => 'kanban', 'ref' => now()->format('Y-m-d')]));

        $response->assertOk();
        $response->assertSee('Carlos Barbeiro');
    });
});

describe('updateStatus JSON', function () {
    it('admin pode mudar status via JSON', function () {
        $ag = criarAgendamento();

        $response = $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.updateStatus', $ag), ['status' => 'confirmado']);

        $response->assertOk()
            ->assertJson(['success' => true, 'status' => 'confirmado']);

        expect($ag->fresh()->status)->toBe('confirmado');
    });

    it('retorna 422 para status inválido', function () {
        $ag = criarAgendamento();

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.updateStatus', $ag), ['status' => 'invalido'])
            ->assertUnprocessable();
    });
});

describe('ACL por profissional', function () {
    it('profissional vinculado pode mudar seu próprio agendamento', function () {
        $userProf = User::factory()->create([
            'empresa_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
        ]);
        $userProf->assignRole('gestor');

        $ag = criarAgendamento(['profissional_id' => $this->profissional->id]);

        $this->actingAs($userProf)
            ->patchJson(route('agendamentos.updateStatus', $ag), ['status' => 'confirmado'])
            ->assertOk();
    });

    it('profissional vinculado NÃO pode mudar agendamento de outro', function () {
        $outroProfissional = Profissional::create([
            'company_id' => $this->company->id,
            'name' => 'Ana Manicure',
            'ativo' => true,
        ]);

        $userProf = User::factory()->create([
            'empresa_id' => $this->company->id,
            'profissional_id' => $outroProfissional->id,
        ]);
        $userProf->assignRole('gestor');

        $ag = criarAgendamento(['profissional_id' => $this->profissional->id]);

        $this->actingAs($userProf)
            ->patchJson(route('agendamentos.updateStatus', $ag), ['status' => 'confirmado'])
            ->assertForbidden();
    });

    it('admin pode mudar agendamento de qualquer profissional', function () {
        $ag = criarAgendamento();

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.updateStatus', $ag), ['status' => 'finalizado'])
            ->assertOk();
    });
});
