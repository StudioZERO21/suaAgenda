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

    $this->company = Company::create([
        'name' => 'Barbearia CliStats', 'slug' => 'barbearia-clistats',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->user = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->user->assignRole('admin_empresa');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50.00, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'phone' => '11999990001']);
});

function makeCliStats($self, string $status = 'finalizado', int $diasAtras = 5, float $valor = 50.0): Agendamento
{
    return Agendamento::create([
        'company_id' => $self->company->id,
        'cliente_id' => $self->cliente->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $self->servico->id,
        'data_hora' => now()->subDays($diasAtras),
        'duracao' => 30,
        'status' => $status,
        'valor' => $valor,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('cliente_stats', function () {
    it('retorna estrutura correta', function () {
        $this->actingAs($this->user)
            ->getJson(route('clientes.stats', $this->cliente))
            ->assertOk()
            ->assertJsonStructure([
                'total', 'finalizados', 'receita_total',
                'nota_media', 'ultimos_90_dias', 'primeiro_agendamento', 'ultimo_agendamento',
            ]);
    });

    it('conta total e finalizados corretamente', function () {
        makeCliStats($this, 'finalizado', 5, 100.0);
        makeCliStats($this, 'finalizado', 10, 60.0);
        makeCliStats($this, 'cancelado', 3);

        $data = $this->actingAs($this->user)
            ->getJson(route('clientes.stats', $this->cliente))
            ->json();

        expect($data['total'])->toBe(3);
        expect($data['finalizados'])->toBe(2);
        expect((float) $data['receita_total'])->toBe(160.0);
    });

    it('retorna zeros quando não há agendamentos', function () {
        $data = $this->actingAs($this->user)
            ->getJson(route('clientes.stats', $this->cliente))
            ->json();

        expect($data['total'])->toBe(0);
        expect((float) $data['receita_total'])->toBe(0.0);
        expect((float) $data['nota_media'])->toBe(0.0);
        expect($data['primeiro_agendamento'])->toBeNull();
        expect($data['ultimo_agendamento'])->toBeNull();
    });

    it('conta ultimos_90_dias corretamente', function () {
        makeCliStats($this, 'finalizado', 30);
        makeCliStats($this, 'finalizado', 60);
        makeCliStats($this, 'finalizado', 120);

        $data = $this->actingAs($this->user)
            ->getJson(route('clientes.stats', $this->cliente))
            ->json();

        expect($data['ultimos_90_dias'])->toBe(2);
    });

    it('não acessa stats de cliente de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-clistats', 'plano' => 'trial', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'X', 'phone' => '99999999999']);

        $this->actingAs($this->user)
            ->getJson(route('clientes.stats', $cliOutra))
            ->assertForbidden();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('clientes.stats', $this->cliente))
            ->assertUnauthorized();
    });
});
