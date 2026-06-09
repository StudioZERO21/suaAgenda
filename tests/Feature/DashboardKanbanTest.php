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

    $this->company = Company::create([
        'name' => 'Dashboard Kanban Test',
        'slug' => 'dashboard-kanban-test',
        'plano' => 'trial',
        'whatsapp' => '(11) 99999-0055',
        'trial_ends_at' => now()->addDays(7),
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->prof1 = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Prof Um',
        'ativo' => true,
    ]);

    $this->prof2 = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Prof Dois',
        'ativo' => true,
    ]);

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id,
        'name' => 'Cliente Kanban',
    ]);
});

function criarAgendamentoDash(array $extra = []): Agendamento
{
    return Agendamento::create(array_merge([
        'company_id' => test()->company->id,
        'profissional_id' => test()->prof1->id,
        'cliente_id' => test()->cliente->id,
        'data_hora' => now()->setTime(10, 0),
        'status' => 'pendente',
        'valor' => 50.00,
        'duracao' => 30,
    ], $extra));
}

describe('dashboard kanban cards', function () {
    it('mostra apenas agendamentos de hoje', function () {
        criarAgendamentoDash();
        criarAgendamentoDash(['data_hora' => now()->subDay()->setTime(10, 0)]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();

        $cards = $response->viewData('stats')['kanbanCards'];
        expect($cards)->toHaveCount(1);
    });

    it('admin vê agendamentos de todos os profissionais', function () {
        criarAgendamentoDash(['profissional_id' => test()->prof1->id]);
        criarAgendamentoDash(['profissional_id' => test()->prof2->id]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $cards = $response->viewData('stats')['kanbanCards'];

        expect($cards)->toHaveCount(2);
    });

    it('profissional vinculado vê apenas seus agendamentos', function () {
        $userProf = User::factory()->create([
            'empresa_id' => $this->company->id,
            'profissional_id' => $this->prof1->id,
        ]);
        $userProf->assignRole('gestor');

        criarAgendamentoDash(['profissional_id' => $this->prof1->id]);
        criarAgendamentoDash(['profissional_id' => $this->prof2->id]);

        $response = $this->actingAs($userProf)->get(route('dashboard'));
        $cards = $response->viewData('stats')['kanbanCards'];

        expect($cards)->toHaveCount(1)
            ->and($cards->first()['profissional_id'])->toBe($this->prof1->id);
    });

    it('canEdit é true para admin', function () {
        criarAgendamentoDash();

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $cards = $response->viewData('stats')['kanbanCards'];

        expect($cards->first()['canEdit'])->toBeTrue();
    });

    it('canEdit é false para profissional sem vínculo com o agendamento', function () {
        $userProf = User::factory()->create([
            'empresa_id' => $this->company->id,
            'profissional_id' => $this->prof2->id,
        ]);
        $userProf->assignRole('gestor');

        criarAgendamentoDash(['profissional_id' => $this->prof1->id]);

        $response = $this->actingAs($userProf)->get(route('dashboard'));
        $cards = $response->viewData('stats')['kanbanCards'];

        expect($cards)->toHaveCount(0);
    });

    it('card contém statusUrl para PATCH', function () {
        criarAgendamentoDash();

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $cards = $response->viewData('stats')['kanbanCards'];

        expect($cards->first())->toHaveKey('statusUrl')
            ->and($cards->first()['statusUrl'])->toContain('agendamentos');
    });
});

describe('dashboard timeline (próximos agendamentos)', function () {
    it('admin vê próximos de todos os profissionais', function () {
        criarAgendamentoDash(['profissional_id' => test()->prof1->id, 'data_hora' => now()->addHour()]);
        criarAgendamentoDash(['profissional_id' => test()->prof2->id, 'data_hora' => now()->addHour()]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $proximos = $response->viewData('stats')['proximosAgendamentos'];

        expect($proximos)->toHaveCount(2);
    });

    it('profissional vinculado vê apenas seus próximos', function () {
        $userProf = User::factory()->create([
            'empresa_id' => $this->company->id,
            'profissional_id' => $this->prof1->id,
        ]);
        $userProf->assignRole('gestor');

        criarAgendamentoDash(['profissional_id' => $this->prof1->id, 'data_hora' => now()->addHour()]);
        criarAgendamentoDash(['profissional_id' => $this->prof2->id, 'data_hora' => now()->addHour()]);

        $response = $this->actingAs($userProf)->get(route('dashboard'));
        $proximos = $response->viewData('stats')['proximosAgendamentos'];

        expect($proximos)->toHaveCount(1)
            ->and($proximos->first()->profissional_id)->toBe($this->prof1->id);
    });

    it('profissional sem vínculo não vê próximos agendamentos', function () {
        $userSemVinculo = User::factory()->create(['empresa_id' => $this->company->id]);
        $userSemVinculo->assignRole('gestor');

        criarAgendamentoDash(['data_hora' => now()->addHour()]);

        $response = $this->actingAs($userSemVinculo)->get(route('dashboard'));
        $proximos = $response->viewData('stats')['proximosAgendamentos'];

        expect($proximos)->toHaveCount(0);
    });
});

describe('status em_atendimento', function () {
    it('aceita em_atendimento no updateStatus', function () {
        $ag = criarAgendamentoDash();

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.updateStatus', $ag), ['status' => 'em_atendimento'])
            ->assertOk()
            ->assertJson(['success' => true, 'status' => 'em_atendimento']);

        expect($ag->fresh()->status)->toBe('em_atendimento');
    });

    it('rejeita status inválido', function () {
        $ag = criarAgendamentoDash();

        $this->actingAs($this->admin)
            ->patchJson(route('agendamentos.updateStatus', $ag), ['status' => 'invalido'])
            ->assertUnprocessable();
    });
});
