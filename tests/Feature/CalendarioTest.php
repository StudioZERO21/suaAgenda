<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista',      'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Empresa Teste',
        'slug' => 'empresa-teste',
        'plano' => 'trial',
        'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');
});

describe('calendario', function () {
    it('qualquer role pode acessar o calendário', function () {
        $this->actingAs($this->analista)
            ->get(route('calendario'))
            ->assertOk();
    });

    it('exibe agendamentos da semana atual', function () {
        $profissional = Profissional::create([
            'company_id' => $this->company->id,
            'name' => 'Carlos',
            'ativo' => true,
        ]);
        $servico = Servico::create([
            'company_id' => $this->company->id,
            'nome' => 'Corte',
            'duracao_minutos' => 30,
            'preco' => 40,
            'cor' => '#1a1a1a',
            'ativo' => true,
        ]);

        $cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João']);

        $hoje = now()->startOfWeek(Carbon::MONDAY)->addDays(1)->setTime(10, 0);
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $profissional->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'data_hora' => $hoje,
            'duracao' => 30,
            'valor' => 40,
            'status' => 'confirmado',
        ]);

        $this->actingAs($this->admin)
            ->get(route('calendario'))
            ->assertOk()
            ->assertSee('Corte');
    });

    it('filtra por profissional via query string', function () {
        $prof1 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Ana', 'ativo' => true]);
        $prof2 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Bruno', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->get(route('calendario', ['profissional_id' => $prof1->id]))
            ->assertOk();
    });

    it('navega para semana específica via parâmetro', function () {
        $this->actingAs($this->admin)
            ->get(route('calendario', ['semana' => '2026-06-02']))
            ->assertOk();
    });
});
