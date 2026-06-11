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
        'name' => 'Barbearia ProfRank', 'slug' => 'barbearia-profrank',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'S', 'duracao_minutos' => 30, 'preco' => 50.0, 'cor' => '#111', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'C', 'phone' => '11999990001']);
});

describe('profissional_ranking', function () {
    it('retorna estrutura correta', function () {
        Profissional::create(['company_id' => $this->company->id, 'name' => 'Ana', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.ranking'))
            ->assertOk()
            ->json();

        expect($data[0])->toHaveKeys(['profissional_id', 'profissional_nome', 'especialidade', 'cor', 'finalizados', 'receita_total']);
    });

    it('ordena por receita decrescente', function () {
        $p1 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Baixo', 'ativo' => true]);
        $p2 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Alto', 'ativo' => true]);

        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $p1->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->subDay(),
            'duracao' => 30,
            'valor' => 50.0,
            'status' => 'finalizado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $p2->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => now()->subDay(),
            'duracao' => 30,
            'valor' => 200.0,
            'status' => 'finalizado',
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.ranking'))
            ->json();

        expect($data[0]['profissional_nome'])->toBe('Alto');
        expect((float) $data[0]['receita_total'])->toBe(200.0);
        expect($data[1]['profissional_nome'])->toBe('Baixo');
    });

    it('não inclui inativos', function () {
        $inativo = Profissional::create(['company_id' => $this->company->id, 'name' => 'Inativo', 'ativo' => false]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.ranking'))
            ->json();

        $ids = collect($data)->pluck('profissional_id')->all();
        expect($ids)->not->toContain($inativo->id);
    });

    it('não inclui profissionais de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-profrank', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.ranking'))
            ->json();

        $ids = collect($data)->pluck('profissional_id')->all();
        expect($ids)->not->toContain($profOutra->id);
    });

    it('analista pode ver ranking', function () {
        $this->actingAs($this->analista)
            ->getJson(route('profissionais.ranking'))
            ->assertOk();
    });

    it('unauthenticated é redirecionado', function () {
        $this->getJson(route('profissionais.ranking'))
            ->assertUnauthorized();
    });
});
