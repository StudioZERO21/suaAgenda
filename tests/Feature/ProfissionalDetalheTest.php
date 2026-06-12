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
        'name' => 'Barbearia ProfDet', 'slug' => 'barbearia-profdet',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create([
        'company_id' => $this->company->id,
        'name' => 'Bruno Costa',
        'especialidade' => 'Barbeiro',
        'phone' => '11999990055',
        'cor' => '#112233',
        'comissao_pct' => 25.0,
        'ativo' => true,
    ]);
});

describe('profissional_detalhe', function () {
    it('retorna estrutura correta', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.detalhe', $this->prof))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['id', 'name', 'especialidade', 'phone', 'instagram', 'cor', 'comissao_pct', 'ativo', 'foto_url', 'servicos', 'stats_mes']);
        expect($data['stats_mes'])->toHaveKeys(['total', 'finalizados', 'receita', 'taxa_conclusao', 'nota_media']);
    });

    it('retorna dados corretos do profissional', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.detalhe', $this->prof))
            ->json();

        expect($data['name'])->toBe('Bruno Costa');
        expect($data['especialidade'])->toBe('Barbeiro');
        expect($data['cor'])->toBe('#112233');
        expect((float) $data['comissao_pct'])->toBe(25.0);
        expect($data['ativo'])->toBeTrue();
    });

    it('inclui apenas serviços ativos vinculados', function () {
        $s1 = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true]);
        $s2 = Servico::create(['company_id' => $this->company->id, 'nome' => 'Barba', 'duracao_minutos' => 20, 'preco' => 30.0, 'cor' => '#222', 'ativo' => false]);
        $this->prof->servicos()->sync([$s1->id, $s2->id]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.detalhe', $this->prof))
            ->json();

        expect(count($data['servicos']))->toBe(1);
        expect($data['servicos'][0]['nome'])->toBe('Corte');
    });

    it('inclui stats do mês', function () {
        $servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'S', 'duracao_minutos' => 30, 'preco' => 60.0, 'cor' => '#000', 'ativo' => true]);
        $cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'C', 'phone' => '11999990001']);

        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'data_hora' => now()->startOfMonth()->addDays(1),
            'duracao' => 30,
            'valor' => 60.0,
            'status' => 'finalizado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.detalhe', $this->prof))
            ->json();

        expect($data['stats_mes']['total'])->toBe(1);
        expect($data['stats_mes']['finalizados'])->toBe(1);
        expect((float) $data['stats_mes']['receita'])->toBe(60.0);
    });

    it('não acessa profissional de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-profdet', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);

        $this->actingAs($this->admin)
            ->getJson(route('profissionais.detalhe', $profOutra))
            ->assertForbidden();
    });

    it('analista pode ver detalhe', function () {
        $this->actingAs($this->analista)
            ->getJson(route('profissionais.detalhe', $this->prof))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('profissionais.detalhe', $this->prof))
            ->assertUnauthorized();
    });
});
