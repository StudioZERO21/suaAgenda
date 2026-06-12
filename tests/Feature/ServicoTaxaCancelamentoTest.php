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
        'name' => 'Barbearia TC', 'slug' => 'barbearia-tc',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof TC', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente TC', 'lgpd_consent' => true]);
});

function makeAgTC(string $companyId, string $profId, string $clienteId, string $servicoId, string $status): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => now()->subDays(5),
        'duracao' => 30,
        'valor' => 50,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('servico_taxa_cancelamento', function () {
    it('retorna estrutura correta sem dados', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.taxa-cancelamento'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo_dias', 'total', 'items']);
        expect($data['total'])->toBe(0);
        expect($data['items'])->toBeEmpty();
    });

    it('calcula taxa de cancelamento corretamente', function () {
        $s1 = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte TC', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);

        makeAgTC($this->company->id, $this->prof->id, $this->cliente->id, $s1->id, Agendamento::STATUS_FINALIZADO);
        makeAgTC($this->company->id, $this->prof->id, $this->cliente->id, $s1->id, Agendamento::STATUS_FINALIZADO);
        makeAgTC($this->company->id, $this->prof->id, $this->cliente->id, $s1->id, Agendamento::STATUS_CANCELADO);
        makeAgTC($this->company->id, $this->prof->id, $this->cliente->id, $s1->id, Agendamento::STATUS_CANCELADO);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.taxa-cancelamento'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['items'][0]['total_agendamentos'])->toBe(4);
        expect($data['items'][0]['cancelados'])->toBe(2);
        expect((float) $data['items'][0]['taxa_cancelamento_pct'])->toBe(50.0);
    });

    it('items têm campos corretos', function () {
        $s1 = Servico::create(['company_id' => $this->company->id, 'nome' => 'Barba TC', 'duracao_minutos' => 30, 'preco' => 40, 'ativo' => true]);
        makeAgTC($this->company->id, $this->prof->id, $this->cliente->id, $s1->id, Agendamento::STATUS_FINALIZADO);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.taxa-cancelamento'))
            ->assertOk()
            ->json();

        $item = $data['items'][0];
        expect($item)->toHaveKeys(['servico_id', 'servico_nome', 'cor', 'preco', 'ativo', 'total_agendamentos', 'cancelados', 'taxa_cancelamento_pct']);
    });

    it('ignora serviços sem agendamentos no período', function () {
        Servico::create(['company_id' => $this->company->id, 'nome' => 'Sem Ag TC', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.taxa-cancelamento'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('ignora dados de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra TC', 'slug' => 'outra-tc', 'plano' => 'trial', 'ativo' => true]);
        $srvOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'Srv TC', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'Prof', 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Cli', 'lgpd_consent' => true]);
        makeAgTC($outra->id, $profOutra->id, $cliOutra->id, $srvOutra->id, Agendamento::STATUS_FINALIZADO);

        $data = $this->actingAs($this->admin)
            ->getJson(route('servicos.taxa-cancelamento'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('servicos.taxa-cancelamento'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('servicos.taxa-cancelamento'))
            ->assertUnauthorized();
    });
});
