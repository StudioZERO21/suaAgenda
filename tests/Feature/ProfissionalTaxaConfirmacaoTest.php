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
        'name' => 'Barbearia TaxaConf', 'slug' => 'barbearia-taxaconf',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana', 'lgpd_consent' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
});

function makeAgTaxa(string $companyId, string $profId, string $clienteId, string $servicoId, string $status, int $diasAtras = 5): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => now()->subDays($diasAtras),
        'duracao' => 30,
        'valor' => 50,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('profissional_taxa_confirmacao', function () {
    it('retorna estrutura correta sem agendamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.taxa-confirmacao', $this->prof))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys([
            'profissional_id', 'profissional_nome', 'periodo_dias',
            'total', 'finalizados', 'confirmados', 'pendentes', 'cancelados',
            'taxa_conclusao', 'taxa_cancelamento',
        ]);
        expect($data['total'])->toBe(0);
        expect($data['taxa_conclusao'])->toBeNull();
        expect($data['taxa_cancelamento'])->toBeNull();
    });

    it('calcula taxas corretamente', function () {
        makeAgTaxa($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO);
        makeAgTaxa($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO);
        makeAgTaxa($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_CANCELADO);
        makeAgTaxa($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_CANCELADO);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.taxa-confirmacao', $this->prof))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(4);
        expect($data['finalizados'])->toBe(2);
        expect($data['cancelados'])->toBe(2);
        expect((float) $data['taxa_conclusao'])->toBe(50.0);
        expect((float) $data['taxa_cancelamento'])->toBe(50.0);
    });

    it('respeita o parâmetro dias', function () {
        makeAgTaxa($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 5);
        makeAgTaxa($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 60);

        $dataRecente = $this->actingAs($this->admin)
            ->getJson(route('profissionais.taxa-confirmacao', [$this->prof, 'dias' => 30]))
            ->assertOk()
            ->json();
        expect($dataRecente['total'])->toBe(1);

        $dataTodo = $this->actingAs($this->admin)
            ->getJson(route('profissionais.taxa-confirmacao', [$this->prof, 'dias' => 90]))
            ->assertOk()
            ->json();
        expect($dataTodo['total'])->toBe(2);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('profissionais.taxa-confirmacao', $this->prof))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('profissionais.taxa-confirmacao', $this->prof))
            ->assertUnauthorized();
    });
});
