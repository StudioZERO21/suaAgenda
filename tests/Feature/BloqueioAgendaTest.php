<?php

declare(strict_types=1);

use App\Models\BloqueioAgenda;
use App\Models\Company;
use App\Models\HorarioTrabalho;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::create([
        'name' => 'Barbearia Bloqueio', 'slug' => 'barbearia-bloqueio',
        'plano' => 'trial', 'ativo' => true,
    ]);

    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Carlos',
        'ativo' => true,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id,
        'nome' => 'Corte',
        'duracao_minutos' => 30,
        'preco' => 45.00,
        'cor' => '#1a1a1a',
        'ativo' => true,
    ]);

    $this->profissional->servicos()->attach($this->servico->id);
});

describe('bloqueio_agenda', function () {
    it('cria bloqueio de agenda', function () {
        $this->actingAs($this->user)
            ->postJson(route('profissionais.bloqueios.store', $this->profissional), [
                'data_inicio' => now()->addDays(5)->format('Y-m-d'),
                'data_fim' => now()->addDays(7)->format('Y-m-d'),
                'motivo' => 'Férias',
            ])
            ->assertStatus(201)
            ->assertJsonPath('motivo', 'Férias');

        expect(BloqueioAgenda::where('profissional_id', $this->profissional->id)->count())->toBe(1);
    });

    it('valida que data_fim >= data_inicio', function () {
        $this->actingAs($this->user)
            ->postJson(route('profissionais.bloqueios.store', $this->profissional), [
                'data_inicio' => now()->addDays(7)->format('Y-m-d'),
                'data_fim' => now()->addDays(5)->format('Y-m-d'),
            ])
            ->assertUnprocessable();
    });

    it('remove bloqueio', function () {
        $b = BloqueioAgenda::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'data_inicio' => now()->addDays(3)->format('Y-m-d'),
            'data_fim' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $this->actingAs($this->user)
            ->delete(route('bloqueios.destroy', $b->id))
            ->assertNoContent();

        expect(BloqueioAgenda::find($b->id))->toBeNull();
    });

    it('slots retornam vazio quando dia está bloqueado', function () {
        $data = now()->addDay()->format('Y-m-d');
        $diaSemana = (int) now()->addDay()->format('w');

        HorarioTrabalho::create([
            'empresa_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'dia_semana' => $diaSemana,
            'hora_inicio' => '08:00',
            'hora_fim' => '12:00',
            'ativo' => true,
        ]);

        BloqueioAgenda::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'data_inicio' => $data,
            'data_fim' => $data,
        ]);

        $response = $this->getJson(route('agendar.slots', $this->company->slug).'?'.http_build_query([
            'profissional_id' => $this->profissional->id,
            'servico_id' => $this->servico->id,
            'data' => $data,
        ]));

        $response->assertOk();
        expect($response->json())->toBeEmpty();
    });

    it('disponibilidade retorna slots vazios para profissional bloqueado', function () {
        $data = now()->addDay()->format('Y-m-d');
        $diaSemana = (int) now()->addDay()->format('w');

        HorarioTrabalho::create([
            'empresa_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'dia_semana' => $diaSemana,
            'hora_inicio' => '08:00',
            'hora_fim' => '12:00',
            'ativo' => true,
        ]);

        BloqueioAgenda::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'data_inicio' => $data,
            'data_fim' => $data,
        ]);

        $response = $this->getJson(route('vitrine.disponibilidade', $this->company->slug).'?'.http_build_query([
            'servico_id' => $this->servico->id,
            'data' => $data,
        ]));

        $response->assertOk();
        $row = collect($response->json())->firstWhere('profissional.id', $this->profissional->id);
        expect($row['slots'])->toBeEmpty();
    });

    it('BloqueioAgenda::blockedOn retorna true quando data está bloqueada', function () {
        $data = now()->addDays(3)->format('Y-m-d');
        BloqueioAgenda::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->profissional->id,
            'data_inicio' => now()->addDays(2)->format('Y-m-d'),
            'data_fim' => now()->addDays(5)->format('Y-m-d'),
        ]);

        expect(BloqueioAgenda::blockedOn($this->profissional->id, $data))->toBeTrue();
        expect(BloqueioAgenda::blockedOn($this->profissional->id, now()->addDays(10)->format('Y-m-d')))->toBeFalse();
    });
});
