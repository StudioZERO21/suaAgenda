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
        'name' => 'Barbearia PorMes', 'slug' => 'barbearia-pormes',
        'plano' => 'trial', 'ativo' => true,
    ]);

    $this->admin = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->admin->assignRole('admin_empresa');

    $this->analista = User::factory()->create(['empresa_id' => $this->company->id]);
    $this->analista->assignRole('analista');

    $this->prof = Profissional::create(['company_id' => $this->company->id, 'name' => 'Carlos', 'ativo' => true]);
    $this->cliente = Cliente::create(['company_id' => $this->company->id, 'name' => 'Ana', 'lgpd_consent' => true]);
    $this->servico = Servico::create(['company_id' => $this->company->id, 'nome' => 'Corte', 'duracao_minutos' => 30, 'preco' => 50, 'ativo' => true]);
});

function makeAgMes(string $companyId, string $profId, string $clienteId, string $servicoId, string $status, float $valor, int $ano, int $mes): Agendamento
{
    return Agendamento::create([
        'company_id' => $companyId,
        'profissional_id' => $profId,
        'cliente_id' => $clienteId,
        'servico_id' => $servicoId,
        'data_hora' => "{$ano}-".str_pad((string) $mes, 2, '0', STR_PAD_LEFT).'-15 10:00:00',
        'duracao' => 30,
        'valor' => $valor,
        'status' => $status,
        'cancel_token' => Agendamento::generateCancelToken(),
    ]);
}

describe('agendamentos_por_mes', function () {
    it('retorna 12 meses mesmo sem agendamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.por-mes'))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['ano', 'total_ano', 'receita_ano', 'meses']);
        expect($data['meses'])->toHaveCount(12);
        expect($data['total_ano'])->toBe(0);
        expect((float) $data['receita_ano'])->toBe(0.0);
    });

    it('conta agendamentos no mês correto', function () {
        $ano = now()->year;
        makeAgMes($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 100, $ano, 3);
        makeAgMes($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_CANCELADO, 50, $ano, 3);
        makeAgMes($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 80, $ano, 7);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.por-mes', ['ano' => $ano]))
            ->assertOk()
            ->json();

        expect($data['total_ano'])->toBe(3);
        expect((float) $data['receita_ano'])->toBe(180.0);

        $marco = collect($data['meses'])->firstWhere('mes', 3);
        expect($marco['total'])->toBe(2);
        expect($marco['finalizados'])->toBe(1);
        expect($marco['cancelados'])->toBe(1);
        expect((float) $marco['receita'])->toBe(100.0);

        $julho = collect($data['meses'])->firstWhere('mes', 7);
        expect($julho['total'])->toBe(1);
    });

    it('aceita parâmetro ano', function () {
        $anoPassado = now()->year - 1;
        makeAgMes($this->company->id, $this->prof->id, $this->cliente->id, $this->servico->id, Agendamento::STATUS_FINALIZADO, 50, $anoPassado, 6);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.por-mes', ['ano' => $anoPassado]))
            ->assertOk()
            ->json();

        expect($data['ano'])->toBe($anoPassado);
        expect($data['total_ano'])->toBe(1);
    });

    it('não retorna dados de outra empresa', function () {
        $outra = Company::create(['name' => 'Outra', 'slug' => 'outra-pormes', 'plano' => 'trial', 'ativo' => true]);
        $profOutra = Profissional::create(['company_id' => $outra->id, 'name' => 'X', 'ativo' => true]);
        $clienteOutra = Cliente::create(['company_id' => $outra->id, 'name' => 'Y', 'lgpd_consent' => false]);
        $servicoOutra = Servico::create(['company_id' => $outra->id, 'nome' => 'Z', 'duracao_minutos' => 30, 'preco' => 10, 'ativo' => true]);
        makeAgMes($outra->id, $profOutra->id, $clienteOutra->id, $servicoOutra->id, Agendamento::STATUS_FINALIZADO, 50, now()->year, 6);

        $data = $this->actingAs($this->admin)
            ->getJson(route('agendamentos.por-mes'))
            ->assertOk()
            ->json();

        expect($data['total_ano'])->toBe(0);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('agendamentos.por-mes'))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('agendamentos.por-mes'))
            ->assertUnauthorized();
    });
});
