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
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin_empresa', 'guard_name' => 'web', 'company_id' => null]);

    $this->company = Company::create(['name' => 'Empresa', 'slug' => 'empresa-func', 'plano' => 'trial', 'ativo' => true]);

    $this->profissional = Profissional::create([
        'company_id' => $this->company->id, 'name' => 'João Barbeiro',
        'ativo' => true, 'comissao_pct' => 40,
    ]);

    $this->servico = Servico::create([
        'company_id' => $this->company->id, 'nome' => 'Corte',
        'duracao_minutos' => 30, 'preco' => 50, 'cor' => '#1a1a1a', 'ativo' => true,
    ]);

    $this->cliente = Cliente::create([
        'company_id' => $this->company->id, 'name' => 'Cliente', 'phone' => '11999990000',
    ]);

    // Grupo de acesso com agenda própria apenas
    foreach (['cal_own', 'fin_own', 'cli_view'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
    $grupo = Role::create(['name' => 'Profissional', 'guard_name' => 'web', 'company_id' => $this->company->id]);
    $grupo->syncPermissions(['cal_own', 'fin_own', 'cli_view']);

    setPermissionsTeamId($this->company->id);

    $this->funcionario = User::create([
        'name' => 'João Barbeiro', 'email' => 'joao@func.test',
        'password' => bcrypt('secret123'), 'empresa_id' => $this->company->id,
        'profissional_id' => $this->profissional->id, 'ativo' => true,
    ]);
    $this->funcionario->assignRole($grupo);

    $this->admin = User::create([
        'name' => 'Admin', 'email' => 'admin@func.test',
        'password' => bcrypt('secret123'), 'empresa_id' => $this->company->id, 'ativo' => true,
    ]);
    $this->admin->assignRole('admin_empresa');

    setPermissionsTeamId(null);
});

afterEach(function () {
    setPermissionsTeamId(null);
});

describe('dashboard_funcionario', function () {
    it('redireciona funcionário sem cal_view para o dashboard próprio', function () {
        $this->actingAs($this->funcionario)
            ->get(route('dashboard'))
            ->assertRedirect(route('dashboard.funcionario'));
    });

    it('admin continua no dashboard completo', function () {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard');
    });

    it('exibe agenda própria e comissão do mês', function () {
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->profissional->id, 'servico_id' => $this->servico->id,
            'data_hora' => now()->setTime(23, 0), 'duracao' => 30, 'valor' => 50,
            'status' => Agendamento::STATUS_CONFIRMADO,
        ]);
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->profissional->id, 'servico_id' => $this->servico->id,
            'data_hora' => now()->subDays(2), 'duracao' => 30, 'valor' => 100,
            'status' => Agendamento::STATUS_FINALIZADO,
        ]);

        $resposta = $this->actingAs($this->funcionario)
            ->get(route('dashboard.funcionario'))
            ->assertOk()
            ->assertViewIs('dashboard-funcionario');

        $stats = $resposta->viewData('stats');
        expect($stats['agendaHoje'])->toHaveCount(1)
            ->and($stats['comissaoMes'])->toBe(40.0)
            ->and($stats['atendimentosMes'])->toBe(1);
    });

    it('não mostra agendamentos de outro profissional', function () {
        $outroProf = Profissional::create([
            'company_id' => $this->company->id, 'name' => 'Outro', 'ativo' => true,
        ]);
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $outroProf->id, 'servico_id' => $this->servico->id,
            'data_hora' => now()->setTime(23, 30), 'duracao' => 30, 'valor' => 50,
            'status' => Agendamento::STATUS_CONFIRMADO,
        ]);

        $resposta = $this->actingAs($this->funcionario)
            ->get(route('dashboard.funcionario'))
            ->assertOk();

        expect($resposta->viewData('stats')['agendaHoje'])->toHaveCount(0);
    });

    it('usuário sem vínculo de profissional vê aviso', function () {
        setPermissionsTeamId($this->company->id);
        $semVinculo = User::create([
            'name' => 'Sem Vínculo', 'email' => 'sem@func.test',
            'password' => bcrypt('secret123'), 'empresa_id' => $this->company->id, 'ativo' => true,
        ]);
        $grupo = Role::where('company_id', $this->company->id)->where('name', 'Profissional')->first();
        $semVinculo->assignRole($grupo);
        setPermissionsTeamId(null);

        $resposta = $this->actingAs($semVinculo)
            ->get(route('dashboard.funcionario'))
            ->assertOk();

        expect($resposta->viewData('stats'))->toBeNull();
    });

    it('endpoint receita retorna apenas valores próprios para quem não tem fin_view', function () {
        $outroProf = Profissional::create([
            'company_id' => $this->company->id, 'name' => 'Outro', 'ativo' => true,
        ]);
        // Finalizado do próprio (100) e de outro profissional (900)
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $this->profissional->id, 'servico_id' => $this->servico->id,
            'data_hora' => now(), 'duracao' => 30, 'valor' => 100,
            'status' => Agendamento::STATUS_FINALIZADO,
        ]);
        Agendamento::create([
            'company_id' => $this->company->id, 'cliente_id' => $this->cliente->id,
            'profissional_id' => $outroProf->id, 'servico_id' => $this->servico->id,
            'data_hora' => now(), 'duracao' => 30, 'valor' => 900,
            'status' => Agendamento::STATUS_FINALIZADO,
        ]);

        $this->actingAs($this->funcionario)
            ->getJson(route('dashboard.receita'))
            ->assertOk()
            ->assertJsonPath('hoje.receita', 100);

        $this->actingAs($this->admin)
            ->getJson(route('dashboard.receita'))
            ->assertOk()
            ->assertJsonPath('hoje.receita', 1000);
    });
});
