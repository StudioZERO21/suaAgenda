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
        'name' => 'Barbearia RPS', 'slug' => 'barbearia-rps',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana', 'lgpd_consent' => true]);
    $this->servico1 = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->servico2 = Servico::create(['company_id' => $this->company->id, 'nome' => 'Barba', 'duracao_minutos' => 20, 'preco' => 35, 'ativo' => true]);
});

function makeAgRPS(string $companyId, string $profId, string $clienteId, string $servicoId, float $valor, string $status = 'finalizado', int $diasAtras = 5): Agendamento
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

describe('profissional_receita_por_servico', function () {
    it('retorna estrutura correta sem agendamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.receita-por-servico', $this->prof))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['profissional_id', 'profissional_nome', 'total_servicos', 'receita_total', 'items']);
        expect($data['total_servicos'])->toBe(0);
        expect((float) $data['receita_total'])->toBe(0.0);
    });

    it('ordena serviços por receita decrescente', function () {
        makeAgRPS($this->company->id, $this->prof->id, $this->cliente->id, $this->servico1->id, 50);
        makeAgRPS($this->company->id, $this->prof->id, $this->cliente->id, $this->servico1->id, 50);
        makeAgRPS($this->company->id, $this->prof->id, $this->cliente->id, $this->servico2->id, 35);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.receita-por-servico', $this->prof))
            ->assertOk()
            ->json();

        expect($data['total_servicos'])->toBe(2);
        expect((float) $data['receita_total'])->toBe(135.0);
        expect($data['items'][0]['servico_nome'])->toBe('Corte');
        expect((float) $data['items'][0]['receita_total'])->toBe(100.0);
        expect($data['items'][0]['total_realizados'])->toBe(2);
    });

    it('ignora agendamentos não finalizados', function () {
        makeAgRPS($this->company->id, $this->prof->id, $this->cliente->id, $this->servico1->id, 50, Agendamento::STATUS_CONFIRMADO);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.receita-por-servico', $this->prof))
            ->assertOk()
            ->json();

        expect($data['total_servicos'])->toBe(0);
    });

    it('filtra por período com parâmetro dias', function () {
        makeAgRPS($this->company->id, $this->prof->id, $this->cliente->id, $this->servico1->id, 50, 'finalizado', 5);
        makeAgRPS($this->company->id, $this->prof->id, $this->cliente->id, $this->servico2->id, 35, 'finalizado', 60);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.receita-por-servico', [$this->prof, 'dias' => 30]))
            ->assertOk()
            ->json();

        expect($data['total_servicos'])->toBe(1);
        expect($data['items'][0]['servico_nome'])->toBe('Corte');
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('profissionais.receita-por-servico', $this->prof))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('profissionais.receita-por-servico', $this->prof))
            ->assertUnauthorized();
    });
});
