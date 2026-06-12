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
        'name' => 'Barbearia AD', 'slug' => 'barbearia-ad',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof AD', 'ativo' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte AD', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente AD', 'lgpd_consent' => true]);
});

function makeAvalAD(string $companyId, string $profId, string $clienteId, string $servicoId, int $nota): Avaliacao
{
    $ag = Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => now()->subDays(1),
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

describe('avaliacao_distribuicao', function () {
    it('retorna estrutura correta sem avaliações', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('avaliacoes.distribuicao'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['total', 'media', 'periodo_dias', 'profissional_id', 'distribuicao']);
        expect($data['total'])->toBe(0);
        expect($data['media'])->toBeNull();
        expect($data['distribuicao'])->toHaveCount(5);
        expect($data['distribuicao'][0]['estrelas'])->toBe(1);
        expect($data['distribuicao'][4]['estrelas'])->toBe(5);
    });

    it('calcula distribuição corretamente', function () {
        makeAvalAD($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 5);
        makeAvalAD($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 5);
        makeAvalAD($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 3);

        $data = $this->actingAs($this->admin)
            ->getJson(route('avaliacoes.distribuicao'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(3);
        expect((float) $data['media'])->toBe(round(13 / 3, 2));

        $por_estrela = collect($data['distribuicao'])->keyBy('estrelas');
        expect($por_estrela[5]['quantidade'])->toBe(2);
        expect((float) $por_estrela[5]['percentual'])->toBe(round(2 / 3 * 100, 1));
        expect($por_estrela[3]['quantidade'])->toBe(1);
        expect($por_estrela[1]['quantidade'])->toBe(0);
        expect((float) $por_estrela[1]['percentual'])->toBe(0.0);
    });

    it('filtra por profissional_id', function () {
        $prof2 = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof2 AD', 'ativo' => true]);
        makeAvalAD($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 4);
        makeAvalAD($this->company->id, $prof2->id, $this->cliente->id, $this->servico->id, 2);

        $data = $this->actingAs($this->admin)
            ->getJson(route('avaliacoes.distribuicao', ['profissional_id' => $this->prof->id]))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect((float) $data['media'])->toBe(4.0);
        expect($data['profissional_id'])->toBe($this->prof->id);
    });

    it('filtra por periodo_dias', function () {
        $aval = makeAvalAD($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 5);
        $aval->created_at = now()->subDays(40);
        $aval->save();

        makeAvalAD($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, 3);

        $data = $this->actingAs($this->admin)
            ->getJson(route('avaliacoes.distribuicao', ['periodo_dias' => 30]))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(1);
        expect($data['periodo_dias'])->toBe(30);
    });

    it('ignora avaliações de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra AD', 'slug' => 'outra-ad', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'Prof', 'ativo' => true]);
        $srvOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'Srv', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
        $cliOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Cli', 'lgpd_consent' => true]);
        makeAvalAD($outra->id, $profOutra->id, $cliOutra->id, $srvOutra->id, 5);

        $data = $this->actingAs($this->admin)
            ->getJson(route('avaliacoes.distribuicao'))
            ->assertOk()
            ->json();

        expect($data['total'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('avaliacoes.distribuicao'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('avaliacoes.distribuicao'))
            ->assertUnauthorized();
    });
});
