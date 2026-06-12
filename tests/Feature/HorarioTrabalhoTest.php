<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\HorarioTrabalho;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'gestor',        'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista',      'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Teste', 'slug' => 'empresa-teste',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->gestor = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->gestor->assignRole('gestor');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Carlos Silva',
        'especialidade' => 'Barbeiro',
        'ativo' => true,
    ]);
});

describe('horarios_trabalho', function () {
    it('admin pode ver página de horários do profissional', function () {
        $this->actingAs($this->admin)
            ->get(route('profissionais.horarios', $this->profissional))
            ->assertOk()
            ->assertViewIs('profissionais.horarios');
    });

    it('gestor pode ver página de horários', function () {
        $this->actingAs($this->gestor)
            ->get(route('profissionais.horarios', $this->profissional))
            ->assertOk();
    });

    it('analista não pode ver página de horários', function () {
        $this->actingAs($this->analista)
            ->get(route('profissionais.horarios', $this->profissional))
            ->assertForbidden();
    });

    it('admin pode salvar horários', function () {
        $this->actingAs($this->admin)
            ->put(route('profissionais.horarios.update', $this->profissional), [
                'dias' => [
                    1 => ['ativo' => '1', 'hora_inicio' => '08:00', 'hora_fim' => '18:00'],
                    2 => ['ativo' => '1', 'hora_inicio' => '08:00', 'hora_fim' => '17:00'],
                ],
            ])
            ->assertRedirect(route('profissionais.horarios', $this->profissional));

        expect(HorarioTrabalho::where('profissional_id', $this->profissional->id)->where('ativo', true)->count())->toBe(2);
    });

    it('desativar dia remove horário', function () {
        HorarioTrabalho::create([
            'empresa_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00',
            'hora_fim' => '18:00',
            'ativo' => true,
        ]);

        $this->actingAs($this->admin)
            ->put(route('profissionais.horarios.update', $this->profissional), [
                'dias' => [], // nenhum dia marcado
            ]);

        expect(HorarioTrabalho::where('profissional_id', $this->profissional->id)->where('ativo', true)->count())->toBe(0);
    });

    it('upsert não duplica horário ao salvar novamente', function () {
        $this->actingAs($this->admin)
            ->put(route('profissionais.horarios.update', $this->profissional), [
                'dias' => [1 => ['ativo' => '1', 'hora_inicio' => '08:00', 'hora_fim' => '18:00']],
            ]);

        $this->actingAs($this->admin)
            ->put(route('profissionais.horarios.update', $this->profissional), [
                'dias' => [1 => ['ativo' => '1', 'hora_inicio' => '09:00', 'hora_fim' => '17:00']],
            ]);

        $horarios = HorarioTrabalho::where('profissional_id', $this->profissional->id)
            ->where('dia_semana', 1)
            ->get();

        expect($horarios)->toHaveCount(1);
        expect(str_starts_with($horarios->first()->hora_inicio, '09:00'))->toBeTrue();
    });
});
