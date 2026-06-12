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
        'name' => 'Barbearia SE', 'slug' => 'barbearia-se',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente SE', 'lgpd_consent' => true]);
});

function makeAgSE(string $companyId, string $profId, string $clienteId, string $servicoId, float $valor = 50.0): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => now()->subDays(1),
        'duracao' => 30,
        'valor' => $valor,
        'status' => Agendamento::STATUS_FINALIZADO,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('profissional_servicos_executados', function () {
    it('retorna estrutura correta sem profissionais', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.servicos-executados'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo_dias', 'items']);
        expect($data['items'])->toBeEmpty();
    });

    it('lista serviços por profissional com contagem', function () {
        $prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof SE', 'ativo' => true]);
        $corte = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte SE', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
        $barba = Servico::create(['company_id' => $this->company->id, 'nome' => 'Barba SE', 'duracao_minutos' => 20, 'preco' => 30, 'ativo' => true]);

        makeAgSE($this->company->id, $prof->id, $this->cliente->id, $corte->id, 50.0);
        makeAgSE($this->company->id, $prof->id, $this->cliente->id, $corte->id, 50.0);
        makeAgSE($this->company->id, $prof->id, $this->cliente->id, $barba->id, 30.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.servicos-executados'))
            ->assertOk()
            ->json();

        expect($data['items'])->toHaveCount(1);
        $item = $data['items'][0];
        expect($item['profissional_nome'])->toBe('Prof SE');
        expect($item['total_execucoes'])->toBe(3);
        expect((float) $item['receita_total'])->toBe(130.0);
        expect($item['servicos'])->toHaveCount(2);
        expect($item['servicos'][0]['servico_nome'])->toBe('Corte SE');
        expect($item['servicos'][0]['execucoes'])->toBe(2);
    });

    it('exclui profissionais inativos', function () {
        $inativo = Profissional::create(['company_id' => $this->company->id, 'name' => 'Inativo SE', 'ativo' => false]);
        $srv = Servico::create(['company_id' => $this->company->id, 'nome' => 'Srv SE', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
        makeAgSE($this->company->id, $inativo->id, $this->cliente->id, $srv->id);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.servicos-executados'))
            ->assertOk()
            ->json();

        expect($data['items'])->toBeEmpty();
    });

    it('filtra por periodo_dias', function () {
        $prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof Periodo SE', 'ativo' => true]);
        $srv = Servico::create(['company_id' => $this->company->id, 'nome' => 'Srv P SE', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);

        $ag = makeAgSE($this->company->id, $prof->id, $this->cliente->id, $srv->id);
        $ag->data_hora = now()->subDays(40);
        $ag->save();

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.servicos-executados', ['periodo_dias' => 30]))
            ->assertOk()
            ->json();

        expect($data['items'][0]['total_execucoes'])->toBe(0);
        expect($data['items'][0]['servicos'])->toBeEmpty();
    });

    it('ignora agendamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra SE', 'slug' => 'outra-se', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'Prof', 'ativo' => true]);
        $srvOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'Srv', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Cli', 'lgpd_consent' => true]);
        makeAgSE($outra->id, $profOutra->id, $cliOutra->id, $srvOutra->id);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.servicos-executados'))
            ->assertOk()
            ->json();

        expect($data['items'])->toBeEmpty();
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('profissionais.servicos-executados'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('profissionais.servicos-executados'))
            ->assertUnauthorized();
    });
});
