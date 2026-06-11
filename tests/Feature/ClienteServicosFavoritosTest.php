<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia SvcFav', 'slug' => 'barbearia-svcfav',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'P', 'ativo' => true]);
    $this->corte = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30,
        'preco' => 50.0, 'cor' => '#111', 'ativo' => true,
    ]);
    $this->barba = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Barba', 'duracao_minutos' => 20,
        'preco' => 30.0, 'cor' => '#222', 'ativo' => true,
    ]);
    $this->cliente = Cliente::create([
        'company_id' => $this->company->id, 'name' => 'Diego', 'phone' => '11999990055',
    ]);
});

function makeSvcFavAg($self, Servico $servico, string $status = 'finalizado'): Agendamento
{
    return Agendamento::create([
        'company_id' => $self->company->id,
        'profissional_id' => $self->prof->id,
        'servico_id' => $servico->id,
        'cliente_id' => $self->cliente->id,
        'data_hora' => now()->subDays(rand(1, 30)),
        'duracao' => 30,
        'status' => $status,
        'valor' => $servico->preco,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('cliente_servicos_favoritos', function () {
    it('retorna lista vazia quando sem agendamentos finalizados', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.servicos-favoritos', $this->cliente))
            ->assertOk()
            ->json();

        expect($data)->toBeEmpty();
    });

    it('retorna serviços ordenados por total de agendamentos', function () {
        makeSvcFavAg($this, $this->corte);
        makeSvcFavAg($this, $this->corte);
        makeSvcFavAg($this, $this->corte);
        makeSvcFavAg($this, $this->barba);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.servicos-favoritos', $this->cliente))
            ->json();

        expect($data[0]['nome'])->toBe('Corte');
        expect($data[0]['total_agendamentos'])->toBe(3);
        expect($data[1]['nome'])->toBe('Barba');
    });

    it('ignora agendamentos não finalizados', function () {
        makeSvcFavAg($this, $this->corte, 'cancelado');
        makeSvcFavAg($this, $this->barba);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.servicos-favoritos', $this->cliente))
            ->json();

        expect(count($data))->toBe(1);
        expect($data[0]['nome'])->toBe('Barba');
    });

    it('retorna estrutura correta', function () {
        makeSvcFavAg($this, $this->corte);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.servicos-favoritos', $this->cliente))
            ->json();

        expect($data[0])->toHaveKeys(['servico_id', 'nome', 'cor', 'total_agendamentos', 'receita_total']);
    });

    it('não acessa cliente de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-svcfav', 'plano' => 'trial', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'X', 'phone' => '99999999999']);

        $this->actingAs($this->admin)
            ->getJson(route('clientes.servicos-favoritos', $cliOutra))
            ->assertForbidden();
    });

    it('analista pode ver favoritos', function () {
        $this->actingAs($this->analista)
            ->getJson(route('clientes.servicos-favoritos', $this->cliente))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('clientes.servicos-favoritos', $this->cliente))
            ->assertUnauthorized();
    });
});
