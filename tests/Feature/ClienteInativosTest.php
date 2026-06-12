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
        'name' => 'Barbearia IN', 'slug' => 'barbearia-in',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof IN', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte IN', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
});

function makeAgIN(string $companyId, string $profId, string $clienteId, string $servicoId, int $diasAtras): Agendamento
{
    $ag = Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => now()->subDays($diasAtras),
        'duracao' => 30,
        'valor' => 50,
        'status' => Agendamento::STATUS_FINALIZADO,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);

    return $ag;
}

describe('cliente_inativos', function () {
    it('retorna estrutura correta sem clientes inativos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.inativos'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['dias_inatividade', 'total', 'items']);
        expect($data['total'])->toBe(0);
        expect($data['dias_inatividade'])->toBe(60);
    });

    it('retorna clientes com última visita antes do corte', function () {
        $clienteInativo = Cliente::create(['company_id' => $this->company->id, 'name' => 'Inativo IN', 'lgpd_consent' => true]);
        $clienteAtivo = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ativo IN', 'lgpd_consent' => true]);

        makeAgIN($this->company->id, $this->prof->id, $clienteInativo->id, $this->servico->id, 90);
        makeAgIN($this->company->id, $this->prof->id, $clienteAtivo->id, $this->servico->id, 10);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.inativos', ['dias' => 60]))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['items'][0]['nome'])->toBe('Inativo IN');
        expect($data['items'][0]['dias_sem_visita'])->toBeGreaterThanOrEqual(89);
    });

    it('não retorna clientes sem nenhum agendamento', function () {
        Cliente::create(['company_id' => $this->company->id, 'name' => 'Sem Ag IN', 'lgpd_consent' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.inativos'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('não retorna clientes com agendamento cancelado no passado', function () {
        $cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Canc IN', 'lgpd_consent' => true]);

        $ag = makeAgIN($this->company->id, $this->prof->id, $cliente->id, $this->servico->id, 90);
        $ag->status = Agendamento::STATUS_CANCELADO;
        $ag->save();

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.inativos'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('ignora clientes de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra IN', 'slug' => 'outra-in', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'Prof', 'ativo' => true]);
        $srvOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'Srv', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Cli Outra', 'lgpd_consent' => true]);
        makeAgIN($outra->id, $profOutra->id, $cliOutra->id, $srvOutra->id, 90);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.inativos'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('respeita parâmetro dias_inatividade personalizado', function () {
        $cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cli Dias IN', 'lgpd_consent' => true]);
        makeAgIN($this->company->id, $this->prof->id, $cliente->id, $this->servico->id, 20);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.inativos', ['dias' => 15]))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['dias_inatividade'])->toBe(15);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('clientes.inativos'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('clientes.inativos'))
            ->assertUnauthorized();
    });
});
