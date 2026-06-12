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
        'name' => 'Barbearia TopGast', 'slug' => 'barbearia-topgast',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->clienteA = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana', 'lgpd_consent' => true]);
    $this->clienteB = Cliente::create(['company_id' => $this->company->id, 'name' => 'Bruno', 'lgpd_consent' => true]);
});

function makeAgGast(string $companyId, string $profId, string $clienteId, string $servicoId, float $valor, string $status = 'finalizado', int $diasAtras = 10): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => now()->subDays($diasAtras),
        'duracao' => 30,
        'valor' => $valor,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('cliente_top_gastadores', function () {
    it('retorna estrutura correta sem agendamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.top-gastadores'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['total', 'items']);
        expect($data['total'])->toBe(0);
        expect($data['items'])->toBeEmpty();
    });

    it('ordena clientes por maior receita', function () {
        makeAgGast($this->company->id, $this->prof->id, $this->clienteA->id, $this->servico->id, 200);
        makeAgGast($this->company->id, $this->prof->id, $this->clienteA->id, $this->servico->id, 150);
        makeAgGast($this->company->id, $this->prof->id, $this->clienteB->id, $this->servico->id, 100);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.top-gastadores'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(2);
        expect($data['items'][0]['cliente_id'])->toBe($this->clienteA->id);
        expect((float) $data['items'][0]['receita_total'])->toBe(350.0);
        expect($data['items'][0]['total_agendamentos'])->toBe(2);
    });

    it('ignora agendamentos não finalizados', function () {
        makeAgGast($this->company->id, $this->prof->id, $this->clienteA->id, $this->servico->id, 200, 'confirmado');

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.top-gastadores'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('respeita o parâmetro dias', function () {
        makeAgGast($this->company->id, $this->prof->id, $this->clienteA->id, $this->servico->id, 200, 'finalizado', 5);
        makeAgGast($this->company->id, $this->prof->id, $this->clienteB->id, $this->servico->id, 300, 'finalizado', 60);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.top-gastadores', ['dias' => 30]))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['items'][0]['cliente_id'])->toBe($this->clienteA->id);
    });

    it('não retorna dados de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-topgast', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $clienteOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'lgpd_consent' => false]);
        $servicoOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'Z', 'duracao_minutos' => 30, 'preco' => 10, 'ativo' => true]);
        makeAgGast($outra->id, $profOutra->id, $clienteOutra->id, $servicoOutra->id, 500);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.top-gastadores'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('clientes.top-gastadores'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('clientes.top-gastadores'))
            ->assertUnauthorized();
    });
});
