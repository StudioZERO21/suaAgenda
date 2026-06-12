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
        'name' => 'Barbearia CH', 'slug' => 'barbearia-ch',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte CH', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Cliente CH', 'lgpd_consent' => true]);
});

function makeAgCH(string $companyId, string $profId, string $clienteId, string $servicoId, int $duracao, float $valor): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => now()->subDays(5),
        'duracao' => $duracao,
        'valor' => $valor,
        'status' => Agendamento::STATUS_FINALIZADO,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('profissional_carga_horaria', function () {
    it('retorna estrutura correta sem dados', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.carga-horaria'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['periodo_dias', 'total_profissionais', 'total_horas', 'items']);
        expect($data['total_profissionais'])->toBe(0);
        expect((float) $data['total_horas'])->toBe(0.0);
    });

    it('items têm campos corretos', function () {
        $prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof CH', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.carga-horaria'))
            ->assertOk()
            ->json();

        $item = $data['items'][0];
        expect($item)->toHaveKeys(['profissional_id', 'profissional_nome', 'especialidade', 'cor', 'total_agendamentos', 'total_minutos', 'total_horas', 'receita', 'receita_por_hora']);
    });

    it('calcula horas corretamente', function () {
        $prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Prof CH Horas', 'ativo' => true]);
        makeAgCH($this->company->id, $prof->id, $this->cliente->id, $this->servico->id, 60, 100.0);
        makeAgCH($this->company->id, $prof->id, $this->cliente->id, $this->servico->id, 30, 50.0);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.carga-horaria'))
            ->assertOk()
            ->json();

        expect($data['items'][0]['total_minutos'])->toBe(90);
        expect((float) $data['items'][0]['total_horas'])->toBe(1.5);
        expect((float) $data['items'][0]['receita'])->toBe(150.0);
    });

    it('exclui profissionais inativos', function () {
        Profissional::create(['company_id' => $this->company->id, 'name' => 'Inativo CH', 'ativo' => false]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.carga-horaria'))
            ->assertOk()
            ->json();

        expect($data['total_profissionais'])->toBe(0);
    });

    it('ignora profissionais de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra CH', 'slug' => 'outra-ch', 'plano' => 'trial', 'ativo' => true]);
        Profissional::create(['company_id' => $outra->id, 'name' => 'Prof Outra CH', 'ativo' => true]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.carga-horaria'))
            ->assertOk()
            ->json();

        expect($data['total_profissionais'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('profissionais.carga-horaria'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('profissionais.carga-horaria'))
            ->assertUnauthorized();
    });
});
