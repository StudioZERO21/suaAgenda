<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Avaliacao;
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
        'name' => 'Barbearia CargoEst', 'slug' => 'barbearia-cargoest',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->cargo = Cargo::create([
        'company_id' => $this->company->id,
        'nome' => 'Barbeiro',
        'nivel' => 1,
        'comissao_pct' => 40,
    ]);

    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente CE', 'lgpd_consent' => true]);
});

function makeAgCargoEst(string $companyId, string $profId, string $clienteId, string $servicoId, float $valor = 50.0): Agendamento
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

describe('cargo_estatisticas', function () {
    it('retorna estrutura correta sem profissionais', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('cargos.estatisticas', $this->cargo))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['cargo_id', 'cargo_nome', 'total_profissionais', 'ativos', 'inativos', 'agendamentos_mes', 'receita_mes', 'total_avaliacoes', 'media_avaliacao']);
        expect($data['total_profissionais'])->toBe(0);
        expect($data['agendamentos_mes'])->toBe(0);
        expect((float) $data['receita_mes'])->toBe(0.0);
        expect($data['media_avaliacao'])->toBeNull();
    });

    it('conta profissionais ativos e inativos', function () {
        Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof Ativo', 'cargo_id' => $this->cargo->id, 'ativo' => true]);
        Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof Inativo', 'cargo_id' => $this->cargo->id, 'ativo' => false]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('cargos.estatisticas', $this->cargo))
            ->assertOk()
            ->json();

        expect($data['total_profissionais'])->toBe(2);
        expect($data['ativos'])->toBe(1);
        expect($data['inativos'])->toBe(1);
    });

    it('calcula agendamentos e receita do mês', function () {
        $prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof CE', 'cargo_id' => $this->cargo->id, 'ativo' => true]);
        makeAgCargoEst($this->company->id, $prof->id, $this->cliente->id, $this->servico->id, 80.0);
        makeAgCargoEst($this->company->id, $prof->id, $this->cliente->id, $this->servico->id, 60.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('cargos.estatisticas', $this->cargo))
            ->assertOk()
            ->json();

        expect($data['agendamentos_mes'])->toBe(2);
        expect((float) $data['receita_mes'])->toBe(140.0);
    });

    it('calcula media de avaliacao', function () {
        $prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof Aval CE', 'cargo_id' => $this->cargo->id, 'ativo' => true]);
        $ag1 = makeAgCargoEst($this->company->id, $prof->id, $this->cliente->id, $this->servico->id);
        $ag2 = makeAgCargoEst($this->company->id, $prof->id, $this->cliente->id, $this->servico->id);

        Avaliacao::create(['company_id' => $this->company->id, 'agendamento_id' => $ag1->id, 'nota' => 5]);
        Avaliacao::create(['company_id' => $this->company->id, 'agendamento_id' => $ag2->id, 'nota' => 3]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('cargos.estatisticas', $this->cargo))
            ->assertOk()
            ->json();

        expect($data['total_avaliacoes'])->toBe(2);
        expect((float) $data['media_avaliacao'])->toBe(4.0);
    });

    it('rejeita acesso a cargo de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra CE', 'slug' => 'outra-ce', 'plano' => 'trial', 'ativo' => true]);
        $cargoOutra = Cargo::create(['company_id' => $outra->id, 'nome' => 'Outro', 'nivel' => 1, 'comissao_pct' => 30]);

        $this->actingAs($this->admin)
            ->getJson(route('cargos.estatisticas', $cargoOutra))
            ->assertNotFound();
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('cargos.estatisticas', $this->cargo))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('cargos.estatisticas', $this->cargo))
            ->assertUnauthorized();
    });
});
