<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\HorarioTrabalho;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia ProfDisp', 'slug' => 'barbearia-profdisp',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'phone' => '11999990001']);

    // Monday working hours: 09:00–11:00
    $proximaSegunda = now()->next('Monday');
    $this->data = $proximaSegunda->format('Y-m-d');
    $diaSemana = (int) $proximaSegunda->format('w'); // 1=Monday

    HorarioTrabalho::create([
        'empresa_id' => $this->company->id,
        'profissional_id' => $this->prof->id,
        'dia_semana' => $diaSemana,
        'hora_inicio' => '09:00',
        'hora_fim' => '11:00',
        'ativo' => true,
    ]);
});

describe('profissional_disponibilidade', function () {
    it('retorna estrutura correta', function () {
        $this->actingAs($this->user)
            ->getJson(route('profissionais.disponibilidade', [$this->prof, 'data' => $this->data, 'duracao' => 30]))
            ->assertOk()
            ->assertJsonStructure(['slots', 'bloqueado']);
    });

    it('retorna slots dentro do horário de trabalho', function () {
        $data = $this->actingAs($this->user)
            ->getJson(route('profissionais.disponibilidade', [$this->prof, 'data' => $this->data, 'duracao' => 30]))
            ->json();

        $horas = collect($data['slots'])->pluck('hora')->all();
        expect($horas)->toContain('09:00');
        expect($horas)->toContain('09:30');
        expect($horas)->toContain('10:00');
        expect($horas)->toContain('10:30');
        expect(count($horas))->toBe(4);
    });

    it('retorna vazio quando sem horário de trabalho no dia', function () {
        // Use Sunday (no working hours configured)
        $domingo = now()->next('Sunday')->format('Y-m-d');

        $data = $this->actingAs($this->user)
            ->getJson(route('profissionais.disponibilidade', [$this->prof, 'data' => $domingo, 'duracao' => 30]))
            ->json();

        expect($data['slots'])->toBeEmpty();
        expect($data['bloqueado'])->toBeFalse();
    });

    it('marca slot como indisponível quando ocupado', function () {
        Agendamento::create([
            'company_id' => $this->company->id,
            'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->prof->id,
            'servico_id' => $this->servico->id,
            'data_hora' => Carbon::parse($this->data.' 09:00'),
            'duracao' => 30,
            'status' => 'confirmado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->user)
            ->getJson(route('profissionais.disponibilidade', [$this->prof, 'data' => $this->data, 'duracao' => 30]))
            ->json();

        $slot09 = collect($data['slots'])->firstWhere('hora', '09:00');
        $slot930 = collect($data['slots'])->firstWhere('hora', '09:30');
        expect($slot09['disponivel'])->toBeFalse();
        expect($slot930['disponivel'])->toBeTrue();
    });

    it('não acessa disponibilidade de profissional de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-profdisp', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);

        $this->actingAs($this->user)
            ->getJson(route('profissionais.disponibilidade', [$profOutra, 'data' => $this->data, 'duracao' => 30]))
            ->assertForbidden();
    });

    it('data é obrigatória', function () {
        $this->actingAs($this->user)
            ->getJson(route('profissionais.disponibilidade', $this->prof))
            ->assertUnprocessable();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('profissionais.disponibilidade', [$this->prof, 'data' => $this->data]))
            ->assertUnauthorized();
    });
});
