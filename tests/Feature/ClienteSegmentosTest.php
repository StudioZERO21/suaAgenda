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
        'name' => 'Barbearia Seg', 'slug' => 'barbearia-seg',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'P', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'S', 'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true]);
});

function criarAgendamentos(string $clienteId, string $companyId, string $profId, string $servicoId, int $qtd): void
{
    for ($i = 0; $i < $qtd; $i++) {
        Agendamento::create([
            'company_id' => $companyId,
            'profissional_id' => $profId,
            'cliente_id' => $clienteId,
            'servico_id' => $servicoId,
            'data_hora' => now()->subDays($i + 1),
            'duracao' => 30,
            'status' => 'finalizado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);
    }
}

describe('cliente_segmentos', function () {
    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.segmentos'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo_dias', 'total_ativos', 'segmentos']);
        expect(count($data['segmentos']))->toBe(4);
        expect($data['segmentos'][0])->toHaveKeys(['nome', 'descricao', 'total']);
    });

    it('classifica clientes corretamente', function () {
        $vip = Cliente::create(['company_id' => $this->company->id, 'name' => 'VIP', 'phone' => '11000000001', 'ativo' => true]);
        $rec = Cliente::create(['company_id' => $this->company->id, 'name' => 'Rec', 'phone' => '11000000002', 'ativo' => true]);
        $novo = Cliente::create(['company_id' => $this->company->id, 'name' => 'Novo', 'phone' => '11000000003', 'ativo' => true]);
        $inativo = Cliente::create(['company_id' => $this->company->id, 'name' => 'Inativo', 'phone' => '11000000004', 'ativo' => true]);

        criarAgendamentos($vip->id, $this->company->id, $this->prof->id, $this->servico->id, 5);
        criarAgendamentos($rec->id, $this->company->id, $this->prof->id, $this->servico->id, 2);
        criarAgendamentos($novo->id, $this->company->id, $this->prof->id, $this->servico->id, 1);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.segmentos'))
            ->json();

        $segmentos = collect($data['segmentos'])->keyBy('nome');
        expect($segmentos['VIP']['total'])->toBe(1);
        expect($segmentos['Recorrente']['total'])->toBe(1);
        expect($segmentos['Novo']['total'])->toBe(1);
        expect($segmentos['Inativo']['total'])->toBe(1);
    });

    it('retorna apenas clientes ativos', function () {
        Cliente::create(['company_id' => $this->company->id, 'name' => 'Ativo', 'phone' => '11000000010', 'ativo' => true]);
        Cliente::create(['company_id' => $this->company->id, 'name' => 'Inativo', 'phone' => '11000000011', 'ativo' => false]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.segmentos'))
            ->json();

        expect($data['total_ativos'])->toBe(1);
    });

    it('analista pode ver segmentos', function () {
        $this->actingAs($this->analista)
            ->getJson(route('clientes.segmentos'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('clientes.segmentos'))
            ->assertUnauthorized();
    });
});
