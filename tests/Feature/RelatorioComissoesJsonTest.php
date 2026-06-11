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
        'name' => 'Barbearia Comiss', 'slug' => 'barbearia-comiss',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'João', 'phone' => '11999990001']);
});

describe('relatorio_comissoes_json', function () {
    it('retorna estrutura correta', function () {
        Profissional::create(['company_id' => $this->company->id, 'name' => 'Pedro', 'ativo' => true, 'comissao_pct' => 20.0]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.comissoes.json'))
            ->assertOk()
            ->json();

        expect($data[0])->toHaveKeys([
            'profissional_id', 'profissional_nome', 'cor',
            'finalizados', 'receita_bruta', 'comissao_pct', 'valor_comissao',
        ]);
    });

    it('calcula comissão corretamente', function () {
        $prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Ana', 'ativo' => true, 'comissao_pct' => 30.0]);

        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->subDay(),
            'duracao' => 30,
            'valor' => 100.0,
            'status' => 'finalizado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.comissoes.json'))
            ->json();

        $row = collect($data)->firstWhere('profissional_id', $prof->id);
        expect($row['finalizados'])->toBe(1);
        expect((float) $row['receita_bruta'])->toBe(100.0);
        expect((float) $row['comissao_pct'])->toBe(30.0);
        expect((float) $row['valor_comissao'])->toBe(30.0);
    });

    it('retorna zeros quando sem agendamentos finalizados', function () {
        $prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true, 'comissao_pct' => 20.0]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.comissoes.json'))
            ->json();

        $row = collect($data)->firstWhere('profissional_id', $prof->id);
        expect($row['finalizados'])->toBe(0);
        expect((float) $row['valor_comissao'])->toBe(0.0);
    });

    it('não inclui profissionais de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-comiss', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('relatorios.comissoes.json'))
            ->json();

        $ids = collect($data)->pluck('profissional_id')->all();
        expect($ids)->not->toContain($profOutra->id);
    });

    it('analista pode ver comissões', function () {
        $this->actingAs($this->analista)
            ->getJson(route('relatorios.comissoes.json'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('relatorios.comissoes.json'))
            ->assertUnauthorized();
    });
});
