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
        'name' => 'Barbearia CliAg', 'slug' => 'barbearia-cliag',
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

function makeCliAg($self, string $status = 'finalizado', int $diasAtras = 5): Agendamento
{
    return Agendamento::create([
        'company_id' => $self->company->id,
        'cliente_id' => $self->cliente->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $self->servico->id,
        'data_hora' => now()->subDays($diasAtras),
        'duracao' => 30,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('cliente_agendamentos', function () {
    it('retorna estrutura correta', function () {
        makeCliAg($this);

        $this->actingAs($this->user)
            ->getJson(route('clientes.agendamentos', $this->cliente))
            ->assertOk()
            ->assertJsonStructure(['total', 'per_page', 'page', 'data' => [['id', 'data_hora', 'servico_nome', 'status', 'valor']]]);
    });

    it('retorna todos os agendamentos do cliente', function () {
        makeCliAg($this, 'finalizado', 10);
        makeCliAg($this, 'cancelado', 5);
        makeCliAg($this, 'confirmado', 2);

        $data = $this->actingAs($this->user)
            ->getJson(route('clientes.agendamentos', $this->cliente))
            ->json();

        expect($data['total'])->toBe(3);
    });

    it('ordena por data_hora decrescente', function () {
        makeCliAg($this, 'finalizado', 10);
        makeCliAg($this, 'finalizado', 3);
        makeCliAg($this, 'finalizado', 7);

        $data = $this->actingAs($this->user)
            ->getJson(route('clientes.agendamentos', $this->cliente))
            ->json();

        $horarios = collect($data['data'])->pluck('data_hora')->all();
        $sorted = $horarios;
        rsort($sorted);
        expect($horarios)->toBe($sorted);
    });

    it('não acessa agendamentos de cliente de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-cliag', 'plano' => 'trial', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'X', 'phone' => '99999999999']);

        $this->actingAs($this->user)
            ->getJson(route('clientes.agendamentos', $cliOutra))
            ->assertForbidden();
    });

    it('pagina com per_page customizado', function () {
        for ($i = 0; $i < 5; $i++) {
            makeCliAg($this, 'finalizado', $i + 1);
        }

        $data = $this->actingAs($this->user)
            ->getJson(route('clientes.agendamentos', [$this->cliente, 'per_page' => 2]))
            ->json();

        expect($data['total'])->toBe(5);
        expect($data['per_page'])->toBe(2);
        expect(count($data['data']))->toBe(2);
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('clientes.agendamentos', $this->cliente))
            ->assertUnauthorized();
    });
});
