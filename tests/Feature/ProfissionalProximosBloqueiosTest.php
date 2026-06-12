<?php

declare(strict_types=1);

use App\Models\BloqueioAgenda;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia PxBloq', 'slug' => 'barbearia-pxbloq',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create([
        'company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true,
    ]);
});

describe('profissional_proximos_bloqueios', function () {
    it('retorna lista vazia quando sem bloqueios', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.proximos-bloqueios', $this->prof))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
        expect($data['items'])->toHaveCount(0);
    });

    it('retorna apenas bloqueios futuros/atuais', function () {
        BloqueioAgenda::create([
            'company_id' => $this->company->id, 'profissional_id' => $this->prof->id,
            'data_inicio' => now()->addDays(2)->toDateString(),
            'data_fim' => now()->addDays(5)->toDateString(),
            'motivo' => 'Férias',
        ]);
        BloqueioAgenda::create([
            'company_id' => $this->company->id, 'profissional_id' => $this->prof->id,
            'data_inicio' => now()->subDays(10)->toDateString(),
            'data_fim' => now()->subDays(5)->toDateString(),
            'motivo' => 'Passado',
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.proximos-bloqueios', $this->prof))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['items'][0]['motivo'])->toBe('Férias');
    });

    it('retorna estrutura correta', function () {
        BloqueioAgenda::create([
            'company_id' => $this->company->id, 'profissional_id' => $this->prof->id,
            'data_inicio' => now()->addDay()->toDateString(),
            'data_fim' => now()->addDays(3)->toDateString(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.proximos-bloqueios', $this->prof))
            ->assertOk()
            ->json();

        expect($data['items'][0])->toHaveKeys(['id', 'data_inicio', 'data_fim', 'motivo']);
    });

    it('ordena por data_inicio crescente', function () {
        BloqueioAgenda::create(['company_id' => $this->company->id, 'profissional_id' => $this->prof->id, 'data_inicio' => now()->addDays(10)->toDateString(), 'data_fim' => now()->addDays(12)->toDateString()]);
        BloqueioAgenda::create(['company_id' => $this->company->id, 'profissional_id' => $this->prof->id, 'data_inicio' => now()->addDays(3)->toDateString(), 'data_fim' => now()->addDays(5)->toDateString()]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.proximos-bloqueios', $this->prof))
            ->assertOk()
            ->json();

        expect($data['items'][0]['data_inicio'])->toBe(now()->addDays(3)->toDateString());
        expect($data['items'][1]['data_inicio'])->toBe(now()->addDays(10)->toDateString());
    });

    it('analista pode listar bloqueios', function () {
        $this->actingAs($this->analista)
            ->getJson(route('profissionais.proximos-bloqueios', $this->prof))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('profissionais.proximos-bloqueios', $this->prof))
            ->assertUnauthorized();
    });
});
