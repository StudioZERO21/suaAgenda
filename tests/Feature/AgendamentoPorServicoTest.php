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
        'name' => 'Barbearia PorServ', 'slug' => 'barbearia-porserv',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'lgpd_consent' => true]);
    $this->servico1 = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->servico2 = Servico::create(['company_id' => $this->company->id, 'nome' => 'Barba', 'duracao_minutos' => 20, 'preco' => 35, 'ativo' => true]);
});

function makeAgPorServ(string $companyId, string $profId, string $clienteId, string $servicoId, string $status, float $valor, ?string $data = null): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => ($data ?? today()->toDateString()).' 10:00:00',
        'duracao' => 30,
        'valor' => $valor,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('agendamentos_por_servico', function () {
    it('retorna estrutura correta sem agendamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.por-servico'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['data', 'total_agendamentos', 'servicos']);
        expect($data['total_agendamentos'])->toBe(0);
        expect($data['servicos'])->toHaveCount(0);
    });

    it('agrupa agendamentos por serviço', function () {
        makeAgPorServ($this->company->id, $this->prof->id, $this->cliente->id, $this->servico1->id, Agendamento::STATUS_CONFIRMADO, 50);
        makeAgPorServ($this->company->id, $this->prof->id, $this->cliente->id, $this->servico1->id, Agendamento::STATUS_FINALIZADO, 50);
        makeAgPorServ($this->company->id, $this->prof->id, $this->cliente->id, $this->servico2->id, Agendamento::STATUS_PENDENTE, 35);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.por-servico'))
            ->assertOk()
            ->json();

        expect($data['total_agendamentos'])->toBe(3);
        expect($data['servicos'])->toHaveCount(2);

        $servico1data = collect($data['servicos'])->firstWhere('servico_id', $this->servico1->id);
        expect($servico1data['total'])->toBe(2);
        expect($servico1data['finalizados'])->toBe(1);
        expect((float) $servico1data['receita'])->toBe(50.0);
        expect($servico1data['servico_nome'])->toBe('Corte');
    });

    it('aceita parâmetro data', function () {
        $outraData = now()->addDays(3)->toDateString();
        makeAgPorServ($this->company->id, $this->prof->id, $this->cliente->id, $this->servico1->id, Agendamento::STATUS_CONFIRMADO, 50, $outraData);

        $resHoje = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.por-servico'))
            ->assertOk()
            ->json();
        expect($resHoje['total_agendamentos'])->toBe(0);

        $resFutura = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.por-servico', ['data' => $outraData]))
            ->assertOk()
            ->json();
        expect($resFutura['total_agendamentos'])->toBe(1);
    });

    it('não retorna agendamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-porserv', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $clienteOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'lgpd_consent' => false]);
        $servicoOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'Z', 'duracao_minutos' => 30, 'preco' => 10, 'ativo' => true]);
        makeAgPorServ($outra->id, $profOutra->id, $clienteOutra->id, $servicoOutra->id, Agendamento::STATUS_CONFIRMADO, 50);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.por-servico'))
            ->assertOk()
            ->json();

        expect($data['total_agendamentos'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('agendamentos.por-servico'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('agendamentos.por-servico'))
            ->assertUnauthorized();
    });
});
