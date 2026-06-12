<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cargo;
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
        'name' => 'Barbearia CM', 'slug' => 'barbearia-cm',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte CM', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente CM', 'lgpd_consent' => true]);
});

function makeAgCM(string $companyId, string $profId, string $clienteId, string $servicoId, float $valor): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => now()->startOfMonth()->addDays(2),
        'duracao' => 30,
        'valor' => $valor,
        'status' => Agendamento::STATUS_FINALIZADO,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('profissional_comissao_mensal', function () {
    it('retorna estrutura correta sem profissionais', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.comissao-mensal'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['mes', 'ano', 'mes_fmt', 'total_profissionais', 'receita_total', 'comissao_total', 'items']);
        expect($data['total_profissionais'])->toBe(0);
        expect((float) $data['receita_total'])->toBe(0.0);
        expect((float) $data['comissao_total'])->toBe(0.0);
    });

    it('calcula comissão usando comissao_pct do profissional', function () {
        $prof = Profissional::create([
            'company_id' => $this->company->id, 'name' => 'Prof CM', 'ativo' => true,
            'comissao_pct' => 20,
        ]);
        makeAgCM($this->company->id, $prof->id, $this->cliente->id, $this->servico->id, 100.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.comissao-mensal'))
            ->assertOk()
            ->json();

        expect($data['total_profissionais'])->toBe(1);
        expect((float) $data['receita_total'])->toBe(100.0);
        expect((float) $data['comissao_total'])->toBe(20.0);
        expect((float) $data['items'][0]['comissao'])->toBe(20.0);
        expect((float) $data['items'][0]['comissao_pct'])->toBe(20.0);
    });

    it('usa comissao_pct do cargo quando profissional não tem', function () {
        $cargo = Cargo::create([
            'company_id' => $this->company->id, 'nome' => 'Barbeiro CM',
            'comissao_pct' => 15,
        ]);
        $prof = Profissional::create([
            'company_id' => $this->company->id, 'name' => 'Prof Cargo CM', 'ativo' => true,
            'cargo_id' => $cargo->id, 'comissao_pct' => null,
        ]);
        makeAgCM($this->company->id, $prof->id, $this->cliente->id, $this->servico->id, 200.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.comissao-mensal'))
            ->assertOk()
            ->json();

        expect((float) $data['comissao_total'])->toBe(30.0);
        expect((float) $data['items'][0]['comissao_pct'])->toBe(15.0);
    });

    it('profissional sem agendamento no mês tem receita zero', function () {
        Profissional::create(['company_id' => $this->company->id, 'name' => 'Sem Ag CM', 'ativo' => true, 'comissao_pct' => 10]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.comissao-mensal'))
            ->assertOk()
            ->json();

        expect($data['total_profissionais'])->toBe(1);
        expect((float) $data['receita_total'])->toBe(0.0);
        expect((float) $data['items'][0]['receita'])->toBe(0.0);
        expect((float) $data['items'][0]['comissao'])->toBe(0.0);
    });

    it('ignora profissionais de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra CM', 'slug' => 'outra-cm', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'Outro', 'ativo' => true, 'comissao_pct' => 20]);
        $srvOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'Srv', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Cli', 'lgpd_consent' => true]);
        makeAgCM($outra->id, $profOutra->id, $cliOutra->id, $srvOutra->id, 100.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.comissao-mensal'))
            ->assertOk()
            ->json();

        expect($data['total_profissionais'])->toBe(0);
    });

    it('filtra por mes e ano', function () {
        $prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof Mes CM', 'ativo' => true, 'comissao_pct' => 10]);

        $ag = makeAgCM($this->company->id, $prof->id, $this->cliente->id, $this->servico->id, 50.0);
        $ag->data_hora = now()->subMonths(2)->startOfMonth()->addDays(1);
        $ag->save();

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.comissao-mensal'))
            ->assertOk()
            ->json();

        expect((float) $data['receita_total'])->toBe(0.0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('profissionais.comissao-mensal'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('profissionais.comissao-mensal'))
            ->assertUnauthorized();
    });
});
