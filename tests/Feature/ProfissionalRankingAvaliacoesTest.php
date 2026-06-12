<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Avaliacao;
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
        'name' => 'Barbearia RankAval', 'slug' => 'barbearia-rankaval',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof1 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Ana', 'ativo' => true]);
    $this->prof2 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Bruno', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente RK', 'lgpd_consent' => true]);
});

function makeAvaliacaoRankAval(string $companyId, string $profId, string $clienteId, string $servicoId, int $nota): Avaliacao
{
    $ag = Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => now()->subDays(2),
        'duracao' => 30,
        'valor' => 50,
        'status' => Agendamento::STATUS_FINALIZADO,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);

    return Avaliacao::create([
        'company_id' => $companyId,
        'agendamento_id' => $ag->id,
        'nota' => $nota,
    ]);
}

describe('profissional_ranking_avaliacoes', function () {
    it('retorna todos profissionais ativos mesmo sem avaliações', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.ranking-avaliacoes'))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(2);
        expect($data[0])->toHaveKeys(['profissional_id', 'profissional_nome', 'total_avaliacoes', 'media_nota', 'distribuicao']);
        expect($data[0]['media_nota'])->toBeNull();
        expect($data[0]['total_avaliacoes'])->toBe(0);
    });

    it('calcula media e distribuição corretamente', function () {
        makeAvaliacaoRankAval($this->company->id, $this->prof1->id, $this->cliente->id, $this->servico->id, 5);
        makeAvaliacaoRankAval($this->company->id, $this->prof1->id, $this->cliente->id, $this->servico->id, 4);
        makeAvaliacaoRankAval($this->company->id, $this->prof1->id, $this->cliente->id, $this->servico->id, 3);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.ranking-avaliacoes'))
            ->assertOk()
            ->json();

        $ana = collect($data)->firstWhere('profissional_nome', 'Ana');
        expect($ana['total_avaliacoes'])->toBe(3);
        expect((float) $ana['media_nota'])->toBe(4.0);
        expect($ana['distribuicao']['5'])->toBe(1);
        expect($ana['distribuicao']['4'])->toBe(1);
        expect($ana['distribuicao']['3'])->toBe(1);
        expect($ana['distribuicao']['1'])->toBe(0);
    });

    it('ordena por media_nota decrescente', function () {
        makeAvaliacaoRankAval($this->company->id, $this->prof1->id, $this->cliente->id, $this->servico->id, 3);
        makeAvaliacaoRankAval($this->company->id, $this->prof2->id, $this->cliente->id, $this->servico->id, 5);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.ranking-avaliacoes'))
            ->assertOk()
            ->json();

        expect($data[0]['profissional_nome'])->toBe('Bruno');
        expect($data[1]['profissional_nome'])->toBe('Ana');
    });

    it('filtro apenas_com_avaliacoes exclui sem avaliações', function () {
        makeAvaliacaoRankAval($this->company->id, $this->prof1->id, $this->cliente->id, $this->servico->id, 4);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.ranking-avaliacoes', ['apenas_com_avaliacoes' => true]))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(1);
        expect($data[0]['profissional_nome'])->toBe('Ana');
    });

    it('ignora profissionais de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra RA', 'slug' => 'outra-ra', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'Outro', 'ativo' => true]);
        $srvOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'Srv', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Cli', 'lgpd_consent' => true]);
        makeAvaliacaoRankAval($outra->id, $profOutra->id, $cliOutra->id, $srvOutra->id, 5);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.ranking-avaliacoes'))
            ->assertOk()
            ->json();

        expect($data)->toHaveCount(2);
        foreach ($data as $row) {
            expect($row['total_avaliacoes'])->toBe(0);
        }
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('profissionais.ranking-avaliacoes'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('profissionais.ranking-avaliacoes'))
            ->assertUnauthorized();
    });
});
