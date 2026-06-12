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
        'name' => 'Barbearia TopFreq', 'slug' => 'barbearia-topfreq',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof TF', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte TF', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->c1 = Cliente::create(['company_id' => $this->company->id, 'name' => 'Frequente', 'lgpd_consent' => true]);
    $this->c2 = Cliente::create(['company_id' => $this->company->id, 'name' => 'Eventual', 'lgpd_consent' => true]);
});

function makeAgTopFreq(string $companyId, string $profId, string $clienteId, string $servicoId, float $valor = 50.0): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => now()->subDays(5),
        'duracao' => 30,
        'valor' => $valor,
        'status' => Agendamento::STATUS_FINALIZADO,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('cliente_top_frequentes', function () {
    it('retorna estrutura correta sem agendamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.top-frequentes'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo_dias', 'total', 'items']);
        expect($data['total'])->toBe(0);
        expect($data['items'])->toBeEmpty();
    });

    it('ordena por total de visitas descendente', function () {
        makeAgTopFreq($this->company->id, $this->prof->id, $this->c1->id, $this->servico->id);
        makeAgTopFreq($this->company->id, $this->prof->id, $this->c1->id, $this->servico->id);
        makeAgTopFreq($this->company->id, $this->prof->id, $this->c1->id, $this->servico->id);
        makeAgTopFreq($this->company->id, $this->prof->id, $this->c2->id, $this->servico->id);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.top-frequentes'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(2);
        expect($data['items'][0]['nome'])->toBe('Frequente');
        expect($data['items'][0]['total_visitas'])->toBe(3);
        expect($data['items'][1]['nome'])->toBe('Eventual');
        expect($data['items'][1]['total_visitas'])->toBe(1);
    });

    it('items têm campos corretos', function () {
        makeAgTopFreq($this->company->id, $this->prof->id, $this->c1->id, $this->servico->id, 80.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.top-frequentes'))
            ->assertOk()
            ->json();

        expect($data['items'][0])->toHaveKeys(['cliente_id', 'nome', 'phone', 'ativo', 'total_visitas', 'receita_total', 'ultima_visita']);
        expect((float) $data['items'][0]['receita_total'])->toBe(80.0);
    });

    it('respeita parâmetro dias', function () {
        makeAgTopFreq($this->company->id, $this->prof->id, $this->c1->id, $this->servico->id);

        $ag2 = makeAgTopFreq($this->company->id, $this->prof->id, $this->c2->id, $this->servico->id);
        $ag2->data_hora = now()->subDays(60);
        $ag2->save();

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.top-frequentes', ['dias' => 30]))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['items'][0]['nome'])->toBe('Frequente');
    });

    it('ignora agendamentos de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra TF', 'slug' => 'outra-tf', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'Prof', 'ativo' => true]);
        $srvOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'Srv', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Cli', 'lgpd_consent' => true]);
        makeAgTopFreq($outra->id, $profOutra->id, $cliOutra->id, $srvOutra->id);

        $data = $this->actingAs($this->admin)
            ->getJson(route('clientes.top-frequentes'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('clientes.top-frequentes'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('clientes.top-frequentes'))
            ->assertUnauthorized();
    });
});
