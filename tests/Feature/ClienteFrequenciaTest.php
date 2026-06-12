<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Role;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia Frequencia', 'slug' => 'barbearia-frequencia',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'P', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true,
    ]);
    $this->cliente = Cliente::create([
        'company_id' => $this->company->id, 'name' => 'Frequente', 'phone' => '11999990099',
    ]);
});

function makeFreqAg($self, string $status, int $diasAtras, float $valor = 50.0): Agendamento
{
    return Agendamento::create([
        'company_id' => $self->company->id,
        'cliente_id' => $self->cliente->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $self->servico->id,
        'data_hora' => now()->subDays($diasAtras)->setTime(10, 0),
        'duracao' => 30,
        'status' => $status,
        'valor' => $valor,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('cliente_frequencia', function () {
    it('retorna zeros para cliente sem agendamentos finalizados', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.frequencia', $this->cliente))
            ->assertOk()
            ->json();

        expect($data['total_visitas'])->toBe(0);
        expect((float) $data['ltv'])->toBe(0.0);
        expect($data['media_dias_entre_visitas'])->toBeNull();
        expect($data['proxima_visita_prevista'])->toBeNull();
    });

    it('retorna estrutura correta', function () {
        makeFreqAg($this, 'finalizado', 30);

        $this->actingAs($this->admin)
            ->getJson(route('clientes.frequencia', $this->cliente))
            ->assertOk()
            ->assertJsonStructure([
                'total_visitas', 'ltv', 'media_dias_entre_visitas',
                'proxima_visita_prevista', 'frequencia_mensal',
            ]);
    });

    it('calcula LTV corretamente', function () {
        makeFreqAg($this, 'finalizado', 60, 80.0);
        makeFreqAg($this, 'finalizado', 30, 120.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.frequencia', $this->cliente))
            ->json();

        expect((float) $data['ltv'])->toBe(200.0);
        expect($data['total_visitas'])->toBe(2);
    });

    it('ignora agendamentos não finalizados no cálculo', function () {
        makeFreqAg($this, 'finalizado', 60, 50.0);
        makeFreqAg($this, 'cancelado', 30, 50.0);
        makeFreqAg($this, 'confirmado', 10, 50.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.frequencia', $this->cliente))
            ->json();

        expect($data['total_visitas'])->toBe(1);
        expect((float) $data['ltv'])->toBe(50.0);
    });

    it('calcula media_dias_entre_visitas com 2+ visitas', function () {
        makeFreqAg($this, 'finalizado', 60);
        makeFreqAg($this, 'finalizado', 30);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.frequencia', $this->cliente))
            ->json();

        expect((float) $data['media_dias_entre_visitas'])->toBe(30.0);
    });

    it('não acessa cliente de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-freq', 'plano' => 'trial', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'X', 'phone' => '99999999999']);

        $this->actingAs($this->admin)
            ->getJson(route('clientes.frequencia', $cliOutra))
            ->assertForbidden();
    });

    it('analista pode ver frequência', function () {
        $this->actingAs($this->analista)
            ->getJson(route('clientes.frequencia', $this->cliente))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('clientes.frequencia', $this->cliente))
            ->assertUnauthorized();
    });
});
