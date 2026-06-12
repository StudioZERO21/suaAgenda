<?php

declare(strict_types=1);

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Company;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);

    $this->company = Company::create([
        'name' => 'Barbearia FatMen', 'slug' => 'barbearia-fatmen',
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

describe('profissional_faturamento_mensal', function () {
    it('retorna 12 meses com zeros sem agendamentos', function () {
        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.faturamento-mensal', $this->prof))
            ->assertOk()
            ->json();

        expect($data)->toHaveKeys(['profissional_id', 'profissional_nome', 'ano', 'total_ano', 'receita_ano', 'meses']);
        expect($data['meses'])->toHaveCount(12);
        expect($data['total_ano'])->toBe(0);
        expect((float) $data['receita_ano'])->toBe(0.0);
        expect($data['meses'][0])->toHaveKeys(['mes', 'mes_nome', 'total_finalizados', 'receita']);
    });

    it('distribui agendamentos pelo mês correto', function () {
        $ano = now()->year;
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => Carbon::createFromDate($ano, 3, 15)->setTime(10, 0),
            'duracao' => 30, 'valor' => 120.0,
            'status' => Agendamento::STATUS_FINALIZADO,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.faturamento-mensal', [$this->prof, 'ano' => $ano]))
            ->assertOk()
            ->json();

        expect($data['total_ano'])->toBe(1);
        expect((float) $data['receita_ano'])->toBe(120.0);

        $marco = collect($data['meses'])->firstWhere('mes', 3);
        expect($marco['total_finalizados'])->toBe(1);
        expect((float) $marco['receita'])->toBe(120.0);

        $janeiro = collect($data['meses'])->firstWhere('mes', 1);
        expect($janeiro['total_finalizados'])->toBe(0);
    });

    it('aceita parâmetro ano', function () {
        $anoAnterior = now()->year - 1;
        Agendamento::create([
            'company_id' => $this->company->id,
            'profissional_id' => $this->prof->id,
            'cliente_id' => $this->cliente->id,
            'servico_id' => $this->servico->id,
            'data_hora' => Carbon::createFromDate($anoAnterior, 6, 1)->setTime(10, 0),
            'duracao' => 30, 'valor' => 80.0,
            'status' => Agendamento::STATUS_FINALIZADO,
            'cancel_token' => Agendamento::generateCancelToken(),
        ]);

        $data = $this->actingAs($this->admin)
            ->getJson(route('profissionais.faturamento-mensal', [$this->prof, 'ano' => $anoAnterior]))
            ->assertOk()
            ->json();

        expect($data['ano'])->toBe($anoAnterior);
        expect($data['total_ano'])->toBe(1);
    });

    it('analista pode acessar', function () {
        $this->actingAs($this->analista)
            ->getJson(route('profissionais.faturamento-mensal', $this->prof))
            ->assertOk();
    });

    it('unauthenticated é rejeitado', function () {
        $this->getJson(route('profissionais.faturamento-mensal', $this->prof))
            ->assertUnauthorized();
    });
});
